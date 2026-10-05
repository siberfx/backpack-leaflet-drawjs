<?php

namespace Siberfx\LeafletDrawjs\Tests;

use Backpack\Basset\BassetServiceProvider;
use Backpack\CRUD\BackpackServiceProvider;
use Creativeorange\Gravatar\GravatarServiceProvider;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Schema;
use Orchestra\Testbench\TestCase as Orchestra;
use Prologue\Alerts\AlertsServiceProvider;
use Siberfx\LeafletDrawjs\LeafletDrawServiceProvider;
use Siberfx\LeafletDrawjs\Tests\Fixtures\RegionCrudController;
use Siberfx\LeafletDrawjs\Tests\Fixtures\ThemeTablerServiceProvider;
use Siberfx\LeafletDrawjs\Tests\Fixtures\User;

abstract class TestCase extends Orchestra
{
    protected function setUp(): void
    {
        parent::setUp();

        // basset downloads the CDN assets on first render; keep the suite offline
        Http::fake(['*' => Http::response('/* asset */', 200)]);

        RegionCrudController::$fields = [];

        Schema::create('users', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('email')->unique();
            $table->string('password');
            $table->rememberToken();
            $table->timestamps();
        });

        Schema::create('regions', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->json('coordinates')->nullable();
            $table->json('areas')->nullable();
            $table->timestamps();
        });
    }

    protected function getPackageProviders($app): array
    {
        return [
            AlertsServiceProvider::class,
            GravatarServiceProvider::class,
            BassetServiceProvider::class,
            BackpackServiceProvider::class,
            ThemeTablerServiceProvider::class,
            LeafletDrawServiceProvider::class,
        ];
    }

    protected function getPackageAliases($app): array
    {
        return [
            'Alert' => \Prologue\Alerts\Facades\Alert::class,
            'Basset' => \Backpack\Basset\Facades\Basset::class,
            'Gravatar' => \Creativeorange\Gravatar\Facades\Gravatar::class,
            'CRUD' => \Backpack\CRUD\app\Library\CrudPanel\CrudPanelFacade::class,
            'Widget' => \Backpack\CRUD\app\Library\Widget::class,
        ];
    }

    protected function defineEnvironment($app): void
    {
        $app['config']->set('app.key', 'base64:'.base64_encode(str_repeat('a', 32)));
        $app['config']->set('auth.providers.users.model', User::class);
        $app['config']->set('backpack.base.user_model_fqn', User::class);
        $app['config']->set('backpack.base.setup_email_verification_middleware', false);
        $app['config']->set('backpack.base.middleware_class', [
            \Illuminate\Foundation\Http\Middleware\ConvertEmptyStringsToNull::class,
        ]);
        $app['config']->set('backpack.ui.view_namespace', 'backpack.theme-tabler::');
        $app['config']->set('backpack.ui.view_namespace_fallback', 'backpack.theme-tabler::');
        $app['config']->set('backpack.basset.disk', 'basset-test');
        $app['config']->set('filesystems.disks.basset-test', [
            'driver' => 'local',
            'root' => sys_get_temp_dir().'/leaflet-draw-basset-'.getmypid(),
            'url' => '/storage',
        ]);
    }

    protected function defineRoutes($router): void
    {
        Route::group([
            'prefix' => 'admin',
            'middleware' => ['web', 'admin'],
        ], function () {
            Route::crud('region', RegionCrudController::class);
        });
    }

    protected function login(): User
    {
        $user = User::create(['name' => 'Admin', 'email' => 'admin@example.test', 'password' => bcrypt('secret')]);

        $this->actingAs($user, backpack_guard_name());

        return $user;
    }

    /**
     * Sample GeoJSON polygon, shaped like Leaflet's toGeoJSON() output.
     */
    protected function feature(float $offset = 0): array
    {
        return [
            'type' => 'Feature',
            'properties' => [],
            'geometry' => [
                'type' => 'Polygon',
                'coordinates' => [[
                    [30.59 + $offset, 36.83],
                    [30.61 + $offset, 36.89],
                    [30.66 + $offset, 36.90],
                    [30.59 + $offset, 36.83],
                ]],
            ],
        ];
    }

    /**
     * Decode the data-config attribute of the nth map on the page.
     */
    protected function mapConfig(string $html, int $index = 0): array
    {
        preg_match_all("/data-config='([^']*)'/", $html, $matches);

        $this->assertArrayHasKey($index, $matches[1], 'Map config attribute not found.');

        return json_decode(html_entity_decode($matches[1][$index]), true);
    }

    /**
     * Value of the hidden input for the given field name.
     */
    protected function inputValue(string $html, string $name): ?string
    {
        if (! preg_match('/<input\s+type="hidden"\s+name="'.preg_quote($name, '/').'"\s+value="([^"]*)"/', $html, $match)) {
            return null;
        }

        return html_entity_decode($match[1]);
    }
}
