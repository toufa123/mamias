<div class="space-y-6">
    @section ('title', 'Map')

    @section ('breadcrumbs')
        {{ Breadcrumbs::render('map') }}
    @endsection

    @php
        $table = $this->getTable();
        $isCountries = $this->isCountryLayer();
        $selectedCount = $this->selectedCount();
        $areaLabel = $this->selectedAreaLabel();
        $establishmentTotal = max(1, array_sum(array_column($this->establishment, 'count')));
        $topPhylum = max([1, ...array_values($this->phyla)]);
    @endphp

    <div class="print-hidden flex justify-end">{{ $this->guideAction }}</div>

    {{-- On paper the filter form gives way to one line saying what the map shows (app.css, print). --}}
    <p class="print-only print-scope">
        <b>Layer</b> {{ $isCountries ? 'Countries of first record' : 'EcAp subregions' }}
        <b>Selected</b> {{ $isCountries ? $areaLabel : $this->selectedSubregion()->getLabel() }}
        <b>Filters</b>
        {{ collect($table->getFilterIndicators())->map(fn ($indicator): string => strip_tags((string) $indicator->getLabel()))->join(' · ') ?: 'none' }}
        <b>Occurrences</b> {{ $this->pins ? count($this->occurrenceIds) . ' pinned' : 'not shown' }}
    </p>

    <x-block heading="Filters" icon="tabler-filter" class="print-hidden">
        <x-slot name="afterHeader">
            {{ $table->getFiltersResetAction()->defaultView($table->getFiltersResetAction()::LINK_VIEW) }}
        </x-slot>

        <div class="[&>.fi-grid]:items-center px-3">{{ $this->getTableFiltersForm() }}</div>
    </x-block>

    {{-- Full width: the basin needs ~970px to fit at zoom 4 in the basemap's EPSG:4326. --}}
    <x-block
        :compact="false"
        :heading="$isCountries ? 'Countries of first record' : 'EcAp subregions'"
        :description="$isCountries
            ? 'Bubbles sized by the number of species first recorded in each country. Click one to see its species.'
            : 'Shaded by the number of species the filters keep. Click a subregion to see its species.'"
        icon="tabler-map"
    >
        <x-slot name="afterHeader">
            <div class="flex flex-wrap items-center gap-4">
                <x-filament::tabs contained>
                    <x-filament::tabs.item :active="! $isCountries" wire:click="showLayer('subregions')">
                        Subregions
                    </x-filament::tabs.item>
                    <x-filament::tabs.item :active="$isCountries" wire:click="showLayer('countries')">
                        Countries
                    </x-filament::tabs.item>
                </x-filament::tabs>

                <label class="flex items-center gap-2 text-sm">
                    <x-filament::input.checkbox wire:model.live="pins" />
                    Occurrences
                </label>
            </div>
        </x-slot>

        <div class="space-y-3">
            <div class="overflow-hidden rounded-lg">
                @livewire (\App\Livewire\NisAreaMap::class, [
                    'layer' => $this->layer,
                    'counts' => $this->counts,
                    'countryCounts' => $this->countryCounts,
                    'selected' => $this->selectedKey(),
                    'occurrenceIds' => $this->occurrenceIds,
                ], key('nis-area-map'))
            </div>

            <div class="flex flex-wrap items-center justify-between gap-3 text-xs text-gray-500">
                <div class="flex flex-wrap items-center gap-x-4 gap-y-1">
                    @if ($isCountries)
                        <span class="flex items-center gap-2">
                            <span
                                class="size-2.5 rounded-full"
                                style="background: {{ \App\Livewire\NisAreaMap::SCALE[3] }}"
                            ></span>
                            <span
                                class="size-4 rounded-full"
                                style="background: {{ \App\Livewire\NisAreaMap::SCALE[3] }}"
                            ></span>
                            Larger bubble, more species first recorded there
                        </span>
                    @else
                        <span class="flex items-center gap-2">
                            Fewer species
                            <span class="flex overflow-hidden rounded-sm">
                                @foreach (\App\Livewire\NisAreaMap::SCALE as $shade)
                                    <span class="size-3.5" style="background: {{ $shade }}"></span>
                                @endforeach
                            </span>
                            More
                        </span>
                    @endif
                    @if ($this->pins)
                        <span class="flex items-center gap-1.5">
                            <x-filament::icon icon="tabler-map-pin-filled" class="size-4 text-red-600" />
                            {{ trans_choice(':count approved occurrence|:count approved occurrences', count($this->occurrenceIds)) }}
                        </span>
                    @endif
                </div>
                <span> © UNEP/MAP </span>
            </div>
        </div>
    </x-block>

    <x-block heading="Summary" icon="tabler-chart-bar">
        <div class="grid gap-x-6 gap-y-4 md:grid-cols-2 xl:grid-cols-4">
            <div class="space-y-1">
                <p class="text-xs tracking-wider text-gray-500 uppercase">
                    {{ $isCountries ? 'Country of first record' : 'EcAp subregion' }}
                </p>
                <h2 class="text-lg font-semibold">
                    {{ $isCountries ? $areaLabel : $this->selectedSubregion()->getLabel() }}
                </h2>
                <p class="text-sm text-gray-600 dark:text-gray-400">
                    <span class="text-primary-700 dark:text-primary-400 text-2xl font-semibold tabular-nums">
                        {{ number_format($selectedCount) }}
                    </span>
                    {{ $isCountries ? 'species first recorded here' : 'species recorded' }}
                </p>
                @if ($selectedCount)
                    {{-- Full URL keeps the active filters when the link is opened in a new tab. --}}
                    <x-filament::link
                        :href="route('map', ['layer' => $this->layer, 'subregion' => $this->subregion, 'country' => $this->country]) . '#species'"
                        x-on:click.prevent="
                            $dispatch('expand-section', { id: 'species' });
                            $nextTick(() => document.getElementById('species').scrollIntoView({ behavior: 'smooth' }));
                        "
                        icon="tabler-list"
                        class="pt-1"
                    >
                        View these species
                    </x-filament::link>
                @endif
            </div>

            @if ($selectedCount)
                <div class="space-y-1.5">
                    <h3 class="text-sm font-semibold">Establishment</h3>
                    <div class="flex h-3 overflow-hidden rounded">
                        @foreach ($this->establishment as $status)
                            <span
                                style="width: {{ ($status['count'] / $establishmentTotal) * 100 }}%; background: {{ $status['color'] }}"
                            ></span>
                        @endforeach
                    </div>
                    <ul class="space-y-0.5 text-sm">
                        @foreach ($this->establishment as $status)
                            <li class="flex items-center gap-2">
                                <span class="size-2.5 rounded-sm" style="background: {{ $status['color'] }}"></span>
                                <span class="grow">{{ $status['label'] }}</span>
                                <span class="tabular-nums">{{ $status['count'] }}</span>
                            </li>
                        @endforeach
                    </ul>
                </div>

                <div class="space-y-1.5">
                    <h3 class="text-sm font-semibold">Top phyla</h3>
                    <ul class="space-y-1 text-sm">
                        @foreach ($this->phyla as $phylum => $count)
                            <li class="grid grid-cols-[7rem_minmax(0,1fr)_2.5rem] items-center gap-2">
                                <span class="truncate">{{ $phylum }}</span>
                                <span class="h-2 rounded bg-gray-100 dark:bg-white/10">
                                    <span
                                        class="bg-primary-600 block h-2 rounded"
                                        style="width: {{ ($count / $topPhylum) * 100 }}%"
                                    ></span>
                                </span>
                                <span class="text-right tabular-nums">{{ $count }}</span>
                            </li>
                        @endforeach
                    </ul>
                </div>

                <div class="space-y-1.5">
                    <h3 class="text-sm font-semibold">Most recent arrivals</h3>
                    <ul class="divide-y divide-gray-100 text-sm dark:divide-white/10">
                        @foreach ($this->latestArrivals as $arrival)
                            <li>
                                <a
                                    href="{{ route('data.species', $arrival['event']) }}"
                                    class="hover:text-primary-600 flex justify-between gap-3 py-1"
                                >
                                    <em class="truncate">{{ $arrival['event']->taxon->scientificname }}</em>
                                    <span class="text-gray-500 tabular-nums">{{ $arrival['year'] }}</span>
                                </a>
                            </li>
                        @endforeach
                    </ul>
                </div>

            @else
                <p class="text-sm text-gray-500">No species match the filters {{ $isCountries ? 'in any country' : 'in this subregion' }}.</p>
            @endif
        </div>
    </x-block>

    <x-block
        id="species"
        table
        :heading="($isCountries ? 'Species first recorded in ' : 'Species recorded in ') . $areaLabel"
        icon="tabler-list"
    >
        {{ $this->table }}

        <p class="print-only px-4 py-2 text-xs text-gray-500">The rows shown on screen. Full list: www.mamias.org/pages/data</p>
    </x-block>
</div>
