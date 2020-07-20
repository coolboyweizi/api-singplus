<?php

namespace SingPlus\Providers;

use Illuminate\Support\ServiceProvider;

class ChargeOrderServiceProvider extends ServiceProvider
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
      \SingPlus\Contracts\Orders\Services\ChargeOrderService::class,
      \SingPlus\Domains\Orders\Services\ChargeOrderService::class
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
      \SingPlus\Contracts\Orders\Services\ChargeOrderService::class,
    ];  
  } 
}
