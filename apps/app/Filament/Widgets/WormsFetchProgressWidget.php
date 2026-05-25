<?php

namespace App\Filament\Widgets;

use Filament\Widgets\Widget;
use Illuminate\Support\Facades\Cache;
use Livewire\Attributes\On;

class WormsFetchProgressWidget extends Widget
{
    public ?int $userId = null;

    public ?array $taxonIds = [];

    public bool $isSyncing = false;

    public bool $isEasinSyncing = false;

    public bool $importRefreshTriggered = false;

    protected string $view = 'filament.widgets.worms-fetch-progress-widget';

    protected int | string | array $columnSpan = 'full';

    #[On('worms-fetch-started')]
    public function activate(): void
    {
        $this->isSyncing = true;
        $this->dispatch('open-modal', id: 'worms-sync-progress');
    }

    #[On('easin-fetch-started')]
    public function activateEasin(): void
    {
        $this->isEasinSyncing = true;
        $this->dispatch('open-modal', id: 'easin-sync-progress');
    }

    public function getProgress(): ?array
    {
        $progress = Cache::get('worms-fetch-progress-' . (auth()->id() ?? $this->userId));

        if ($progress) {
            $this->isSyncing = true;
        }

        return $progress;
    }

    public function getEasinProgress(): ?array
    {
        $progress = Cache::get('easin-fetch-progress-' . (auth()->id() ?? $this->userId));

        if ($progress) {
            $this->isEasinSyncing = true;
        }

        return $progress;
    }

    public function getImportResult(): ?array
    {
        $result = Cache::get('taxon-import-completed-' . (auth()->id() ?? $this->userId));

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

    public function dismiss(): void
    {
        Cache::forget('worms-fetch-progress-' . (auth()->id() ?? $this->userId));
        $this->isSyncing = false;
        $this->dispatch('close-modal', id: 'worms-sync-progress');
        $this->dispatch('worms-fetch-completed');
    }

    public function dismissEasin(): void
    {
        Cache::forget('easin-fetch-progress-' . (auth()->id() ?? $this->userId));
        $this->isEasinSyncing = false;
        $this->dispatch('close-modal', id: 'easin-sync-progress');
        $this->dispatch('worms-fetch-completed');
    }

    public function dismissImport(): void
    {
        Cache::forget('taxon-import-completed-' . (auth()->id() ?? $this->userId));
        $this->importRefreshTriggered = false;
        $this->dispatch('close-modal', id: 'import-result');
        $this->dispatch('worms-fetch-completed');
    }
}
