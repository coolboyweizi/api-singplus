<?php
/**
 * Created by PhpStorm.
 * User: karl
 * Date: 2018/2/27
 * Time: 下午4:07
 */
namespace SingPlus\Providers;

use Illuminate\Support\ServiceProvider;

class PopularityHierarchyServiceProvider extends ServiceProvider
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
            \SingPlus\Contracts\Hierarchy\Services\PopularityHierarchyService::class,
            \SingPlus\Domains\Hierarchy\Services\PopularityHierarchyService::class
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
            \SingPlus\Contracts\Hierarchy\Services\PopularityHierarchyService::class,
        ];
    }
}
