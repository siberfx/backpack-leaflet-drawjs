import { test, beforeEach } from 'node:test';
import assert from 'node:assert/strict';
import { readFileSync } from 'node:fs';
import { createRequire } from 'node:module';
import { fileURLToPath } from 'node:url';
import { JSDOM } from 'jsdom';

const require = createRequire(import.meta.url);
const root = fileURLToPath(new URL('../../', import.meta.url));

const read = path => readFileSync(path, 'utf8');
const vendor = {
    jquery: read(require.resolve('jquery/dist/jquery.min.js')),
    leaflet: read(require.resolve('leaflet/dist/leaflet-src.js')),
    draw: read(require.resolve('leaflet-draw/dist/leaflet.draw-src.js')),
};

// the field script, extracted from the Blade view so the shipped code is what gets tested
const blade = read(root + 'src/resources/views/fields/leaflet-draw.blade.php');
const fieldScript = blade.match(/leaflet-draw-field\.js'\)\s*<script>([\s\S]*?)<\/script>/)[1];

const defaultConfig = {
    provider: 'openstreetmap',
    accessToken: null,
    style: 'mapbox/streets-v12',
    lat: 36.9667757,
    lng: 30.7028187,
    zoom: 12,
    multiple: false,
    scrollWheelZoom: false,
    color: '#3388ff',
};

const feature = (offset = 0) => ({
    type: 'Feature',
    properties: {},
    geometry: {
        type: 'Polygon',
        coordinates: [[[30.59 + offset, 36.83], [30.61 + offset, 36.89], [30.66 + offset, 36.9], [30.59 + offset, 36.83]]],
    },
});

let window;

beforeEach(() => {
    const dom = new JSDOM('<!doctype html><html><body></body></html>', { runScripts: 'outside-only', pretendToBeVisual: true });
    window = dom.window;
    window.eval(vendor.jquery);
    window.eval(vendor.leaflet);
    window.eval(vendor.draw);
    window.eval(fieldScript);
});

/**
 * Render the field markup the Blade view produces and run Backpack's init function on it.
 */
function mount({ name = 'coordinates', value = '', config = {} } = {}) {
    const wrapper = window.document.createElement('div');
    wrapper.innerHTML = `
        <div class="leaflet-draw-field" data-leaflet-draw-field>
            <input type="hidden" name="${name}" data-init-function="bpFieldInitLeafletDrawElement">
            <div class="leaflet-draw-map" data-leaflet-draw-map style="height: 300px;"></div>
        </div>`;
    window.document.body.appendChild(wrapper);

    const input = wrapper.querySelector('input');
    input.value = value;
    const mapElement = wrapper.querySelector('[data-leaflet-draw-map]');
    mapElement.dataset.config = JSON.stringify({ ...defaultConfig, ...config });

    const element = window.jQuery(input);
    const changes = [];
    element.on('change', () => changes.push(input.value));

    window.bpFieldInitLeafletDrawElement(element);

    const map = mapElement._leafletDrawMap;
    const layers = type => {
        const found = [];
        map.eachLayer(layer => layer instanceof type && found.push(layer));
        return found;
    };
    const drawnItems = layers(window.L.FeatureGroup).find(group => !(group instanceof window.L.GeoJSON));

    return { input, element, mapElement, map, drawnItems, layers, changes };
}

const drawPolygon = (map, latlngs) => {
    const layer = window.L.polygon(latlngs);
    map.fire(window.L.Draw.Event.CREATED, { layer, layerType: 'polygon' });
    return layer;
};

const triangle = offset => [[36.9 + offset, 30.6], [36.92 + offset, 30.65], [36.88 + offset, 30.64]];

test('initialises a Leaflet map with the draw toolbar', () => {
    const { map, mapElement } = mount();

    assert.ok(map instanceof window.L.Map);
    assert.ok(mapElement.classList.contains('leaflet-container'));
    assert.ok(mapElement.querySelector('.leaflet-draw-draw-polygon'), 'polygon tool is present');
    assert.equal(mapElement.querySelector('.leaflet-draw-draw-rectangle'), null, 'other shapes are disabled');
    assert.ok(mapElement.querySelector('.leaflet-draw-edit-edit'), 'edit tool is present');
    assert.ok(mapElement.querySelector('.leaflet-draw-edit-remove'), 'remove tool is present');
});

test('centers on the configured default view when nothing is stored', () => {
    const { map } = mount({ config: { lat: 52.37, lng: 4.89, zoom: 10 } });

    assert.deepEqual([map.getCenter().lat, map.getCenter().lng], [52.37, 4.89]);
    assert.equal(map.getZoom(), 10);
    assert.equal(map.scrollWheelZoom.enabled(), false);
});

test('uses OpenStreetMap tiles for the openstreetmap provider', () => {
    const { layers } = mount();
    const [tiles] = layers(window.L.TileLayer);

    assert.equal(tiles._url, 'https://tile.openstreetmap.org/{z}/{x}/{y}.png');
});

test('uses Mapbox tiles with the token and style for the mapbox provider', () => {
    const { layers } = mount({ config: { provider: 'mapbox', accessToken: 'pk.test', style: 'mapbox/satellite-v9' } });
    const [tiles] = layers(window.L.TileLayer);

    assert.match(tiles._url, /^https:\/\/api\.mapbox\.com\/styles\/v1\/\{id\}/);
    assert.equal(tiles.options.accessToken, 'pk.test');
    assert.equal(tiles.options.id, 'mapbox/satellite-v9');
});

test('loads a stored Feature into the editable layer', () => {
    const { drawnItems, input } = mount({ value: JSON.stringify(feature()) });

    assert.equal(drawnItems.getLayers().length, 1);
    assert.ok(drawnItems.getLayers()[0] instanceof window.L.Polygon);
    assert.deepEqual(JSON.parse(input.value), feature(), 'value is left untouched on load');
});

test('loads a stored FeatureCollection into the editable layer', () => {
    const collection = { type: 'FeatureCollection', features: [feature(), feature(0.2)] };
    const { drawnItems } = mount({ value: JSON.stringify(collection), config: { multiple: true } });

    assert.equal(drawnItems.getLayers().length, 2);
});

test('ignores an invalid stored value instead of throwing', () => {
    const warnings = [];
    window.console.warn = (...args) => warnings.push(args);

    const { drawnItems } = mount({ value: '{not json' });

    assert.equal(drawnItems.getLayers().length, 0);
    assert.equal(warnings.length, 1);
});

test('single mode: drawing stores a Feature and replaces the previous shape', () => {
    const { map, drawnItems, input, changes } = mount({ value: JSON.stringify(feature()) });

    drawPolygon(map, triangle(0));
    drawPolygon(map, triangle(0.5));

    assert.equal(drawnItems.getLayers().length, 1);

    const value = JSON.parse(input.value);
    assert.equal(value.type, 'Feature');
    assert.equal(value.geometry.type, 'Polygon');
    assert.deepEqual(value.geometry.coordinates[0][0], [30.6, 37.4]);
    assert.equal(changes.length, 2, 'a change event is triggered for Backpack');
});

test('multiple mode: drawing appends to a FeatureCollection', () => {
    const { map, drawnItems, input } = mount({ config: { multiple: true } });

    drawPolygon(map, triangle(0));
    drawPolygon(map, triangle(0.5));

    assert.equal(drawnItems.getLayers().length, 2);

    const value = JSON.parse(input.value);
    assert.equal(value.type, 'FeatureCollection');
    assert.equal(value.features.length, 2);
});

test('editing a shape updates the value', () => {
    const { map, drawnItems, input } = mount({ value: JSON.stringify(feature()) });
    const [polygon] = drawnItems.getLayers();

    polygon.setLatLngs(triangle(1));
    map.fire(window.L.Draw.Event.EDITED, { layers: window.L.layerGroup([polygon]) });

    assert.deepEqual(JSON.parse(input.value).geometry.coordinates[0][0], [30.6, 37.9]);
});

test('deleting every shape clears the value', () => {
    const { map, drawnItems, input, changes } = mount({ value: JSON.stringify(feature()) });

    const removed = window.L.layerGroup(drawnItems.getLayers());
    drawnItems.clearLayers();
    map.fire(window.L.Draw.Event.DELETED, { layers: removed });

    assert.equal(input.value, '');
    assert.deepEqual(changes, ['']);
});

test('deleting one of many shapes keeps the rest in multiple mode', () => {
    const collection = { type: 'FeatureCollection', features: [feature(), feature(0.2)] };
    const { map, drawnItems, input } = mount({ value: JSON.stringify(collection), config: { multiple: true } });

    const [first] = drawnItems.getLayers();
    drawnItems.removeLayer(first);
    map.fire(window.L.Draw.Event.DELETED, { layers: window.L.layerGroup([first]) });

    assert.equal(JSON.parse(input.value).features.length, 1);
});

test('several fields on one page work independently', () => {
    const first = mount({ name: 'coordinates' });
    const second = mount({ name: 'areas', config: { multiple: true } });

    drawPolygon(first.map, triangle(0));

    assert.notEqual(first.map, second.map);
    assert.equal(JSON.parse(first.input.value).type, 'Feature');
    assert.equal(second.input.value, '');
});

test('initialising the same field twice is a no-op', () => {
    const { element, map, mapElement } = mount();

    window.bpFieldInitLeafletDrawElement(element);

    assert.equal(mapElement._leafletDrawMap, map);
    assert.equal(mapElement.querySelectorAll('.leaflet-draw-toolbar-top').length, 1);
});
