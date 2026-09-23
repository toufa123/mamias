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

    /**
     * Record a permanent failure for the progress widget, keeping whatever
     * progress the run had reached.
     *
     * handle() marks its own exceptions as failed, but a timeout or exhausted
     * retries go straight to the job's failed() without passing through it.
     * Without this the widget never learns the run is over and shows it as
     * starting indefinitely.
     */
    private function markProgressFailed(\Throwable $exception, int $total): void
    {
        if ($this->userId === null) {
            return;
        }

        $this->setProgress([
            'processed' => 0,
            'total' => $total,
            'percentage' => 0,
            ...(Cache::get(static::PROGRESS_CACHE_PREFIX.$this->userId) ?? []),
            'status' => 'failed',
            'estimatedTime' => '',
            'error' => $exception->getMessage(),
        ]);
    }

    /**
     * Whether the user has requested this sync be aborted. Checked at chunk
     * boundaries so the job can stop cooperatively.
     */
    private function isCancellationRequested(): bool
    {
        return $this->userId !== null && (bool) Cache::get($this->cancelKey());
    }

    /**
     * Clear any stale cancellation flag so a fresh (or resumed) run is not
     * killed immediately by a leftover request.
     */
    private function clearCancellation(): void
    {
        if ($this->userId !== null) {
            Cache::forget($this->cancelKey());
        }
    }

    private function cancelKey(): string
    {
        return str_replace('-progress-', '-cancel-', static::PROGRESS_CACHE_PREFIX).$this->userId;
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
