{{-- The countries layer's bubbles are drawn over this map by resources/js/nis-area-map.js. --}}
<div data-nis-bubbles="{{ json_encode($this->bubbles()) }}">
    @vite ('resources/js/nis-area-map.js')

    <x-filament-leaflet::map :config="$this->getMapData()" widget />
</div>
