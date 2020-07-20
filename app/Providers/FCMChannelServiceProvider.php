<?php

namespace SingPlus\Providers;

use Illuminate\Notifications\ChannelManager;
use SingPlus\Support\Notification\FCMChannel;

class FCMChannelServiceProvider extends \Illuminate\Support\ServiceProvider
{
    /**
     * Bootstrap the application services.
     */
    public function boot()
    {
        $this->app->make(ChannelManager::class)->extend('fcm', function () {
            return new FCMChannel($this->app->make('fcm.sender'));
        });
    }

    /**
     * Register any package services.
     */
    public function register()
    {
        $this->app->register(\LaravelFCM\FCMServiceProvider::class);
    }
}
