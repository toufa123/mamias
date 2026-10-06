<?php

return [
    'columns' => [
        'latitude' => 'lat',
        'longitude' => 'lng',
        'coords' => 'location',
        'radius' => 'radius',
        'title' => 'title',
        'description' => 'description',
        'bounds' => 'bounds',
        'points' => 'points',
    ],

    'sync_record_attributes' => true,
    // Every map opens on the Mediterranean unless it sets its own centre.
    'default_map_center' => [36, 15],

    // UNEP/MAP INFO/RAC basemap, applied to every Leaflet map in AppServiceProvider.
    // It is cached in EPSG:4326, so resources/js/app.js switches Leaflet's CRS to
    // match; one zoom level there shows what the next one up did in web mercator.
    // Tiles exist to level 10 (about 75 m per pixel) and 404 beyond it.
    'basemap' => [
        'label' => 'UNEP/MAP',
        'url' => env('BASEMAP_TILE_URL', 'https://maps.info-rac.org/arcgis/rest/services/Hosted/basemap_inforac/MapServer/tile/{z}/{y}/{x}'),
        'max_zoom' => 10,
    ],
];
