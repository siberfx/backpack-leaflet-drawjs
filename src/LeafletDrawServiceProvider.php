<?php

namespace Siberfx\LeafletDrawJs;

use Illuminate\Support\ServiceProvider;

class LeafletDrawServiceProvider extends ServiceProvider
{

    protected $defer = false;

    /**
     * Bootstrap the application services.
     */
    public function boot()
    {
        if ($this->app->runningInConsole()) {
            // publish the migrations and seeds

            $this->publish();
        }
    }

    private function publish()
    {
        $crud_views = [
            __DIR__ . '/resources/views' => resource_path('views/vendor/backpack/crud/fields'),
        ];
      

        $crud_config = [
            __DIR__ . '/config' => config_path('backpack'),
        ];

        $this->publishes($crud_config, 'config');
        $this->publishes($crud_views, 'views');
        $this->publishes(array_merge($crud_config, $crud_views), 'all');

    }

    /**
     * Register the application services.
     */
    public function register()
    {

    }
}
