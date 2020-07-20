<?php

namespace SingPlus\Support\Google;

use Illuminate\Support\ServiceProvider;
use Symfony\Component\Cache\Adapter\RedisAdapter;

class CacheProvider extends ServiceProvider
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
    $this->app->singleton('google.cache', function ($app) {
         
        $redisConn = $app->make('redis')->connection('default');
        return new RedisAdapter(
            $redisConn->client(),
            sprintf('%s.google.auth', config('cache.prefix'))
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
        'google.cache',
    ];  
  } 
}
