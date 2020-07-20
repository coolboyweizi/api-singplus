<?php
/**
 * Created by PhpStorm.
 * User: karl
 * Date: 2018/1/18
 * Time: 下午7:25
 */

namespace SingPlus\Providers;


use Illuminate\Support\ServiceProvider;

class NewsServiceProvider extends ServiceProvider
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
            \SingPlus\Contracts\News\Services\NewsServices::class,
            \SingPlus\Domains\News\Services\NewsServices::class
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
            \SingPlus\Contracts\News\Services\NewsServices::class,
        ];
    }
}