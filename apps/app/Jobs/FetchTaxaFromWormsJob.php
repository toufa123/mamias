<?php

namespace App\Jobs;

use App\Models\Taxon;
use App\Services\TaxonService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

class FetchTaxaFromWormsJob implements ShouldQueue
{
    use Queueable;

    public int $timeout = 1800;

    public int $tries = 1;

    private const SECONDS_PER_TAXON = 1;

    private const CHUNK_SIZE = 50;

    private const CACHE_DURATION = 3600;

    private int $processed = 0;

    private array $totals = [];

    public function __construct(
        private readonly array $taxonIds,
        private readonly ?int $userId = null,
    ) {}

    public function handle(TaxonService $taxonService): void
    {
        $this->totals = ['updated' => 0, 'missing_aphia_id' => 0, 'not_found' => 0];
        $startTime = microtime(true);
        $total = count($this->taxonIds);

        $this->reportProgress('running', 0, $total, $startTime);

        try {
            $this->processTaxaChunks($taxonService, $total, $startTime);
            $this->reportCompletion($total);
        } catch (\Throwable $e) {
            $this->reportError($e, $total);
            throw $e;
        }
    }

    private function processTaxaChunks(TaxonService $taxonService, int $total, float $startTime): void
    {
        Taxon::whereIn('id', $this->taxonIds)
            ->chunkById(self::CHUNK_SIZE, function (Collection $chunk) use ($taxonService, $total, $startTime): void {
                $result = $taxonService->refreshFromWorms($chunk, function () use ($total, $startTime) {
                    $this->processed++;
                    $this->reportProgress('running', $this->processed, $total, $startTime);
                });

                $this->totals['updated'] += $result['updated'];
                $this->totals['missing_aphia_id'] += $result['missing_aphia_id'];
                $this->totals['not_found'] += $result['not_found'];
            });
    }

    private function reportProgress(string $status, int $processed, int $total, float $startTime): void
    {
        $this->cacheProgress([
            'status' => $status,
            'processed' => $processed,
            'total' => $total,
            'percentage' => $this->calculatePercentage($processed, $total),
            'estimatedTime' => $this->calculateEstimatedTime($processed, $total, $startTime),
        ]);
    }

    private function reportCompletion(int $total): void
    {
        $this->cacheProgress([
            'status' => 'completed',
            'processed' => $total,
            'total' => $total,
            'percentage' => 100,
            'estimatedTime' => '',
            'totals' => $this->totals,
        ]);
    }

    private function reportError(\Throwable $e, int $total): void
    {
        Log::error("FetchTaxaFromWormsJob failed: {$e->getMessage()}");

        $this->cacheProgress([
            'status' => 'failed',
            'processed' => $this->processed,
            'total' => $total,
            'percentage' => $this->calculatePercentage($this->processed, $total),
            'estimatedTime' => '',
            'error' => $e->getMessage(),
        ]);
    }

    private function calculatePercentage(int $processed, int $total): int
    {
        return $total > 0 ? (int) round(($processed / $total) * 100) : 0;
    }

    private function calculateEstimatedTime(int $processed, int $total, float $startTime): string
    {
        if ($processed === 0) {
            return self::formatDuration($total * self::SECONDS_PER_TAXON);
        }

        $elapsed = microtime(true) - $startTime;
        $rate = $processed / $elapsed;
        $remaining = $rate > 0 ? ($total - $processed) / $rate : 0;

        return self::formatDuration($remaining);
    }

    public static function estimateDuration(int $count): string
    {
        return self::formatDuration($count * self::SECONDS_PER_TAXON);
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

    private function cacheProgress(array $data): void
    {
        if ($this->userId === null) {
            return;
        }

        Cache::put("worms-fetch-progress-{$this->userId}", $data, now()->addSeconds(self::CACHE_DURATION));
    }
}
