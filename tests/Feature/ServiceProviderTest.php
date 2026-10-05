<?php

namespace Siberfx\LeafletDrawjs\Tests\Feature;

use Backpack\CRUD\ViewNamespaces;
use Illuminate\Support\ServiceProvider;
use Siberfx\LeafletDrawjs\LeafletDrawServiceProvider;
use Siberfx\LeafletDrawjs\Tests\TestCase;

class ServiceProviderTest extends TestCase
{
    public function test_provider_is_registered(): void
    {
        $this->assertArrayHasKey(LeafletDrawServiceProvider::class, $this->app->getLoadedProviders());
    }

    public function test_composer_discovers_the_provider(): void
    {
        $composer = json_decode(file_get_contents(__DIR__.'/../../composer.json'), true);

        $this->assertSame([LeafletDrawServiceProvider::class], $composer['extra']['laravel']['providers']);
    }

    public function test_config_defaults_are_merged_without_publishing(): void
    {
        $this->assertSame('mapbox', config('backpack.leaflet-draw.provider'));
        $this->assertSame('mapbox/streets-v12', config('backpack.leaflet-draw.mapbox.style'));
        $this->assertSame(12, config('backpack.leaflet-draw.default.zoom'));
        $this->assertSame('300px', config('backpack.leaflet-draw.default.height'));
    }

    public function test_field_view_is_available_without_publishing(): void
    {
        $this->assertContains('leaflet-draw::fields', ViewNamespaces::getFor('fields'));
        $this->assertTrue(view()->exists('leaflet-draw::fields.leaflet-draw'));
    }

    public function test_publishable_paths(): void
    {
        $config = ServiceProvider::pathsToPublish(LeafletDrawServiceProvider::class, 'config');
        $views = ServiceProvider::pathsToPublish(LeafletDrawServiceProvider::class, 'views');
        $all = ServiceProvider::pathsToPublish(LeafletDrawServiceProvider::class, 'all');

        $this->assertSame([config_path('backpack/leaflet-draw.php')], array_values($config));
        $this->assertSame([resource_path('views/vendor/backpack/crud/fields')], array_values($views));
        $this->assertCount(2, $all);

        foreach (array_keys($all) as $source) {
            $this->assertFileExists($source);
        }
    }
}
