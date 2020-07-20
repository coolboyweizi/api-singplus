<?php

namespace SingPlus\SMS;

use Illuminate\Support\ServiceProvider;
use SingPlus\SMS\SMSer;

class SMSServiceProvider extends ServiceProvider
{
  /**
   * Indicates if loading of the provider is deferred.
   *
   * @var bool
   */
  protected $defer = true;

  /**
   * Register the service provider.
   *
   * @return void
   */
  public function register()
  {
    $this->app->singleton(\SingPlus\SMS\TransportManager::class, function ($app) {
      return new \SingPlus\SMS\TransportManager($app);
    });
    $this->app->singleton(\SingPlus\SMS\SMSer::class, \SingPlus\SMS\SMSer::class);
    $this->app->alias(\SingPlus\SMS\SMSer::class, 'sms');
  }

  /**
   * Get the services provided by the provider.
   *
   * @return array
   */
  public function provides()
  {
    return [
      'sms',
      \SingPlus\SMS\SMSer::class,
      \SingPlus\SMS\TransportManager::class,
    ];
  }
}
