<?php

return [
    /*
    | Tile provider used when the field does not set `options.provider`.
    | Supported: "mapbox", "openstreetmap".
    | When "mapbox" is selected but no access token is set, the field falls back to OpenStreetMap.
    */
    'provider' => env('LEAFLET_DRAW_PROVIDER', 'mapbox'),

    'mapbox' => [
        'access_token' => env('MAPS_MAPBOX_ACCESS_TOKEN'),
        'style' => env('MAPS_MAPBOX_STYLE', 'mapbox/streets-v12'),
    ],

    /*
    | Default map view when the field has no stored polygon yet.
    */
    'default' => [
        'lat' => 36.9667757,
        'lng' => 30.7028187,
        'zoom' => 12,
        'height' => '300px',
    ],
];
