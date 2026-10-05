<?php

namespace Siberfx\LeafletDrawjs;

use Backpack\CRUD\ViewNamespaces;
use Illuminate\Support\ServiceProvider;

class LeafletDrawServiceProvider extends ServiceProvider
{
    /**
     * Register the application services.
     */
    public function register(): void
    {
        $this->mergeConfigFrom(__DIR__.'/config/leaflet-draw.php', 'backpack.leaflet-draw');
    }

    /**
     * Bootstrap the application services.
     */
    public function boot(): void
    {
        $this->loadViewsFrom(__DIR__.'/resources/views', 'leaflet-draw');

        // make the `leaflet-draw` field type available without publishing the view
        ViewNamespaces::addFor('fields', 'leaflet-draw::fields');

        if ($this->app->runningInConsole()) {
            $this->publish();
        }
    }

    private function publish(): void
    {
        $crud_config = [
            __DIR__.'/config/leaflet-draw.php' => config_path('backpack/leaflet-draw.php'),
        ];

        $crud_views = [
            __DIR__.'/resources/views/fields' => resource_path('views/vendor/backpack/crud/fields'),
        ];

        $this->publishes($crud_config, 'config');
        $this->publishes($crud_views, 'views');
        $this->publishes(array_merge($crud_config, $crud_views), 'all');
    }
}
