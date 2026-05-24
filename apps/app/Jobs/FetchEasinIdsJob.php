<?php

namespace App\Jobs;

use App\Models\Taxon;
use App\Services\EasinService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

class FetchEasinIdsJob implements ShouldQueue
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

    public function handle(EasinService $easinService): void
    {
        $total = count($this->taxonIds);
        $startTime = microtime(true);
        $processed = 0;
        $totals = ['found' => 0, 'not_found' => 0, 'skipped' => 0];

        $this->updateProgress($processed, $total, $startTime);

        try {
            Taxon::whereIn('id', $this->taxonIds)
                ->chunkById(50, function (Collection $chunk) use ($easinService, &$totals, &$processed, $total, $startTime): void {
                    foreach ($chunk as $taxon) {
                        if (! $taxon->scientificname) {
                            $totals['skipped']++;
                            $processed++;
                            $this->updateProgress($processed, $total, $startTime);

                            continue;
                        }

                        $easinId = $easinService->fetchEasinId($taxon->scientificname);
                        if ($easinId) {
                            $taxon->update(['Easin_id' => $easinId]);
                            $totals['found']++;
                        } else {
                            $totals['not_found']++;
                        }

                        $processed++;
                        $this->updateProgress($processed, $total, $startTime);
                    }
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

        $this->setProgress([
            'status' => 'completed',
            'processed' => $total,
            'total' => $total,
            'percentage' => 100,
            'estimatedTime' => '',
            'totals' => $totals,
        ]);
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
            $estimatedTime = self::formatDuration($total * self::SECONDS_PER_TAXON);
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

        Cache::put("easin-fetch-progress-{$this->userId}", $data, now()->addHour());
    }

    private static function formatDuration(float $seconds): string
    {
        if ($seconds < 60) {
            return round($seconds).' seconds';
        }

        if ($seconds < 3600) {
            return round($seconds / 60, 1).' minutes';
        }

        return round($seconds / 3600, 1).' hours';
    }
}
