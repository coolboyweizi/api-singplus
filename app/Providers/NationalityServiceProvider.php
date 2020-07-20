<?php

namespace SingPlus\Providers;

use Illuminate\Support\ServiceProvider;

class NationalityServiceProvider extends ServiceProvider
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
      \SingPlus\Contracts\Nationalities\Services\NationalityService::class,
      \SingPlus\Domains\Nationalities\Services\NationalityService::class
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
      \SingPlus\Contracts\Nationalities\Services\NationalityService::class,
    ];  
  } 
}
