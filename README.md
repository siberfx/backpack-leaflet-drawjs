<h1 align="center">Leaflet Draw field for Backpack</h1>

<p align="center">
    Draw polygons on a Leaflet map (Mapbox or OpenStreetMap) and store them as GeoJSON, as a Laravel Backpack 7.x CRUD field.
</p>

<p align="center">
    <a href="https://github.com/siberfx/backpack-leaflet-drawjs/actions/workflows/tests.yml"><img alt="Tests" src="https://img.shields.io/github/actions/workflow/status/siberfx/backpack-leaflet-drawjs/tests.yml?branch=main&label=tests&style=flat-square&labelColor=343b41"></a>
    <a href="https://packagist.org/packages/siberfx/backpack-leaflet-drawjs"><img alt="Latest Version" src="https://img.shields.io/packagist/v/siberfx/backpack-leaflet-drawjs?style=flat-square&labelColor=343b41"></a>
    <a href="https://packagist.org/packages/siberfx/backpack-leaflet-drawjs"><img alt="Total Downloads" src="https://img.shields.io/packagist/dt/siberfx/backpack-leaflet-drawjs?style=flat-square&labelColor=343b41"></a>
    <a href="https://packagist.org/packages/siberfx/backpack-leaflet-drawjs"><img alt="PHP Version" src="https://img.shields.io/packagist/dependency-v/siberfx/backpack-leaflet-drawjs/php?style=flat-square&labelColor=343b41&color=777bb4"></a>
    <img alt="Laravel" src="https://img.shields.io/badge/Laravel-12.x%20%7C%2013.x-ff2d20?style=flat-square&labelColor=343b41&logo=laravel">
    <img alt="Backpack" src="https://img.shields.io/badge/Backpack-7.x-7c69ef?style=flat-square&labelColor=343b41">
    <a href="LICENSE.md"><img alt="License" src="https://img.shields.io/packagist/l/siberfx/backpack-leaflet-drawjs?style=flat-square&labelColor=343b41"></a>
</p>

<p align="center">
    <img src="https://raw.githubusercontent.com/siberfx/backpack-leaflet-drawjs/refs/heads/main/img/preview.png" alt="Leaflet Draw field preview">
</p>

## Features

- Draw, edit and delete polygons right inside a Backpack create / update form
- Saves the shape as a standard GeoJSON `Feature` (or a `FeatureCollection` in multiple mode)
- Mapbox tiles, or OpenStreetMap with no API key needed
- Assets are loaded through [Basset](https://github.com/Laravel-Backpack/basset), so nothing has to be published
- Works with several map fields on one form, and inside tabs and modals

## Requirements

| Package             | Version                 |
|---------------------|-------------------------|
| PHP                 | 8.2, 8.3, 8.4, 8.5      |
| Laravel             | 12.x, 13.x              |
| Backpack/CRUD       | 7.x                     |

> Using Backpack 6.x? Install the 1.x line: `composer require siberfx/backpack-leaflet-drawjs:^1.3`

## Installation

```bash
composer require siberfx/backpack-leaflet-drawjs
```

The service provider is auto-discovered, and the `leaflet-draw` field type works straight away.

Optionally add a Mapbox token to your `.env`. Without one, the field uses OpenStreetMap tiles:

```dotenv
MAPS_MAPBOX_ACCESS_TOKEN=pk.your-mapbox-token
```

## Usage

### 1. Add a column that can hold JSON

```php
Schema::create('regions', function (Blueprint $table) {
    $table->id();
    $table->string('name');
    $table->json('coordinates')->nullable(); // GeoJSON
    $table->timestamps();
});
```

### 2. Cast the attribute on your model

```php
class Region extends Model
{
    use \Backpack\CRUD\app\Models\Traits\CrudTrait;

    protected $fillable = ['name', 'coordinates'];

    protected function casts(): array
    {
        return [
            'coordinates' => 'array',
        ];
    }
}
```

### 3. Add the field to your CrudController

```php
CRUD::field([
    'name'  => 'coordinates',
    'label' => 'Polygon coordinates',
    'type'  => 'leaflet-draw',
    'hint'  => 'Use the polygon tool to draw an area. Click the edit tool to adjust it.',
]);
```

That's all you need. Backpack saves the GeoJSON with the rest of the form, so you don't need a custom route or controller.

### Field options

Every option is optional. Anything you leave out falls back to `config/backpack/leaflet-draw.php`.

```php
CRUD::field([
    'name'    => 'coordinates',
    'label'   => 'Polygon coordinates',
    'type'    => 'leaflet-draw',
    'options' => [
        'provider'          => 'mapbox',             // 'mapbox' or 'openstreetmap'
        'access_token'      => null,                 // overrides the configured Mapbox token
        'style'             => 'mapbox/streets-v12', // Mapbox style id
        'lat'               => 36.9667757,           // initial center (when nothing is stored yet)
        'lng'               => 30.7028187,
        'zoom'              => 12,
        'height'            => '400px',
        'color'             => '#3388ff',            // polygon stroke / fill color
        'multiple'          => false,                // true: allow many polygons, stored as FeatureCollection
        'scroll_wheel_zoom' => false,
    ],
]);
```

### Stored data

With `multiple => false` (the default), the field stores a single GeoJSON `Feature`:

```json
{
  "type": "Feature",
  "properties": {},
  "geometry": {
    "type": "Polygon",
    "coordinates": [[[30.59967, 36.832371], [30.617523, 36.899391], [30.669708, 36.904882], [30.59967, 36.832371]]]
  }
}
```

With `multiple => true`, it stores a `FeatureCollection`. When every shape is deleted, the field stores an empty value.

## Configuration

To change the defaults, publish the config file:

```bash
php artisan vendor:publish --provider="Siberfx\LeafletDrawjs\LeafletDrawServiceProvider" --tag="config"
```

```php
// config/backpack/leaflet-draw.php
return [
    'provider' => env('LEAFLET_DRAW_PROVIDER', 'mapbox'),

    'mapbox' => [
        'access_token' => env('MAPS_MAPBOX_ACCESS_TOKEN'),
        'style' => env('MAPS_MAPBOX_STYLE', 'mapbox/streets-v12'),
    ],

    'default' => [
        'lat' => 36.9667757,
        'lng' => 30.7028187,
        'zoom' => 12,
        'height' => '300px',
    ],
];
```

To customize the field markup, publish the view. It is copied to `resources/views/vendor/backpack/crud/fields/leaflet-draw.blade.php`:

```bash
php artisan vendor:publish --provider="Siberfx\LeafletDrawjs\LeafletDrawServiceProvider" --tag="views"
```

Use `--tag="all"` to publish both.

## Upgrading from 1.x

- You now need Backpack 7.x, Laravel 12 or 13, and PHP 8.2 or newer.
- You no longer need to publish the view. If you published it before, delete `resources/views/vendor/backpack/crud/fields/leaflet-draw.blade.php` to pick up the new version.
- The config file is now optional. If you published one, the `mapbox.access_token` key works as before.

## Testing

The PHP suite boots a real Backpack 7 app (Tabler theme) with Orchestra Testbench. It renders the create and edit pages and saves polygons through Backpack. The JavaScript suite runs the field script against real Leaflet and Leaflet.draw in jsdom.

```bash
composer install
composer test

npm install
npm test
```

GitHub Actions runs both suites on every push and pull request: PHP 8.2 to 8.5 on Laravel 12, and PHP 8.3 to 8.5 on Laravel 13.

## Changelog

See [CHANGELOG.md](CHANGELOG.md).

## Security

If you find a security issue, please email info@siberfx.com rather than opening a public issue.

## Credits

- [Selim Görmüş](https://github.com/siberfx)
- [Leaflet](https://leafletjs.com) and [Leaflet.draw](https://github.com/Leaflet/Leaflet.draw)

## License

MIT. See [LICENSE.md](LICENSE.md).
