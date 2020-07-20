<?php

namespace SingPlus\Support\Locations\Providers;

use Illuminate\Support\ServiceProvider;
use SingPlus\Support\Locations\Location;

class LocationServiceProvider extends ServiceProvider
{
    /**
     * Run boot operations.
     */
    public function boot()
    {
        $this->app->bind('location', Location::class);
    }

    /**
     * Register the service provider.
     */
    public function register()
    {
        $config = base_path() . '/vendor/stevebauman/location/src/Config/config.php';

        $this->publishes([
            $config => config_path('location.php'),
        ], 'config');

        $this->mergeConfigFrom($config, 'location');
    }
    
    /**
     * Get the services provided by the provider.
     *
     * @return array
     */
    public function provides()
    {
        return ['location'];
    }
}
