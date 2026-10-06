{{-- The package's map-widget view, in a section that folds away and remembers it. --}}
<x-filament-widgets::widget>
    <x-filament::section collapsible persist-collapsed id="occurrences-map" icon="tabler-map-2">
        <x-slot name="heading">
            {{ $this->getHeading() }}
        </x-slot>

        {{-- Leaflet only re-measures on a window resize; a map first drawn folded has no size. --}}
        <div x-data x-init="new ResizeObserver(() => window.dispatchEvent(new Event('resize'))).observe($el)">
            <x-filament-leaflet::map :config="$this->getMapData()" widget />
        </div>
    </x-filament::section>

    <x-filament-actions::modals />
</x-filament-widgets::widget>
