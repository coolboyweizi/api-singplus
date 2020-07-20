<?php
/**
 * Created by PhpStorm.
 * User: karl
 * Date: 2018/3/22
 * Time: 下午5:18
 */

namespace SingPlus\Providers;

use Illuminate\Support\ServiceProvider;

class WorkTagServiceProvider extends ServiceProvider
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
            \SingPlus\Contracts\Works\Services\WorkTagService::class,
            \SingPlus\Domains\Works\Services\WorkTagService::class
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
            \SingPlus\Contracts\Works\Services\WorkTagService::class,
        ];
    }
}