<?php

namespace App\Jobs;

use App\Jobs\Concerns\TracksJobProgress;
use App\Models\Taxon;
use App\Models\User;
use App\Services\EasinService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Notification;

class FetchEasinIdsJob implements ShouldQueue
{
    use Queueable;
    use TracksJobProgress;

    public int $timeout = 1800;

    public int $tries = 3;

    public array $backoff = [30, 60, 120];

    private const SECONDS_PER_TAXON = 1;

    private const PROGRESS_CACHE_PREFIX = 'easin-fetch-progress-';

    /**
     * @param  array<int>  $taxonIds
     */
    public function __construct(
        private readonly array $taxonIds,
        private readonly ?int $userId = null,
    ) {}

    public function handle(EasinService $easinService): void
    {
        $total = count($this->taxonIds);
        $startTime = microtime(true);
        $processed = 0;
        $totals = ['found' => 0, 'not_found' => 0, 'skipped' => 0];
        $processedIds = [];
        $cancelled = false;

        // A resumed/new run must not inherit a stale abort request.
        $this->clearCancellation();

        $this->updateProgress($processed, $total, $startTime);

        try {
            Taxon::whereIn('id', $this->taxonIds)
                ->chunkById(50, function (Collection $chunk) use ($easinService, &$totals, &$processed, &$processedIds, &$cancelled, $total, $startTime): bool {
                    foreach ($chunk as $taxon) {
                        if ($this->isCancellationRequested()) {
                            $cancelled = true;

                            return false; // stop; unprocessed taxa are left for resume
                        }

                        if (! $taxon->scientificname) {
                            $totals['skipped']++;
                        } else {
                            $easinId = $easinService->fetchEasinId($taxon->scientificname);
                            if ($easinId) {
                                $taxon->update(['Easin_id' => $easinId]);
                                $totals['found']++;
                            } else {
                                $totals['not_found']++;
                            }
                        }

                        $processedIds[] = $taxon->id;
                        $processed++;
                        $this->updateProgress($processed, $total, $startTime);
                    }

                    return true;
                });
        } catch (\Throwable $e) {
            Log::error("FetchEasinIdsJob failed: {$e->getMessage()}");

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
        Log::error('FetchEasinIdsJob failed permanently', [
            'taxon_ids' => $this->taxonIds,
            'user_id' => $this->userId,
            'message' => $exception->getMessage(),
        ]);

        if ($this->userId) {
            $user = User::find($this->userId);
            if ($user) {
                Notification::make()
                    ->title('EASIN ID fetch failed')
                    ->body('The EASIN ID lookup failed after multiple attempts: '.$exception->getMessage())
                    ->danger()
                    ->sendToDatabase($user);
            }
        }
    }
}
