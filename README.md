## Leaflet Draw Polygon with mapbox

#### <p align="center">Leaflet Drawing Polygon field and storing as json for Laravel Backpack ^6.x</p>

<p align="center">
 <img src="https://raw.githubusercontent.com/siberfx/backpack-leaflet-drawjs/refs/heads/main/img/preview.png">
</p>

<img alt="Stars" src="https://img.shields.io/github/stars/siberfx/backpack-leaflet-drawjs?style=plastic&labelColor=343b41"/> 
<img alt="Forks" src="https://img.shields.io/github/forks/siberfx/backpack-leaflet-drawjs?style=plastic&labelColor=343b41"/>

## Installation

You can install the package via composer:

```bash
composer require siberfx/backpack-leaflet-drawjs
```

## Usage
``` php

// config/leaflet.php file content, you can modify it by your own settings.
return [

    'mapbox' => [
        'access_token' => env('MAPS_MAPBOX_ACCESS_TOKEN', 'xxxxxxxxxxxxxxxxxxxxx'),
    ],
];

```

### Publish files

``` bash
php artisan vendor:publish --provider="Backpack\LeafletDrawjs\LeafLetServiceProvider" --tag="all" 
```


### Add Leaflet drawing polygon as json and store as json

you will have to need a migration with json or text type to store the data, as in the example below;
```php
 public function up()
    {
        Schema::create('polygons', function (Blueprint $table) {
            $table->id();
            $table->json('geojson'); // Store GeoJSON data
            $table->timestamps();
        });
    }

```

the model should be like;


```php
namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Polygon extends Model
{
    use HasFactory;

    protected $fillable = ['geojson'];

    // Optionally, if you want to decode the GeoJSON automatically
    public function getGeojsonAttribute($value)
    {
        return json_decode($value);
    }
}

```

the controller example should be like;

```php
namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Polygon;

class PolygonController extends Controller
{
    public function store(Request $request)
    {
        // Validate the incoming data (optional)
        $request->validate([
            'polygon' => 'required|array'
        ]);

        // Store the polygon data as GeoJSON or serialized data
        $polygon = new Polygon();
        $polygon->geojson = json_encode($request->polygon); // Save GeoJSON
        $polygon->save();

        return response()->json(['message' => 'Polygon saved successfully']);
    }
}
```
the route example;

```php
Route::post('/store-polygon', [PolygonController::class, 'store'])->name('store-polygon');
```


### Call it inside your controller like this or

or add in your Crud controller manually where you want to see it as shown below.

```php

 $this->crud->addField([
        'label' => 'Location',
        'name' => 'location',
        'type' => 'leaflet-draw',
        'route' => route('store-polygon'), // as you desire
        'options' => [
            'provider' => 'mapbox',
            'marker_image' => null
        ],
        'tab' => 'General'
        'hint' => '<em>You can also drag and adjust your mark by clicking</em>'
 ]);

```

```

### Security

If you discover any security related issues, please email info@siberfx.com instead of using the issue tracker.

## Credits

- [Selim Gormus](https://github.com/siberfx)
