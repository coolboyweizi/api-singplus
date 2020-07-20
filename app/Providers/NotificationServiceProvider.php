<?php

namespace SingPlus\Providers;

use Illuminate\Support\ServiceProvider;

class NotificationServiceProvider extends ServiceProvider
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
      \SingPlus\Contracts\Notifications\Services\NotificationService::class,
      \SingPlus\Domains\Notifications\Services\NotificationService::class
    );
    $this->app->singleton(
      \SingPlus\Contracts\Notifications\Services\PushMessageService::class,
      \SingPlus\Domains\Notifications\Services\PushMessageService::class
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
      \SingPlus\Contracts\Notifications\Services\NotificationService::class,
      \SingPlus\Contracts\Notifications\Services\PushMessageService::class,
    ];  
  } 
}
