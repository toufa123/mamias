@php
    use Illuminate\Support\Js;
    $config = $getMapData();
    $mapClass = 'leafletMapField';
@endphp

<x-dynamic-component
    :component="$getFieldWrapperView()"
    :field="$field"
>
    <div
        wire:ignore
        x-data="{{ $mapClass }}(
            $wire,
            {{ Js::from($config) }},
        )"
        x-on:x-modal-opened.window="
            $nextTick(() => {
                mapCore?.map?.invalidateSize();
                setTimeout(() => mapCore?.map?.invalidateSize(), 100);
                setTimeout(() => mapCore?.map?.invalidateSize(), 300);
                setTimeout(() => mapCore?.map?.invalidateSize(), 800);
            });
        "
        style="height: {{ $config['mapHeight'] }}px; width: 100%; min-height: {{ $config['mapHeight'] }}px;"
    >
        <div id="{{ $config['mapId'] }}" style="height: {{ $config['mapHeight'] }}px; width: 100%;"></div>

        @push('styles')
            <style>
                {!! $config['customStyles'] !!}
                /* Fix Leaflet 1.9 gray tile issue — mix-blend-mode makes tiles invisible on many browsers */
                .leaflet-container img.leaflet-tile {
                    mix-blend-mode: normal !important;
                }
            </style>
        @endpush

        @push('scripts')
            <script>
                {!! $config['customScripts'] !!}
            </script>
        @endpush
    </div>
</x-dynamic-component>
