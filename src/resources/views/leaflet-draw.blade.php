@php
    $current_value = old_empty_or_null($field['name'], '') ?? $field['default'] ?? '';

    $mapProvider = $field['options']['provider'] ?? 'mapbox';
    $zoomLevel = 14;

    $mapId = $field['name'];

    $mapMarker = $field['options']['marker_image'] ?? null;

    $entryInstance = null;

    $routeIs = $field['ajax-route']; // route('store-polygon');

    $latMarker = 53.8965741;
    $lngMarker = 27.547158;

@endphp

@include('crud::fields.inc.wrapper_start')
@include('crud::fields.inc.translatable_icon')

<div class="mapfield">
    <div id="{{ $mapId }}"></div>
    <div class='pointer'></div>

    {{-- HINT --}}
    @if (isset($field['hint']))
        <p class="help-block">{!! $field['hint'] !!}</p>
    @endif
</div>
@include('crud::fields.inc.wrapper_end')

@push('crud_fields_styles')

    @basset('https://unpkg.com/leaflet@1.9.4/dist/leaflet.css')
    @basset('https://cdnjs.cloudflare.com/ajax/libs/leaflet.draw/1.0.4/leaflet.draw.css')
    @basset('https://cdn-geoweb.s3.amazonaws.com/esri-leaflet-geocoder/0.0.1-beta.5/esri-leaflet-geocoder.css')
    @bassetBlock('backpack/fields/basset-draw-field.css')
    <style>
        #{{ $mapId }}
        {
            width: 100%;
            height: 300px;
            z-index: 100;
        }

        #mapSearchContainer {
            position: fixed;
            top: 20px;
            right: 40px;
            height: 30px;
            width: 190px;
            z-index: 110;
            font-size: 12pt;
            color: #5d5d5d;
            border: solid 1px #bbb;
            background-color: #f8f8f8;
        }

        .pointer {
            position: absolute;
            top: 86px;
            left: 60px;
            z-index: 99999;
        }
    </style>
    @endBassetBlock
@endpush

@push('crud_fields_scripts')

    @basset('https://unpkg.com/leaflet@1.9.4/dist/leaflet.js')
    @basset('https://cdnjs.cloudflare.com/ajax/libs/leaflet.draw/1.0.4/leaflet.draw.js')
    @basset('https://cdn-geoweb.s3.amazonaws.com/esri-leaflet/0.0.1-beta.5/esri-leaflet.js')
    @basset('https://cdn-geoweb.s3.amazonaws.com/esri-leaflet-geocoder/0.0.1-beta.5/esri-leaflet-geocoder.js')

    @bassetBlock('backpack/fields/basset-draw-field.js')
    <script>
        let mapId = '{{ $mapId }}',
            defaultZoom = '{{ $zoomLevel }}',
            defaultLng = '{{ $lngMarker }}',
            defaultLat = '{{ $latMarker }}',
            url = 'https://api.mapbox.com/styles/v1/{id}/tiles/{z}/{x}/{y}?access_token={accessToken}';

        let map = L.map(mapId, {
            scrollWheelZoom: false
        }).setView([defaultLng, defaultLat], defaultZoom);

        let results = new L.LayerGroup().addTo(map);

        L.tileLayer(url, {
            maxZoom: 18,
            attribution: 'Map data &copy; <a href="https://www.openstreetmap.org/copyright">OpenStreetMap</a> contributors, Imagery © <a href="https://www.mapbox.com/">Mapbox</a>',
            id: 'mapbox/streets-v11',
            tileSize: 512,
            zoomOffset: -1,
            accessToken: '{{config('backpack.leaflet-draw.mapbox.access_token', null)}}'
        }).addTo(map);

        let searchControl = new L.esri.Controls.Geosearch().addTo(map);

        searchControl.on('results', function(data) {
            results.clearLayers();
            for (var i = data.results.length - 1; i >= 0; i--) {
                results.addLayer(L.marker(data.results[i].latlng));
            }
        });

        // Initialize the Leaflet Draw feature
        var drawnItems = new L.FeatureGroup();
        map.addLayer(drawnItems);

        var drawControl = new L.Control.Draw({
            edit: {
                featureGroup: drawnItems
            },
            draw: {
                polygon: true,
                marker: false,  // disable other shapes
                circle: false,
                rectangle: false,
                polyline: false
            }
        });

        map.addControl(drawControl);

        // Handle the 'draw:created' event
        map.on('draw:created', function (e) {
            var type = e.layerType;
            var layer = e.layer;

            if (type === 'polygon') {
                var polygonData = layer.toGeoJSON(); // Convert drawn polygon to GeoJSON
                console.log(JSON.stringify(polygonData));

                // Optionally, add the drawn polygon to the map
                drawnItems.addLayer(layer);

                // Send polygon data to the server
                fetch('{{ $routeIs }}', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': '{{ csrf_token() }}'  // For Laravel CSRF protection
                    },
                    body: JSON.stringify({
                        polygon: polygonData
                    })
                }).then(response => response.json())
                    .then(data => {
                        alert("Polygon stored!");
                    }).catch(error => {
                    console.error('Error:', error);
                });
            }
        });
    </script>
    @endLoadOnce

@endpush
