<?php

namespace SingPlus\Providers;

use Illuminate\Support\ServiceProvider;

class VersionUpdateServiceProvider extends ServiceProvider
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
    $this->app->singleton(
      \SingPlus\Contracts\ClientSupports\Services\VersionUpdateService::class,
      \SingPlus\Domains\ClientSupports\Services\VersionUpdateService::class
    );
  }

  /** 
   * Get the services provided by the provider.
   *
   * @return array
   */
  public function provides()
  {   
    return [
      \SingPlus\Contracts\ClientSupports\Services\VersionUpdateService::class,
    ];  
  } 
}
