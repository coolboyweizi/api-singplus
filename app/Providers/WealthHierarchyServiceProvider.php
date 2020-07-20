<?php
/**
 * Created by PhpStorm.
 * User: karl
 * Date: 2018/2/28
 * Time: 下午6:32
 */

namespace SingPlus\Providers;


use Illuminate\Support\ServiceProvider;

class WealthHierarchyServiceProvider extends ServiceProvider
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
            \SingPlus\Contracts\Hierarchy\Services\WealthHierarchyService::class,
            \SingPlus\Domains\Hierarchy\Services\WealthHierarchyService::class
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
            \SingPlus\Contracts\Hierarchy\Services\WealthHierarchyService::class,
        ];
    }
}