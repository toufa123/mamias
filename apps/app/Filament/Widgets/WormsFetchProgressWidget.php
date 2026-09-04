<?php

namespace App\Filament\Widgets;

use App\Jobs\FetchEasinIdsJob;
use App\Jobs\FetchTaxaFromWormsJob;
use Filament\Widgets\Widget;
use Illuminate\Support\Facades\Cache;
use Livewire\Attributes\On;

/**
 * Livewire widget that tracks WoRMS/EASIN fetch progress, displays sync
 * modals, and handles import result notifications via cache-backed state.
 *
 * Supports aborting a running sync (a cooperative cancel flag the jobs poll
 * between chunks) and resuming an aborted sync by re-dispatching the fetch
 * for the records that were never processed.
 */
class WormsFetchProgressWidget extends Widget
{
    public ?int $userId = null;

    public ?array $taxonIds = [];

    public bool $isSyncing = false;

    public bool $isEasinSyncing = false;

    public bool $importRefreshTriggered = false;

    public bool $abortingWorms = false;

    public bool $abortingEasin = false;

    protected string $view = 'filament.widgets.worms-fetch-progress-widget';

    protected int|string|array $columnSpan = 'full';

    /**
     * Handles the 'worms-fetch-started' event: sets syncing flag and
     * opens the WoRMS progress modal.
     */
    #[On('worms-fetch-started')]
    public function activate(): void
    {
        $this->isSyncing = true;
        $this->abortingWorms = false;
        $this->dispatch('open-modal', id: 'worms-sync-progress');
    }

    /**
     * Handles the 'easin-fetch-started' event: sets EASIN syncing flag
     * and opens the EASIN progress modal.
     */
    #[On('easin-fetch-started')]
    public function activateEasin(): void
    {
        $this->isEasinSyncing = true;
        $this->abortingEasin = false;
        $this->dispatch('open-modal', id: 'easin-sync-progress');
    }

    /**
     * Returns the current WoRMS fetch progress from cache, or null if
     * no sync is in progress.
     */
    public function getProgress(): ?array
    {
        $progress = Cache::get('worms-fetch-progress-'.(auth()->id() ?? $this->userId));

        if ($progress) {
            $this->isSyncing = true;
        }

        return $progress;
    }

    /**
     * Returns the current EASIN fetch progress from cache, or null if
     * no sync is in progress.
     */
    public function getEasinProgress(): ?array
    {
        $progress = Cache::get('easin-fetch-progress-'.(auth()->id() ?? $this->userId));

        if ($progress) {
            $this->isEasinSyncing = true;
        }

        return $progress;
    }

    /**
     * Checks for a completed taxon import result in cache. Dispatches
     * import-completed and open-modal events when a fresh result is found.
     */
    public function getImportResult(): ?array
    {
        $result = Cache::get('taxon-import-completed-'.(auth()->id() ?? $this->userId));

        if ($result && ! $this->importRefreshTriggered) {
            $this->importRefreshTriggered = true;
            $this->dispatch('import-completed');
            $this->dispatch('open-modal', id: 'import-result');
        }

        if (! $result) {
            $this->importRefreshTriggered = false;
        }

        return $result;
    }

    /**
     * Request abort of the running WoRMS sync. The job polls this flag at
     * chunk boundaries and stops, recording the unprocessed ids for resume.
     */
    public function abortWorms(): void
    {
        $this->abortingWorms = true;
        Cache::put('worms-fetch-cancel-'.(auth()->id() ?? $this->userId), true, now()->addHour());
    }

    /**
     * Request abort of the running EASIN fetch.
     */
    public function abortEasin(): void
    {
        $this->abortingEasin = true;
        Cache::put('easin-fetch-cancel-'.(auth()->id() ?? $this->userId), true, now()->addHour());
    }

    /**
     * Resume an aborted WoRMS sync by re-dispatching the fetch for the ids
     * that were never processed.
     */
    public function resumeWorms(): void
    {
        $userId = auth()->id() ?? $this->userId;
        $remaining = Cache::get('worms-fetch-progress-'.$userId)['remaining_ids'] ?? [];

        Cache::forget('worms-fetch-progress-'.$userId);
        $this->abortingWorms = false;

        if ($remaining !== [] && $userId !== null) {
            FetchTaxaFromWormsJob::dispatch(array_values($remaining), $userId);
            $this->isSyncing = true;
            $this->dispatch('open-modal', id: 'worms-sync-progress');

            return;
        }

        $this->isSyncing = false;
        $this->dispatch('close-modal', id: 'worms-sync-progress');
        $this->dispatch('worms-fetch-completed');
    }

    /**
     * Resume an aborted EASIN fetch for the unprocessed ids.
     */
    public function resumeEasin(): void
    {
        $userId = auth()->id() ?? $this->userId;
        $remaining = Cache::get('easin-fetch-progress-'.$userId)['remaining_ids'] ?? [];

        Cache::forget('easin-fetch-progress-'.$userId);
        $this->abortingEasin = false;

        if ($remaining !== [] && $userId !== null) {
            FetchEasinIdsJob::dispatch(array_values($remaining), $userId);
            $this->isEasinSyncing = true;
            $this->dispatch('open-modal', id: 'easin-sync-progress');

            return;
        }

        $this->isEasinSyncing = false;
        $this->dispatch('close-modal', id: 'easin-sync-progress');
        $this->dispatch('worms-fetch-completed');
    }

    /**
     * Clears the WoRMS progress cache, hides the modal, and dispatches
     * the worms-fetch-completed event.
     */
    public function dismiss(): void
    {
        Cache::forget('worms-fetch-progress-'.(auth()->id() ?? $this->userId));
        $this->isSyncing = false;
        $this->abortingWorms = false;
        $this->dispatch('close-modal', id: 'worms-sync-progress');
        $this->dispatch('worms-fetch-completed');
    }

    /**
     * Clears the EASIN progress cache, hides the modal, and dispatches
     * the worms-fetch-completed event.
     */
    public function dismissEasin(): void
    {
        Cache::forget('easin-fetch-progress-'.(auth()->id() ?? $this->userId));
        $this->isEasinSyncing = false;
        $this->abortingEasin = false;
        $this->dispatch('close-modal', id: 'easin-sync-progress');
        $this->dispatch('worms-fetch-completed');
    }

    /**
     * Clears the import result cache, resets the trigger flag, hides the
     * modal, and dispatches the worms-fetch-completed event.
     */
    public function dismissImport(): void
    {
        Cache::forget('taxon-import-completed-'.(auth()->id() ?? $this->userId));
        $this->importRefreshTriggered = false;
        $this->dispatch('close-modal', id: 'import-result');
        $this->dispatch('worms-fetch-completed');
    }
}
