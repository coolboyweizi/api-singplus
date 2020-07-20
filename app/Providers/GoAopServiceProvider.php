<?php

namespace SingPlus\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Contracts\Foundation\Application;
use Go\Core\AspectContainer;
use Go\Core\AspectKernel;
use SingPlus\Aop\Kernel as ApplicationAopKernel;

class GoAopServiceProvider extends ServiceProvider
{
  /*
   * Indicates if loading of the provider is deferred.
   *
   * @var bool
   */
  protected $defer = true;

  /**
   * Bootstrap the application services.
   *
   * @return void
   */
  public function boot()
  {
    $aspectContainer = $this->app->make(AspectContainer::class);

    // Let's collect all aspects and just register them in the container
    $aspects = $this->app->tagged('goaop.aspects');
    foreach ($aspects as $aspect) {
      $aspectContainer->registerAspect($aspect);
    }
  }

  /**
   * Register the application services.
   *
   * @return void
   */
  public function register()
  {
    $this->app->singleton(AspectKernel::class, function (Application $app) {
      $aopKernel = ApplicationAopKernel::getInstance();
      $aopKernel->init(config('goaop'));
      
      return $aopKernel;
    });
    
    $this->app->singleton(AspectContainer::class, function (Application $app) {
      $aopKernel = $app->make(AspectKernel::class); 

      return $aopKernel->getContainer();
    });
  }

  public function provides()
  {
    return [
      AspectKernel::class,
      AspectContainer::class,
    ];
  }
}
