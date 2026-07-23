@php
    $progress = $this->getProgress();
    $status = $progress['status'] ?? null;
    $isRunning = $status === 'running';
    $isCompleted = $status === 'completed';
    $isFailed = $status === 'failed';

    // Syncing is active if the component says so OR if we have data in cache
    $isSyncing = $this->isSyncing || ($progress !== null);

    $easinProgress = $this->getEasinProgress();
    $easinStatus = $easinProgress['status'] ?? null;
    $easinIsRunning = $easinStatus === 'running';
    $easinIsCompleted = $easinStatus === 'completed';
    $easinIsFailed = $easinStatus === 'failed';
    $isEasinSyncing = $this->isEasinSyncing || ($easinProgress !== null);

    $importResult = $this->getImportResult();
    $hasImportResult = $importResult !== null;

    $processed = $progress['processed'] ?? 0;
    $total = $progress['total'] ?? 0;
    $percentage = $progress['percentage'] ?? 0;

    $easinProcessed = $easinProgress['processed'] ?? 0;
    $easinTotal = $easinProgress['total'] ?? 0;
    $easinPercentage = $easinProgress['percentage'] ?? 0;
@endphp

<div>

    {{-- WoRMS sync modal --}}
    <x-filament::modal
        id="worms-sync-progress"
        icon="tabler-cloud-download"
        icon-color="primary"
        :close-button="false"
        :close-by-clicking-away="false"
        :close-by-escaping="false"
        width="lg"
    >
        <x-slot name="heading">
            @if($isRunning)
                Syncing taxonomy from WoRMS
            @elseif($isCompleted)
                Taxonomy sync complete
            @elseif($isFailed)
                Taxonomy sync failed
            @endif
        </x-slot>

        <x-slot name="description">
            @if($isRunning)
                Updating {{ $total }} {{ Str::plural('species', $total) }} with the latest classification data.
            @elseif($isCompleted)
                @php $totals = $progress['totals'] ?? []; @endphp
                All species have been processed successfully.
            @elseif($isFailed)
                {{ $progress['error'] ?? 'An unexpected error occurred.' }}
            @endif
        </x-slot>

        <div @if($isSyncing) wire:poll.2s @endif>

            @if($isRunning)
                <div class="space-y-4">
                    <div
                        class="pb-track"
                        data-pb-size="md"
                        data-pb-shape="pill"
                        data-pb-color="primary"
                        data-pb-indeterminate="false"
                        role="progressbar"
                        aria-valuenow="{{ $percentage }}"
                        aria-valuemin="0"
                        aria-valuemax="100"
                    >
                        <div
                            class="pb-fill"
                            style="width: {{ $percentage }}%;"
                            data-pb-striped="true"
                            data-pb-animated="true"
                            data-pb-gradient="true"
                            data-pb-full="{{ $percentage >= 100 ? 'true' : 'false' }}"
                        ></div>
                        <div class="pb-label" data-pb-full="{{ $percentage >= 100 ? 'true' : 'false' }}">
                            <span>{{ $percentage }}%</span>
                        </div>
                    </div>

                    <div class="flex items-center justify-between text-sm">
                        <span class="font-medium text-primary-600 dark:text-primary-400">
                            {{ $processed }} of {{ $total }} processed
                        </span>
                        @if(! empty($progress['estimatedTime']))
                            <span class="flex items-center gap-1.5 text-gray-500 dark:text-gray-400">
                                <x-tabler-clock class="h-4 w-4" />
                                ~{{ $progress['estimatedTime'] }} remaining
                            </span>
                        @endif
                    </div>
                </div>

            @elseif($isCompleted)
                @php $totals = $progress['totals'] ?? []; @endphp
                <div class="grid grid-cols-3 gap-3">
                    <div class="rounded-lg bg-success-50 p-3 text-center dark:bg-success-950/30">
                        <p class="text-2xl font-bold text-success-600 dark:text-success-400">{{ $totals['updated'] ?? 0 }}</p>
                        <p class="text-xs text-success-600/70 dark:text-success-400/70">Updated</p>
                    </div>
                    <div class="rounded-lg bg-warning-50 p-3 text-center dark:bg-warning-950/30">
                        <p class="text-2xl font-bold text-warning-600 dark:text-warning-400">{{ $totals['not_found'] ?? 0 }}</p>
                        <p class="text-xs text-warning-600/70 dark:text-warning-400/70">Not found</p>
                    </div>
                    <div class="rounded-lg bg-gray-50 p-3 text-center dark:bg-gray-800">
                        <p class="text-2xl font-bold text-gray-600 dark:text-gray-400">{{ $totals['missing_aphia_id'] ?? 0 }}</p>
                        <p class="text-xs text-gray-500 dark:text-gray-400/70">Missing Aphia ID</p>
                    </div>
                </div>

            @elseif($isFailed)
                <div class="rounded-lg bg-danger-50 p-4 dark:bg-danger-950/30">
                    <p class="text-sm text-danger-700 dark:text-danger-300">
                        {{ $progress['error'] ?? 'An unexpected error occurred. Please try again.' }}
                    </p>
                </div>
            @endif

        </div>

        <x-slot name="footerActions">
            @if($isCompleted)
                <x-filament::button wire:click="dismiss" color="success">
                    Done
                </x-filament::button>
            @elseif($isFailed)
                <x-filament::button wire:click="dismiss" color="danger">
                    Dismiss
                </x-filament::button>
            @endif
        </x-slot>
    </x-filament::modal>

    {{-- EASIN sync modal --}}
    <x-filament::modal
        id="easin-sync-progress"
        icon="tabler-cloud-download"
        icon-color="success"
        :close-button="false"
        :close-by-clicking-away="false"
        :close-by-escaping="false"
        width="lg"
    >
        <x-slot name="heading">
            @if($easinIsRunning)
                Fetching EASIN IDs
            @elseif($easinIsCompleted)
                EASIN fetch complete
            @elseif($easinIsFailed)
                EASIN fetch failed
            @endif
        </x-slot>

        <x-slot name="description">
            @if($easinIsRunning)
                Looking up EASIN IDs for {{ $easinTotal }} {{ Str::plural('species', $easinTotal) }}.
            @elseif($easinIsCompleted)
                All species have been processed successfully.
            @elseif($easinIsFailed)
                {{ $easinProgress['error'] ?? 'An unexpected error occurred.' }}
            @endif
        </x-slot>

        <div @if($isEasinSyncing) wire:poll.2s @endif>

            @if($easinIsRunning)
                <div class="space-y-4">
                    <div
                        class="pb-track"
                        data-pb-size="md"
                        data-pb-shape="pill"
                        data-pb-color="success"
                        data-pb-indeterminate="false"
                        role="progressbar"
                        aria-valuenow="{{ $easinPercentage }}"
                        aria-valuemin="0"
                        aria-valuemax="100"
                    >
                        <div
                            class="pb-fill"
                            style="width: {{ $easinPercentage }}%;"
                            data-pb-striped="true"
                            data-pb-animated="true"
                            data-pb-gradient="true"
                            data-pb-full="{{ $easinPercentage >= 100 ? 'true' : 'false' }}"
                        ></div>
                        <div class="pb-label" data-pb-full="{{ $easinPercentage >= 100 ? 'true' : 'false' }}">
                            <span>{{ $easinPercentage }}%</span>
                        </div>
                    </div>

                    <div class="flex items-center justify-between text-sm">
                        <span class="font-medium text-success-600 dark:text-success-400">
                            {{ $easinProcessed }} of {{ $easinTotal }} processed
                        </span>
                        @if(! empty($easinProgress['estimatedTime']))
                            <span class="flex items-center gap-1.5 text-gray-500 dark:text-gray-400">
                                <x-tabler-clock class="h-4 w-4" />
                                ~{{ $easinProgress['estimatedTime'] }} remaining
                            </span>
                        @endif
                    </div>
                </div>

            @elseif($easinIsCompleted)
                @php $easinTotals = $easinProgress['totals'] ?? []; @endphp
                <div class="grid grid-cols-3 gap-3">
                    <div class="rounded-lg bg-success-50 p-3 text-center dark:bg-success-950/30">
                        <p class="text-2xl font-bold text-success-600 dark:text-success-400">{{ $easinTotals['found'] ?? 0 }}</p>
                        <p class="text-xs text-success-600/70 dark:text-success-400/70">Found</p>
                    </div>
                    <div class="rounded-lg bg-warning-50 p-3 text-center dark:bg-warning-950/30">
                        <p class="text-2xl font-bold text-warning-600 dark:text-warning-400">{{ $easinTotals['not_found'] ?? 0 }}</p>
                        <p class="text-xs text-warning-600/70 dark:text-warning-400/70">Not found</p>
                    </div>
                    <div class="rounded-lg bg-gray-50 p-3 text-center dark:bg-gray-800">
                        <p class="text-2xl font-bold text-gray-600 dark:text-gray-400">{{ $easinTotals['skipped'] ?? 0 }}</p>
                        <p class="text-xs text-gray-500 dark:text-gray-400/70">Skipped</p>
                    </div>
                </div>

            @elseif($easinIsFailed)
                <div class="rounded-lg bg-danger-50 p-4 dark:bg-danger-950/30">
                    <p class="text-sm text-danger-700 dark:text-danger-300">
                        {{ $easinProgress['error'] ?? 'An unexpected error occurred. Please try again.' }}
                    </p>
                </div>
            @endif

        </div>

        <x-slot name="footerActions">
            @if($easinIsCompleted)
                <x-filament::button wire:click="dismissEasin" color="success">
                    Done
                </x-filament::button>
            @elseif($easinIsFailed)
                <x-filament::button wire:click="dismissEasin" color="danger">
                    Dismiss
                </x-filament::button>
            @endif
        </x-slot>
    </x-filament::modal>

    {{-- Import result modal --}}
    <x-filament::modal
        id="import-result"
        icon="tabler-file-check"
        icon-color="success"
        :close-button="false"
        :close-by-clicking-away="false"
        :close-by-escaping="false"
        width="md"
    >
        <x-slot name="heading">
            Import complete
        </x-slot>

        <x-slot name="description">
            Your data has been imported successfully.
        </x-slot>

        @if($hasImportResult)
        <div class="grid grid-cols-2 gap-3">
            <div class="rounded-lg bg-success-50 p-3 text-center dark:bg-success-950/30">
                <p class="text-2xl font-bold text-success-600 dark:text-success-400">{{ $importResult['successful_rows'] ?? 0 }}</p>
                <p class="text-xs text-success-600/70 dark:text-success-400/70">{{ Str::plural('Row', $importResult['successful_rows'] ?? 0) }} imported</p>
            </div>
            <div class="rounded-lg {{ ($importResult['failed_rows'] ?? 0) > 0 ? 'bg-danger-50 dark:bg-danger-950/30' : 'bg-gray-50 dark:bg-gray-800' }} p-3 text-center">
                <p class="text-2xl font-bold {{ ($importResult['failed_rows'] ?? 0) > 0 ? 'text-danger-600 dark:text-danger-400' : 'text-gray-400 dark:text-gray-500' }}">{{ $importResult['failed_rows'] ?? 0 }}</p>
                <p class="text-xs {{ ($importResult['failed_rows'] ?? 0) > 0 ? 'text-danger-600/70 dark:text-danger-400/70' : 'text-gray-500 dark:text-gray-400/70' }}">Failed</p>
            </div>
        </div>
        @endif

        <x-slot name="footerActions">
            <x-filament::button wire:click="dismissImport" color="success">
                Done
            </x-filament::button>
        </x-slot>
    </x-filament::modal>

    <div
        wire:poll.5s
        x-data="{ hasSynced: false, hasEasinSynced: false }"
        x-init="
            @if($isSyncing)
                if (! hasSynced) { $dispatch('open-modal', { id: 'worms-sync-progress' }); hasSynced = true; }
            @else
                if (hasSynced) { $dispatch('close-modal', { id: 'worms-sync-progress' }); hasSynced = false; }
            @endif

            @if($isEasinSyncing)
                if (! hasEasinSynced) { $dispatch('open-modal', { id: 'easin-sync-progress' }); hasEasinSynced = true; }
            @else
                if (hasEasinSynced) { $dispatch('close-modal', { id: 'easin-sync-progress' }); hasEasinSynced = false; }
            @endif
        "
    ></div>

</div>
