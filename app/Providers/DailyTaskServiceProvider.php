<?php
/**
 * Created by PhpStorm.
 * User: karl
 * Date: 2018/1/25
 * Time: 下午6:08
 */

namespace SingPlus\Providers;
use Illuminate\Support\ServiceProvider;

class DailyTaskServiceProvider extends ServiceProvider
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
            \SingPlus\Contracts\DailyTask\Services\DailyTaskService::class,
            \SingPlus\Domains\DailyTask\Services\DailyTaskService::class
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
            \SingPlus\Contracts\DailyTask\Services\DailyTaskService::class,
        ];
    }
}