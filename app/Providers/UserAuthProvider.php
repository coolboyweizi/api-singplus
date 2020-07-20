<?php

namespace SingPlus\Providers;

use Illuminate\Support\ServiceProvider;

class UserAuthProvider extends ServiceProvider
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
      \SingPlus\Contracts\Users\Services\Auth\LoginService::class,
      \SingPlus\Domains\Users\Services\Auth\LoginService::class
    );

    $this->app->singleton(
      \SingPlus\Contracts\Users\Services\Auth\RegisterService::class,
      \SingPlus\Domains\Users\Services\Auth\RegisterService::class
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
      \SingPlus\Contracts\Users\Services\Auth\LoginService::class,
      \SingPlus\Contracts\Users\Services\Auth\RegisterService::class,
    ];  
  } 
}
