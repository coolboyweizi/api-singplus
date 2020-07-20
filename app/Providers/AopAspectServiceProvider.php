<?php

namespace SingPlus\Providers;

use Illuminate\Support\ServiceProvider;

class AopAspectServiceProvider extends ServiceProvider
{
  /**
   * Bootstrap the application services.
   *
   * @return void
   */
  public function boot()
  {
      //
  }

  /**
   * Register the application services.
   *
   * @return void
   */
  public function register()
  {
    $this->app->singleton(
      \SingPlus\Aop\Aspects\FeedbackAspect::class,
      \SingPlus\Aop\Aspects\FeedbackAspect::class
    );

    $this->app->tag([
      \SingPlus\Aop\Aspects\FeedbackAspect::class,
    ], 'goaop.aspects');
  }
}
