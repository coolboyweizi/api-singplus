<?php

namespace SingPlus\Support\Google;

use Illuminate\Support\ServiceProvider;
use Symfony\Component\Cache\Adapter\RedisAdapter;

class ApiServiceAdapterProvider extends ServiceProvider
{
  /** 
   * Indicates if loading of the provider is deferred.
   *
   * @var bool
   */
  protected $defer = true;

  /**
   * Bootstrap any application services.
   *
   * @return void
   */
  public function boot()
  {
    //
  }

  /**
   * Register any application services.
   *
   * @return void
   */
  public function register()
  {
    $this->app->singleton('google.service', function ($app) {
        putenv(sprintf('GOOGLE_APPLICATION_CREDENTIALS=%s', config('google.application_credentials')));
        return new \SingPlus\Support\Google\ApiServiceAdapter(
            $app->make('google.cache')
        );
    });
  }

  /** 
   * Get the services provided by the provider.
   *
   * @return array
   */
  public function provides()
  {   
    return [
        'google.service',
    ];  
  } 
}
