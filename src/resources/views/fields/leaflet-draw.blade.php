{{-- Leaflet Draw polygon field, stores the drawn shape(s) as GeoJSON --}}
@php
    $value = old_empty_or_null($field['name'], '') ?? $field['value'] ?? $field['default'] ?? '';

    // the value may come as a JSON string, or already decoded by a model cast / accessor
    if (! is_string($value)) {
        $value = json_encode($value);
    }

    $options = $field['options'] ?? [];
    $provider = $options['provider'] ?? config('backpack.leaflet-draw.provider', 'mapbox');
    $accessToken = $options['access_token'] ?? config('backpack.leaflet-draw.mapbox.access_token');

    if ($provider === 'mapbox' && empty($accessToken)) {
        $provider = 'openstreetmap';
    }

    $mapConfig = [
        'provider' => $provider,
        'accessToken' => $provider === 'mapbox' ? $accessToken : null,
        'style' => $options['style'] ?? config('backpack.leaflet-draw.mapbox.style', 'mapbox/streets-v12'),
        'lat' => (float) ($options['lat'] ?? config('backpack.leaflet-draw.default.lat')),
        'lng' => (float) ($options['lng'] ?? config('backpack.leaflet-draw.default.lng')),
        'zoom' => (int) ($options['zoom'] ?? config('backpack.leaflet-draw.default.zoom', 12)),
        'multiple' => (bool) ($options['multiple'] ?? false),
        'scrollWheelZoom' => (bool) ($options['scroll_wheel_zoom'] ?? false),
        'color' => $options['color'] ?? '#3388ff',
    ];

    $height = $options['height'] ?? config('backpack.leaflet-draw.default.height', '300px');
@endphp

@include('crud::fields.inc.wrapper_start')
    <label>{!! $field['label'] !!}</label>
    @include('crud::fields.inc.translatable_icon')

    <div class="leaflet-draw-field" data-leaflet-draw-field>
        <input
            type="hidden"
            name="{{ $field['name'] }}"
            value="{{ $value }}"
            data-init-function="bpFieldInitLeafletDrawElement"
            @include('crud::fields.inc.attributes')
        />
        <div
            class="leaflet-draw-map"
            data-leaflet-draw-map
            data-config='@json($mapConfig)'
            style="height: {{ $height }};"
        ></div>
    </div>

    {{-- HINT --}}
    @if (isset($field['hint']))
        <p class="help-block">{!! $field['hint'] !!}</p>
    @endif
@include('crud::fields.inc.wrapper_end')

@push('crud_fields_styles')
    @basset('https://cdnjs.cloudflare.com/ajax/libs/leaflet/1.9.4/leaflet.min.css')
    @basset('https://cdnjs.cloudflare.com/ajax/libs/leaflet.draw/1.0.4/leaflet.draw.css')
    @bassetBlock('siberfx/leaflet-draw/leaflet-draw-field.css')
    <style>
        .leaflet-draw-map {
            position: relative;
            width: 100%;
            z-index: 0;
            border-radius: var(--tblr-border-radius, 4px);
        }

        /* basset internalizes the stylesheet, so point the toolbar sprites back to the CDN */
        .leaflet-draw-map .leaflet-draw-toolbar a {
            background-image: url('https://cdnjs.cloudflare.com/ajax/libs/leaflet.draw/1.0.4/images/spritesheet.png');
            background-image: linear-gradient(transparent, transparent), url('https://cdnjs.cloudflare.com/ajax/libs/leaflet.draw/1.0.4/images/spritesheet.svg');
        }

        .leaflet-retina .leaflet-draw-map .leaflet-draw-toolbar a {
            background-image: url('https://cdnjs.cloudflare.com/ajax/libs/leaflet.draw/1.0.4/images/spritesheet-2x.png');
            background-image: linear-gradient(transparent, transparent), url('https://cdnjs.cloudflare.com/ajax/libs/leaflet.draw/1.0.4/images/spritesheet.svg');
        }
    </style>
    @endBassetBlock
@endpush

@push('crud_fields_scripts')
    @basset('https://cdnjs.cloudflare.com/ajax/libs/leaflet/1.9.4/leaflet.min.js')
    @basset('https://cdnjs.cloudflare.com/ajax/libs/leaflet.draw/1.0.4/leaflet.draw.js')
    @bassetBlock('siberfx/leaflet-draw/leaflet-draw-field.js')
    <script>
        function bpFieldInitLeafletDrawElement(element) {
            const input = element[0];
            const mapElement = input.closest('[data-leaflet-draw-field]').querySelector('[data-leaflet-draw-map]');

            if (mapElement._leafletDrawMap) {
                return;
            }

            const config = JSON.parse(mapElement.dataset.config);

            L.Icon.Default.imagePath = 'https://cdnjs.cloudflare.com/ajax/libs/leaflet/1.9.4/images/';

            const map = L.map(mapElement, {
                scrollWheelZoom: config.scrollWheelZoom,
            }).setView([config.lat, config.lng], config.zoom);

            mapElement._leafletDrawMap = map;

            if (config.provider === 'mapbox') {
                L.tileLayer('https://api.mapbox.com/styles/v1/{id}/tiles/{z}/{x}/{y}?access_token={accessToken}', {
                    maxZoom: 18,
                    attribution: '&copy; <a href="https://www.openstreetmap.org/copyright">OpenStreetMap</a> contributors, Imagery &copy; <a href="https://www.mapbox.com/">Mapbox</a>',
                    id: config.style,
                    tileSize: 512,
                    zoomOffset: -1,
                    accessToken: config.accessToken,
                }).addTo(map);
            } else {
                L.tileLayer('https://tile.openstreetmap.org/{z}/{x}/{y}.png', {
                    maxZoom: 19,
                    attribution: '&copy; <a href="https://www.openstreetmap.org/copyright">OpenStreetMap</a> contributors',
                }).addTo(map);
            }

            const shapeOptions = { color: config.color };
            const drawnItems = new L.FeatureGroup();
            map.addLayer(drawnItems);

            // load the stored GeoJSON (Feature, FeatureCollection or Geometry) into the editable layer
            let stored = null;
            try {
                stored = input.value ? JSON.parse(input.value) : null;
            } catch (e) {
                console.warn('leaflet-draw: stored value is not valid GeoJSON', e);
            }

            if (stored) {
                L.geoJSON(stored, { style: shapeOptions }).eachLayer(layer => drawnItems.addLayer(layer));

                if (drawnItems.getLayers().length) {
                    map.fitBounds(drawnItems.getBounds());
                }
            }

            map.addControl(new L.Control.Draw({
                edit: {
                    featureGroup: drawnItems,
                    remove: true,
                },
                draw: {
                    polygon: { allowIntersection: false, showArea: true, shapeOptions: shapeOptions },
                    rectangle: false,
                    circle: false,
                    circlemarker: false,
                    marker: false,
                    polyline: false,
                },
            }));

            const sync = () => {
                const features = drawnItems.toGeoJSON().features;
                let value = '';

                if (features.length) {
                    value = JSON.stringify(config.multiple ? { type: 'FeatureCollection', features: features } : features[0]);
                }

                input.value = value;
                element.trigger('change');
            };

            map.on(L.Draw.Event.CREATED, function (e) {
                if (!config.multiple) {
                    drawnItems.clearLayers();
                }

                drawnItems.addLayer(e.layer);
                sync();
            });

            map.on(L.Draw.Event.EDITED, sync);
            map.on(L.Draw.Event.DELETED, sync);

            // maps rendered inside hidden tabs or modals need a resize once they become visible
            if (window.ResizeObserver) {
                new ResizeObserver(() => map.invalidateSize()).observe(mapElement);
            }
        }
    </script>
    @endBassetBlock
@endpush
