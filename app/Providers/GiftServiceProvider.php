<?php
/**
 * Created by PhpStorm.
 * User: karl
 * Date: 2018/1/30
 * Time: 上午10:06
 */

namespace SingPlus\Providers;


use Illuminate\Support\ServiceProvider;

class GiftServiceProvider extends ServiceProvider
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
            \SingPlus\Contracts\Gifts\Services\GiftService::class,
            \SingPlus\Domains\Gifts\Services\GiftService::class
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
            \SingPlus\Contracts\Gifts\Services\GiftService::class,
        ];
    }
}