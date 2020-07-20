<?php

namespace SingPlus\Providers\Musics;

use Illuminate\Support\ServiceProvider;

class LanguageServiceProvider extends ServiceProvider
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
      \SingPlus\Contracts\Musics\Services\LanguageService::class,
      \SingPlus\Domains\Musics\Services\LanguageService::class
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
      \SingPlus\Contracts\Musics\Services\LanguageService::class,
    ];  
  } 
}
