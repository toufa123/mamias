<?php

namespace App\Jobs\Concerns;

use Illuminate\Support\Facades\Cache;

trait TracksJobProgress
{
    private function updateProgress(int $processed, int $total, float $startTime): void
    {
        $percentage = $total > 0 ? round(($processed / $total) * 100) : 0;

        if ($processed > 0) {
            $elapsed = microtime(true) - $startTime;
            $rate = $processed / $elapsed;
            $remaining = $rate > 0 ? ($total - $processed) / $rate : 0;
            $estimatedTime = self::formatDuration($remaining);
        } else {
            $estimatedTime = self::formatDuration($total * static::SECONDS_PER_TAXON);
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

        $cacheKey = static::PROGRESS_CACHE_PREFIX.$this->userId;
        Cache::put($cacheKey, $data, now()->addHour());
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
