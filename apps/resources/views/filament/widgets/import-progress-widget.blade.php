@php
    $import = $this->getImport();
    $this->syncModal($import);

    $isRunning = $import && $import->completed_at === null;
    $isCompleted = $import && $import->completed_at !== null;

    $total = (int) ($import->total_rows ?? 0);
    $processed = (int) ($import->processed_rows ?? 0);
    $successful = (int) ($import->successful_rows ?? 0);
    $notImported = $import ? (int) $import->getFailedRowsCount() : 0;
    // Skipped rows are logged as failures, so subtract them to leave real failures.
    $skipped = $this->getSkippedRowsCount($import);
    $failed = max(0, $notImported - $skipped);
    $percentage = $total > 0
        ? min(100, (int) round($processed / $total * 100))
        : ($isRunning ? 0 : 100);

    // Poll while running so the bar advances; slower when idle to catch a new import.
    $pollRate = $isRunning ? '2s' : '5s';
@endphp

<div wire:poll.{{ $pollRate }}>
    @if ($import)
        <x-filament::modal
            id="import-progress"
            :icon="$isCompleted ? 'tabler-file-check' : 'tabler-cloud-upload'"
            :icon-color="$isCompleted ? 'success' : 'primary'"
            :close-button="false"
            :close-by-clicking-away="false"
            :close-by-escaping="false"
            width="lg"
        >
            <x-slot name="heading">{{ $isCompleted ? 'Import complete' : 'Importing taxa…' }}</x-slot>

            <x-slot name="description">
                @if ($isRunning)
                    Importing{{ $import->file_name ? ' '.$import->file_name : '' }} — this runs in the background.
                @elseif ($isCompleted)
                    Your taxon import has finished.
                @endif
            </x-slot>

            @if ($isRunning)
                <div class="space-y-4">
                    <div
                        class="pb-track"
                        data-pb-size="md"
                        data-pb-shape="pill"
                        data-pb-color="primary"
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

                    <div class="text-primary-600 dark:text-primary-400 text-sm font-medium">
                        {{ $processed }} of {{ $total }} {{ Str::plural('row', $total) }} processed
                    </div>
                </div>
            @elseif ($isCompleted)
                <div class="space-y-4">
                    <div class="grid grid-cols-3 gap-3">
                        <div class="bg-success-50 dark:bg-success-950/30 p-3 text-center">
                            <p class="text-success-600 dark:text-success-400 text-2xl font-bold">
                                {{ $successful }}
                            </p>
                            <p class="text-success-600/70 dark:text-success-400/70 text-xs">
                                {{ Str::plural('Row', $successful) }} imported
                            </p>
                        </div>
                        <div @class([
                            'p-3 text-center',
                            'bg-warning-50 dark:bg-warning-950/30' => $skipped > 0,
                            'bg-gray-50 dark:bg-gray-800' => $skipped === 0,
                        ])>
                            <p @class([
                                'text-2xl font-bold',
                                'text-warning-600 dark:text-warning-400' => $skipped > 0,
                                'text-gray-400 dark:text-gray-500' => $skipped === 0,
                            ])>
                                {{ $skipped }}
                            </p>
                            <p @class([
                                'text-xs',
                                'text-warning-600/70 dark:text-warning-400/70' => $skipped > 0,
                                'text-gray-500 dark:text-gray-400/70' => $skipped === 0,
                            ])>
                                Already in database
                            </p>
                        </div>
                        <div @class([
                            'p-3 text-center',
                            'bg-danger-50 dark:bg-danger-950/30' => $failed > 0,
                            'bg-gray-50 dark:bg-gray-800' => $failed === 0,
                        ])>
                            <p @class([
                                'text-2xl font-bold',
                                'text-danger-600 dark:text-danger-400' => $failed > 0,
                                'text-gray-400 dark:text-gray-500' => $failed === 0,
                            ])>
                                {{ $failed }}
                            </p>
                            <p @class([
                                'text-xs',
                                'text-danger-600/70 dark:text-danger-400/70' => $failed > 0,
                                'text-gray-500 dark:text-gray-400/70' => $failed === 0,
                            ])>
                                {{ Str::plural('Row', $failed) }} failed
                            </p>
                        </div>
                    </div>

                    @if ($skipped > 0)
                        <p class="text-sm text-gray-500 dark:text-gray-400">
                            {{ $skipped }} {{ Str::plural('species', $skipped) }}
                            {{ $skipped === 1 ? 'was' : 'were' }} not imported because the scientific name is
                            already in the database.
                        </p>
                    @endif

                    @if ($notImported > 0)
                        <div class="flex flex-wrap items-center gap-1.5 text-sm">
                            <x-tabler-file-spreadsheet class="text-primary-600 dark:text-primary-400 h-4 w-4" />
                            <span class="text-gray-500 dark:text-gray-400">Download not imported species</span>
                            <a
                                href="{{ route('imports.not-imported-rows.download', ['import' => $import, 'format' => 'xlsx']) }}"
                                target="_blank"
                                class="text-primary-600 dark:text-primary-400 font-medium hover:underline"
                            >
                                Excel
                            </a>
                            <span class="text-gray-400 dark:text-gray-500">/</span>
                            <a
                                href="{{ route('imports.not-imported-rows.download', ['import' => $import, 'format' => 'csv']) }}"
                                target="_blank"
                                class="text-primary-600 dark:text-primary-400 font-medium hover:underline"
                            >
                                CSV
                            </a>
                        </div>
                    @endif
                </div>
            @endif

            <x-slot name="footerActions">
                @if ($isCompleted)
                    <x-filament::button wire:click="dismiss" color="success"> Done </x-filament::button>
                @endif
            </x-slot>
        </x-filament::modal>
    @endif
</div>
