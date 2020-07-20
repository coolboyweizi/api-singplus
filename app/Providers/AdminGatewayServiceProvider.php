<?php

namespace SingPlus\Providers;

use Log;
use Illuminate\Support\ServiceProvider;

class AdminGatewayServiceProvider extends ServiceProvider
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
      \SingPlus\Contracts\Admins\Services\AdminGatewayService::class,
      function ($app) {
        return new \SingPlus\Domains\Admins\Services\AdminGatewayService(
          $app->make(\GuzzleHttp\ClientInterface::class),
          Log::getMonolog()->withName('admin-interface')
        );
      }
    );
    $this->app->singleton(
      \SingPlus\Contracts\Admins\Services\AdminTaskService::class,
      \SingPlus\Domains\Admins\Services\AdminTaskService::class
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
      \SingPlus\Contracts\Admins\Services\AdminGatewayService::class,
      \SingPlus\Contracts\Admins\Services\AdminTaskService::class,
    ];  
  } 
}
