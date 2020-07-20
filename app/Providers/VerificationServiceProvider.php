<?php

namespace SingPlus\Providers;

use Illuminate\Support\ServiceProvider;

class VerificationServiceProvider extends ServiceProvider
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
      \SingPlus\Contracts\Verifications\Services\VerificationService::class, function ($app) {
      $config = $app['config']['verification'];
      return new \SingPlus\Domains\Verifications\Services\VerificationService(
        $app[\SingPlus\Domains\Verifications\Repositories\VerificationRepository::class],
        $config
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
      \SingPlus\Contracts\Verifications\Services\VerificationService::class,
    ];  
  } 
}
