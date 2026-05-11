<?php

namespace App\Jobs;

use App\Models\Taxon;
use App\Services\TaxonService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

class FetchTaxaFromWormsJob implements ShouldQueue
{
    use Queueable;

    public int $timeout = 1800;

    public int $tries = 1;

    private const SECONDS_PER_TAXON = 1;

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

        $this->updateProgress($processed, $total, $startTime);

        try {
            Taxon::whereIn('id', $this->taxonIds)
                ->chunkById(50, function (\Illuminate\Database\Eloquent\Collection $chunk) use ($taxonService, &$totals, &$processed, $total, $startTime): void {
                    $result = $taxonService->refreshFromWorms($chunk, function () use (&$processed, $total, $startTime) {
                        $processed++;
                        $this->updateProgress($processed, $total, $startTime);
                    });

                    $totals['updated'] += $result['updated'];
                    $totals['missing_aphia_id'] += $result['missing_aphia_id'];
                    $totals['not_found'] += $result['not_found'];
                });
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

        $this->setProgress([
            'status' => 'completed',
            'processed' => $total,
            'total' => $total,
            'percentage' => 100,
            'estimatedTime' => '',
            'totals' => $totals,
        ]);
    }

    public static function estimateDuration(int $count): string
    {
        return self::formatDuration($count * self::SECONDS_PER_TAXON);
    }

    private function updateProgress(int $processed, int $total, float $startTime): void
    {
        $percentage = $total > 0 ? round(($processed / $total) * 100) : 0;

        if ($processed > 0) {
            $elapsed = microtime(true) - $startTime;
            $rate = $processed / $elapsed;
            $remaining = $rate > 0 ? ($total - $processed) / $rate : 0;
            $estimatedTime = self::formatDuration($remaining);
        } else {
            $estimatedTime = self::estimateDuration($total);
        }

        $this->setProgress([
            'status' => 'running',
            'processed' => $processed,
            'total' => $total,
            'percentage' => $percentage,
            'estimatedTime' => $estimatedTime,
        ]);
    }

    private function setProgress(array $data): void
    {
        if ($this->userId === null) {
            return;
        }

        Cache::put("worms-fetch-progress-{$this->userId}", $data, now()->addHour());
    }

    private static function formatDuration(float $seconds): string
    {
        if ($seconds < 60) {
            return round($seconds) . ' seconds';
        }

        if ($seconds < 3600) {
            return round($seconds / 60, 1) . ' minutes';
        }

        return round($seconds / 3600, 1) . ' hours';
    }
}
