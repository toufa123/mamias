<?php

namespace App\Jobs;

use App\Exceptions\SyncCancelledException;
use App\Jobs\Concerns\TracksJobProgress;
use App\Models\Taxon;
use App\Models\User;
use App\Services\TaxonService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Notification;

class FetchTaxaFromWormsJob implements ShouldQueue
{
    use Queueable;
    use TracksJobProgress;

    public int $timeout = 1800;

    public int $tries = 3;

    public array $backoff = [30, 60, 120];

    private const SECONDS_PER_TAXON = 1;

    private const PROGRESS_CACHE_PREFIX = 'worms-fetch-progress-';

    /**
     * @param  array<int>  $taxonIds
     */
    public function __construct(
        private readonly array $taxonIds,
        private readonly ?int $userId = null,
    ) {}

    public function handle(TaxonService $taxonService): void
    {
        $total = count($this->taxonIds);
        $startTime = microtime(true);
        $processed = 0;
        $totals = ['updated' => 0, 'missing_aphia_id' => 0, 'not_found' => 0];
        $processedIds = [];
        $cancelled = false;

        // A resumed/new run must not inherit a stale abort request.
        $this->clearCancellation();

        $this->updateProgress($processed, $total, $startTime);

        try {
            Taxon::whereIn('id', $this->taxonIds)
                ->chunkById(50, function (Collection $chunk) use ($taxonService, &$totals, &$processed, &$processedIds, $total, $startTime): void {
                    $result = $taxonService->refreshFromWorms($chunk, function (Taxon $taxon) use (&$processed, &$processedIds, $total, $startTime): void {
                        $processed++;
                        $processedIds[] = $taxon->id;
                        $this->updateProgress($processed, $total, $startTime);

                        // Abort as soon as the user requests it, mid-chunk.
                        if ($this->isCancellationRequested()) {
                            throw new SyncCancelledException;
                        }
                    });

                    $totals['updated'] += $result['updated'];
                    $totals['missing_aphia_id'] += $result['missing_aphia_id'];
                    $totals['not_found'] += $result['not_found'];
                });
        } catch (SyncCancelledException) {
            $cancelled = true;
        } catch (\Throwable $e) {
            Log::error("FetchTaxaFromWormsJob failed: {$e->getMessage()}");

            $this->setProgress([
                'status' => 'failed',
                'processed' => $processed,
                'total' => $total,
                'percentage' => $total > 0 ? round(($processed / $total) * 100) : 0,
                'estimatedTime' => '',
                'error' => $e->getMessage(),
            ]);

            throw $e;
        }

        if ($cancelled) {
            $remainingIds = array_values(array_diff($this->taxonIds, $processedIds));

            $this->clearCancellation();
            $this->setProgress([
                'status' => 'cancelled',
                'processed' => $processed,
                'total' => $total,
                'percentage' => $total > 0 ? round(($processed / $total) * 100) : 0,
                'estimatedTime' => '',
                'remaining' => count($remainingIds),
                'remaining_ids' => $remainingIds,
                'totals' => $totals,
            ]);

            return;
        }

        $this->setProgress([
            'status' => 'completed',
            'processed' => $total,
            'total' => $total,
            'percentage' => 100,
            'estimatedTime' => '',
            'totals' => $totals,
        ]);
    }

    public function failed(\Throwable $exception): void
    {
        Log::error('FetchTaxaFromWormsJob failed permanently', [
            'taxon_ids' => $this->taxonIds,
            'user_id' => $this->userId,
            'message' => $exception->getMessage(),
        ]);

        if ($this->userId) {
            $user = User::find($this->userId);
            if ($user) {
                Notification::make()
                    ->title('WoRMS sync failed')
                    ->body('The taxonomy update failed after multiple attempts: '.$exception->getMessage())
                    ->danger()
                    ->sendToDatabase($user);
            }
        }
    }

    public static function estimateDuration(int $count): string
    {
        return self::formatDuration($count * self::SECONDS_PER_TAXON);
    }
}
