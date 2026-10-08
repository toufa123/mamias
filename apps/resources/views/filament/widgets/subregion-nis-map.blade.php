{{-- fi-wi-chart: same card treatment as the ECharts widgets beside it (theme.css). --}}
<x-filament-widgets::widget class="fi-wi-chart" x-data="{ ready: false }">
    <x-filament::section heading="Reported NIS by EcAp sub-region" collapsible>
        <x-slot name="afterHeader">
            <x-filament::icon-button
                icon="heroicon-o-arrow-down-tray"
                label="Download PNG"
                tooltip="Download PNG"
                color="gray"
                size="sm"
                x-bind:disabled="! ready"
                x-on:click.stop="window.downloadSubregionMap($refs.map, {{ \Illuminate\Support\Js::from($values) }}, 'Reported NIS by EcAp sub-region')"
            />
        </x-slot>

        {{-- Height on the wrapper: jsvectormap's unlayered .jvm-container { height: 100% } beats a utility on the map element itself. --}}
        <div class="h-[278px] w-full">
            {{-- x-intersect, not x-init: the dashboard tab may be hidden at load, and a 0×0 container makes jsvectormap's focus scale NaN. --}}
            <div
                wire:ignore
                x-ref="map"
                x-intersect.once="await import(@js($script)); await window.renderSubregionMap($el, @js($values), @js($labels)); ready = true"
            ></div>
        </div>
    </x-filament::section>
</x-filament-widgets::widget>
