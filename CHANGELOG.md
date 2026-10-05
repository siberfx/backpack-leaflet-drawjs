# Changelog

All notable changes to `siberfx/backpack-leaflet-drawjs` are documented in this file.

## Unreleased

### Added
- PHPUnit suite on Orchestra Testbench: provider and config, field rendering (create, edit, old input, provider and option handling, asset loading) and saving through Backpack.
- JavaScript suite (node:test + jsdom) for the field script: map init, tile providers, loading stored GeoJSON, draw, edit and delete, single and multiple modes, several fields on one page.
- GitHub Actions workflow: PHP 8.2 to 8.5 with Laravel 12 and 13, plus the JavaScript suite.
- `.gitattributes` keeps tests and tooling out of the Composer dist archive.

## 2.0.0 - 2026-10-05

### Breaking
- Requires Backpack/CRUD 7.x, Laravel 12.x / 13.x and PHP 8.2+. Backpack 6 users stay on `^1.3`.
- Field view moved to `src/resources/views/fields/leaflet-draw.blade.php` and is registered as a Backpack field view namespace, so publishing it is no longer required.

### Fixed
- Package auto-discovery: `composer.json` pointed to a non-existent service provider class, and the provider namespace did not match the PSR-4 autoload prefix.
- Blade error caused by `{{ mapId }}` in the hidden input name.
- Polygons loaded from the database could not be edited or deleted (they were not added to the editable layer).
- Values cast to `array` / `object` on the model were not rendered back into the map.
- Leaflet.draw toolbar icons missing when the stylesheet is served by Basset.
- Multiple `leaflet-draw` fields on the same form conflicted (global variables and a hard-coded input id).
- Map rendering incorrectly inside hidden tabs / modals.

### Added
- OpenStreetMap tile provider, used automatically when no Mapbox token is configured.
- Field options: `provider`, `access_token`, `style`, `lat`, `lng`, `zoom`, `height`, `color`, `multiple`, `scroll_wheel_zoom`.
- `multiple` mode storing a GeoJSON `FeatureCollection`.
- Config defaults are merged, so the config file no longer has to be published.
- Field label is rendered.

### Removed
- Unused Esri geocoder assets.

## 1.3.1

- Last release supporting Backpack 6.x.
