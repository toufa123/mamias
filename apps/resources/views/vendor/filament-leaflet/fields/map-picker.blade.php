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
        style="height: {{ $config['mapHeight'] }}px; width: 100%"
    >
        <div id="{{ $config['mapId'] }}" style="height:100%;width:100%;"></div>

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
