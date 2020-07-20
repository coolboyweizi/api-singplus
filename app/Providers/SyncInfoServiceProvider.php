<?php
/**
 * Created by PhpStorm.
 * User: karl
 * Date: 2018/3/19
 * Time: 下午2:48
 */

namespace SingPlus\Providers;


use Illuminate\Support\ServiceProvider;

class SyncInfoServiceProvider extends ServiceProvider
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
            \SingPlus\Contracts\Sync\Services\SyncInfoService::class,
            \SingPlus\Domains\Sync\Services\SyncInfoService::class
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
            \SingPlus\Contracts\Sync\Services\SyncInfoService::class,
        ];
    }

}