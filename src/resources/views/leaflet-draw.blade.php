@php
    $value = old_empty_or_null($field['name'], '') ?? $field['value'] ?? $field['default'] ?? '';

    $mapProvider = $field['options']['provider'] ?? 'mapbox';
    $zoomLevel = 12;

    $mapId = $field['name'];

    $mapMarker = $field['options']['marker_image'] ?? null;

    $entryInstance = null;

    [$lngMarker, $latMarker] = [30.7028187, 36.9667757];


@endphp

@include('crud::fields.inc.wrapper_start')
@include('crud::fields.inc.translatable_icon')

<div class="mapfield">
    <div id="{{ $mapId }}"></div>
    <div class='pointer'></div>
    <input type="hidden" name="coordinates" id="polygonInput">

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
        let polygonData = @json($value),  // Assuming $polygon is the GeoJSON stored data
            mapId = '{{ $mapId }}',
            defaultZoom = '{{ $zoomLevel }}',
            defaultLat = '{{ $latMarker }}',
            defaultLng = '{{ $lngMarker }}',
            url = 'https://api.mapbox.com/styles/v1/{id}/tiles/{z}/{x}/{y}?access_token={accessToken}';

        let map = L.map(mapId, {
            scrollWheelZoom: false
        }).setView([defaultLat, defaultLng], defaultZoom);

        // Add tile layer from Mapbox
        L.tileLayer(url, {
            maxZoom: 18,
            attribution: 'Map data &copy; <a href="https://www.openstreetmap.org/copyright">OpenStreetMap</a> contributors, Imagery © <a href="https://www.mapbox.com/">Mapbox</a>',
            id: 'mapbox/streets-v11',
            tileSize: 512,
            zoomOffset: -1,
            accessToken: '{{ config('backpack.leaflet-draw.mapbox.access_token', null) }}'
        }).addTo(map);

        // Retrieve polygon data from your database (passed from backend as JSON)

        // Parse and add the polygon to the map (FLIP the coordinates)
        if (polygonData && polygonData.geometry && polygonData.geometry.type === "Polygon") {
            let coordinates = polygonData.geometry.coordinates[0].map(coord => [coord[1], coord[0]]);  // Flip coordinates to [lat, lng]

            // Create polygon and add it to the map
            let polygonLayer = L.polygon(coordinates).addTo(map);

            document.getElementById('polygonInput').value = JSON.stringify(polygonData);

            // Automatically adjust the map view to fit the polygon
            map.fitBounds(polygonLayer.getBounds());
        }

        // Initialize Leaflet Draw for manual polygon drawing
        let drawnItems = new L.FeatureGroup();
        map.addLayer(drawnItems);

        var drawControl = new L.Control.Draw({
            edit: {
                featureGroup: drawnItems, // Use the drawnItems group for editing
                remove: true // Enable deletion
            },
            draw: {
                polygon: true,
                marker: false,
                circle: false,
                rectangle: false,
                polyline: false
            }
        });
        map.addControl(drawControl);

        // Handle the 'draw:created' event for drawing new polygons
        map.on('draw:created', function (e) {
            var type = e.layerType;
            var layer = e.layer;

            if (type === 'polygon') {
                var polygonData = layer.toGeoJSON();  // Convert drawn polygon to GeoJSON
                console.log(JSON.stringify(polygonData));  // Log GeoJSON to console (you can send it to the server)

                // Optionally, add the drawn polygon to the map
                drawnItems.addLayer(layer);

                // Store polygon data into a hidden input to submit to backend
                document.getElementById('polygonInput').value = JSON.stringify(polygonData);
            }
        });

        // Handle 'draw:edited' event
        map.on('draw:edited', function (e) {
            e.layers.eachLayer(function (layer) {
                var polygonData = layer.toGeoJSON();
                document.getElementById('polygonInput').value = JSON.stringify(polygonData);  // Update the hidden input with new polygon data
            });
        });

        // Handle 'draw:deleted' event
        map.on('draw:deleted', function (e) {
            document.getElementById('polygonInput').value = '';  // Clear the hidden input when the polygon is deleted
        });
    </script>
    @endBassetBlock
@endpush
