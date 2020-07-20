<?php
/**
 * Created by PhpStorm.
 * User: karl
 * Date: 2018/4/8
 * Time: 下午4:16
 */

namespace SingPlus\Providers;


use Illuminate\Support\ServiceProvider;

class BoomcoinServiceProvider extends ServiceProvider
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
            \SingPlus\Contracts\Boomcoin\Services\BoomcoinService::class,
            \SingPlus\Domains\Boomcoin\Services\BoomcoinService::class
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
            \SingPlus\Contracts\Boomcoin\Services\BoomcoinService::class,
        ];
    }
}
