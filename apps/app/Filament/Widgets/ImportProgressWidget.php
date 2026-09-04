<?php

declare(strict_types=1);

namespace App\Filament\Widgets;

use Filament\Actions\Imports\Models\Import;
use Filament\Widgets\Widget;
use Illuminate\Support\Facades\Cache;

/**
 * Modal that tracks CSV imports on the Taxa list.
 *
 * Reads Filament's own Import model for the current user's latest import and
 * opens a modal with a live progress bar while it runs, then the
 * imported/failed summary when it finishes. The taxa list refreshes as soon
 * as the import completes (so new rows appear), and the whole page reloads
 * when the user closes the modal.
 */
class ImportProgressWidget extends Widget
{
    protected string $view = 'filament.widgets.import-progress-widget';

    protected int|string|array $columnSpan = 'full';

    public bool $modalOpened = false;

    public bool $completionAnnounced = false;

    /**
     * The current user's latest import when it is running or recently
     * finished and not dismissed; null hides the modal entirely.
     */
    public function getImport(): ?Import
    {
        $userId = auth()->id();

        if ($userId === null) {
            return null;
        }

        $import = Import::query()
            ->where('user_id', $userId)
            ->latest('id')
            ->first();

        // Cast both sides: Postgres returns the key as a string when re-queried,
        // which would defeat a strict comparison against the cached value.
        if ($import === null || (string) Cache::get($this->dismissedKey()) === (string) $import->getKey()) {
            return null;
        }

        // Running: not yet completed, and started recently enough that a dead
        // worker can't leave a stale "importing…" modal up forever.
        $isRunning = $import->completed_at === null
            && $import->created_at?->greaterThan(now()->subHour());

        // Freshly finished: keep the summary visible for a short window.
        // completed_at uses a `timestamp` cast (int), so compare epoch seconds.
        $recentlyCompleted = $import->completed_at !== null
            && $import->completed_at > now()->subMinutes(10)->getTimestamp();

        return ($isRunning || $recentlyCompleted) ? $import : null;
    }

    /**
     * Called each render: opens the modal once when an import appears and
     * refreshes the list once the moment it completes (new taxa show up
     * behind the modal without the user doing anything).
     */
    public function syncModal(?Import $import): void
    {
        if ($import === null) {
            $this->modalOpened = false;
            $this->completionAnnounced = false;

            return;
        }

        if (! $this->modalOpened) {
            $this->modalOpened = true;
            $this->dispatch('open-modal', id: 'import-progress');
        }

        if ($import->completed_at !== null && ! $this->completionAnnounced) {
            $this->completionAnnounced = true;
            $this->dispatch('import-completed'); // ListTaxons listens → $refresh
        }
    }

    /**
     * Close the modal and reload the page so the list reflects the import.
     * The dismissal is cached so the modal does not re-open after the reload.
     */
    public function dismiss(): void
    {
        $import = Import::query()
            ->where('user_id', auth()->id())
            ->latest('id')
            ->first();

        if ($import !== null) {
            Cache::put($this->dismissedKey(), $import->getKey(), now()->addDay());
        }

        $this->dispatch('close-modal', id: 'import-progress');
        $this->js('window.location.reload()');
    }

    private function dismissedKey(): string
    {
        return 'taxon-import-dismissed-'.auth()->id();
    }
}
