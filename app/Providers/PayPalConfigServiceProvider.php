<?php
/**
 * Created by PhpStorm.
 * User: zhangyujie
 * Date: 2018/7/16
 * Time: 下午6:43
 */

namespace SingPlus\Providers;


use Illuminate\Support\ServiceProvider;

class PayPalConfigServiceProvider extends ServiceProvider
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
            \SingPlus\Contracts\PayPal\Services\PayPalConfigService::class,
            \SingPlus\Domains\PayPal\Services\PayPalConfigService::class
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
            \SingPlus\Contracts\PayPal\Services\PayPalConfigService::class,
        ];
    }
}