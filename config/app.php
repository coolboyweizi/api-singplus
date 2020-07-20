<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Application Name
    |--------------------------------------------------------------------------
    |
    | This value is the name of your application. This value is used when the
    | framework needs to place the application's name in a notification or
    | any other location as required by the application or its packages.
    */

    'name' => 'ApiSingPlus',

    /*
    |--------------------------------------------------------------------------
    | Application Environment
    |--------------------------------------------------------------------------
    |
    | This value determines the "environment" your application is currently
    | running in. This may determine how you prefer to configure various
    | services your application utilizes. Set this in your ".env" file.
    |
    */

    'env' => env('APP_ENV', 'production'),

    /*
    |--------------------------------------------------------------------------
    | Application Debug Mode
    |--------------------------------------------------------------------------
    |
    | When your application is in debug mode, detailed error messages with
    | stack traces will be shown on every error that occurs within your
    | application. If disabled, a simple generic error page is shown.
    |
    */

    'debug' => env('APP_DEBUG', false),

    /*
    |--------------------------------------------------------------------------
    | Application URL
    |--------------------------------------------------------------------------
    |
    | This URL is used by the console to properly generate URLs when using
    | the Artisan command line tool. You should set this to the root of
    | your application so that it is used when running Artisan tasks.
    |
    */

    'url' => env('APP_URL', 'http://localhost'),

    /*
    |--------------------------------------------------------------------------
    | Application Timezone
    |--------------------------------------------------------------------------
    |
    | Here you may specify the default timezone for your application, which
    | will be used by the PHP date and date-time functions. We have gone
    | ahead and set this to a sensible default for you out of the box.
    |
    */

    'timezone' => 'UTC',

    /*
    |--------------------------------------------------------------------------
    | Application Locale Configuration
    |--------------------------------------------------------------------------
    |
    | The application locale determines the default locale that will be used
    | by the translation service provider. You are free to set this value
    | to any of the locales which will be supported by the application.
    |
    */

    'locale' => 'en',

    /*
    |--------------------------------------------------------------------------
    | Application Fallback Locale
    |--------------------------------------------------------------------------
    |
    | The fallback locale determines the locale to use when the current one
    | is not available. You may change the value to correspond to any of
    | the language folders that are provided through your application.
    |
    */

    'fallback_locale' => 'en',

    /*
    |--------------------------------------------------------------------------
    | Encryption Key
    |--------------------------------------------------------------------------
    |
    | This key is used by the Illuminate encrypter service and should be set
    | to a random, 32 character string, otherwise these encrypted strings
    | will not be safe. Please do this before deploying an application!
    |
    */

    'key' => env('APP_KEY'),

    'cipher' => 'AES-256-CBC',

    /*
    |--------------------------------------------------------------------------
    | Logging Configuration
    |--------------------------------------------------------------------------
    |
    | Here you may configure the log settings for your application. Out of
    | the box, Laravel uses the Monolog PHP logging library. This gives
    | you a variety of powerful log handlers / formatters to utilize.
    |
    | Available Settings: "single", "daily", "syslog", "errorlog"
    |
    */

    'log' => env('APP_LOG', 'single'),

    'log_level' => env('APP_LOG_LEVEL', 'debug'),

    'log_max_files' => env('APP_LOG_MAX_FILES', 15),

    /*
    |--------------------------------------------------------------------------
    | Autoloaded Service Providers
    |--------------------------------------------------------------------------
    |
    | The service providers listed here will be automatically loaded on the
    | request to your application. Feel free to add your own services to
    | this array to grant expanded functionality to your applications.
    |
    */

    'providers' => [

        /*
         * Laravel Framework Service Providers...
         */
        Illuminate\Auth\AuthServiceProvider::class,
        Illuminate\Broadcasting\BroadcastServiceProvider::class,
        Illuminate\Bus\BusServiceProvider::class,
        Illuminate\Cache\CacheServiceProvider::class,
        Illuminate\Foundation\Providers\ConsoleSupportServiceProvider::class,
        Illuminate\Cookie\CookieServiceProvider::class,
        Illuminate\Database\DatabaseServiceProvider::class,
        Illuminate\Encryption\EncryptionServiceProvider::class,
        Illuminate\Filesystem\FilesystemServiceProvider::class,
        Illuminate\Foundation\Providers\FoundationServiceProvider::class,
        Illuminate\Hashing\HashServiceProvider::class,
        Illuminate\Mail\MailServiceProvider::class,
        Illuminate\Notifications\NotificationServiceProvider::class,
        Illuminate\Pagination\PaginationServiceProvider::class,
        Illuminate\Pipeline\PipelineServiceProvider::class,
        Illuminate\Queue\QueueServiceProvider::class,
        Illuminate\Redis\RedisServiceProvider::class,
        Illuminate\Auth\Passwords\PasswordResetServiceProvider::class,
        Illuminate\Session\SessionServiceProvider::class,
        Illuminate\Translation\TranslationServiceProvider::class,
        Illuminate\Validation\ValidationServiceProvider::class,
        Illuminate\View\ViewServiceProvider::class,

        /*
         * Package Service Providers...
         */
        Laravel\Tinker\TinkerServiceProvider::class,
        Jenssegers\Mongodb\MongodbServiceProvider::class,
        Jenssegers\Mongodb\MongodbQueueServiceProvider::class,

        /*
         * Application Service Providers...
         */
        SingPlus\Providers\AppServiceProvider::class,
        SingPlus\Providers\AuthServiceProvider::class,
        // SingPlus\Providers\BroadcastServiceProvider::class,
        SingPlus\Providers\EventServiceProvider::class,
        SingPlus\Providers\RouteServiceProvider::class,

        SingPlus\Providers\UserAuthProvider::class,
        SingPlus\Providers\UserServiceProvider::class,
        SingPlus\Providers\UserProfileServiceProvider::class,
        SingPlus\Providers\UserImageServiceProvider::class,
        SingPlus\Providers\VerificationServiceProvider::class,
        SingPlus\Providers\HelpServiceProvider::class,
        SingPlus\Providers\StorageServiceProvider::class,
        SingPlus\Providers\BannerServiceProvider::class,
        SingPlus\Providers\MusicServiceProvider::class,
        SingPlus\Providers\ArtistServiceProvider::class,
        SingPlus\Providers\NationalityServiceProvider::class,

        // musics
        SingPlus\Providers\Musics\LanguageServiceProvider::class,
        SingPlus\Providers\Musics\StyleServiceProvider::class,

        SingPlus\Providers\WorkServiceProvider::class,
        SingPlus\Providers\WorkRankServiceProvider::class,
        SingPlus\Providers\AnnouncementServiceProvider::class,

        //Laravel\Socialite\SocialiteServiceProvider::class,
        SingPlus\Support\Socialite\ServiceProvider::class,

        SingPlus\SMS\SMSServiceProvider::class,
        SingPlus\Providers\UrlShortenerProvider::class,
        SingPlus\Providers\VersionUpdateServiceProvider::class,
        SingPlus\Providers\PayPalConfigServiceProvider::class,
        SingPlus\Providers\DeviceServiceProvider::class,
        //SingPlus\Providers\GoAopServiceProvider::class,
        //SingPlus\Providers\AopAspectServiceProvider::class,

        //NotificationChannels\FCM\ServiceProvider::class,
        SingPlus\Providers\FCMChannelServiceProvider::class,
        SingPlus\Providers\FeedServiceProvider::class,
        SingPlus\Providers\NotificationServiceProvider::class,
        SingPlus\Providers\FriendServiceProvider::class,
        SingPlus\Providers\AdminGatewayServiceProvider::class,
        SingPlus\Providers\HttpClientServiceProvider::class,
        SingPlus\Providers\AdServiceProvider::class,

        SingPlus\Providers\Logs\NotificationServiceProvider::class,
        SingPlus\Support\Logs\Providers\LocationServiceProvider::class,
        SingPlus\Support\Logs\Providers\TUDCServiceProvider::class,
        SingPlus\Support\Logs\Providers\RequestServiceProvider::class,
        SingPlus\Support\Logs\Providers\TXIMServiceProvider::class,
        SingPlus\Support\Locations\Providers\LocationServiceProvider::class,
        SingPlus\Providers\TUDCServiceProvider::class,
        SingPlus\Providers\TXIMServiceProvider::class,

        SingPlus\Providers\SearchServiceProvider::class,
        SingPlus\Providers\NewsServiceProvider::class,
        SingPlus\Support\Google\CacheProvider::class,
        SingPlus\Support\Google\ApiServiceAdapterProvider::class,
        SingPlus\Providers\ChargeOrderServiceProvider::class,
        SingPlus\Providers\SkuServiceProvider::class,
        SingPlus\Providers\AccountServiceProvider::class,
        SingPlus\Providers\GiftServiceProvider::class,
        SingPlus\Providers\DailyTaskServiceProvider::class,
        SingPlus\Providers\PopularityHierarchyServiceProvider::class,
        SingPlus\Providers\WealthHierarchyServiceProvider::class,
        SingPlus\Providers\SyncInfoServiceProvider::class,
        SingPlus\Providers\WorkTagServiceProvider::class,
        SingPlus\Providers\BoomcoinServiceProvider::class,
    ],

    /*
    |--------------------------------------------------------------------------
    | Class Aliases
    |--------------------------------------------------------------------------
    |
    | This array of class aliases will be registered when this application
    | is started. However, feel free to register as many as you wish as
    | the aliases are "lazy" loaded so they don't hinder performance.
    |
    */

    'aliases' => [

        'App' => Illuminate\Support\Facades\App::class,
        'Artisan' => Illuminate\Support\Facades\Artisan::class,
        'Auth' => Illuminate\Support\Facades\Auth::class,
        'Blade' => Illuminate\Support\Facades\Blade::class,
        'Broadcast' => Illuminate\Support\Facades\Broadcast::class,
        'Bus' => Illuminate\Support\Facades\Bus::class,
        'Cache' => Illuminate\Support\Facades\Cache::class,
        'Config' => Illuminate\Support\Facades\Config::class,
        'Cookie' => Illuminate\Support\Facades\Cookie::class,
        'Crypt' => Illuminate\Support\Facades\Crypt::class,
        'DB' => Illuminate\Support\Facades\DB::class,
        'Eloquent' => Illuminate\Database\Eloquent\Model::class,
        'Event' => Illuminate\Support\Facades\Event::class,
        'File' => Illuminate\Support\Facades\File::class,
        'Gate' => Illuminate\Support\Facades\Gate::class,
        'Hash' => Illuminate\Support\Facades\Hash::class,
        'Lang' => Illuminate\Support\Facades\Lang::class,
        'Log' => Illuminate\Support\Facades\Log::class,
        'Mail' => Illuminate\Support\Facades\Mail::class,
        'Notification' => Illuminate\Support\Facades\Notification::class,
        'Password' => Illuminate\Support\Facades\Password::class,
        'Queue' => Illuminate\Support\Facades\Queue::class,
        'Redirect' => Illuminate\Support\Facades\Redirect::class,
        'Redis' => Illuminate\Support\Facades\Redis::class,
        'Request' => Illuminate\Support\Facades\Request::class,
        'Response' => Illuminate\Support\Facades\Response::class,
        'Route' => Illuminate\Support\Facades\Route::class,
        'Schema' => Illuminate\Support\Facades\Schema::class,
        'Session' => Illuminate\Support\Facades\Session::class,
        'Storage' => Illuminate\Support\Facades\Storage::class,
        'URL' => Illuminate\Support\Facades\URL::class,
        'Validator' => Illuminate\Support\Facades\Validator::class,
        'View' => Illuminate\Support\Facades\View::class,
        'Socialite' => Laravel\Socialite\Facades\Socialite::class,

        'SMS' => SingPlus\SMS\Facades\SMS::class,
        'LogNotification' => SingPlus\Support\Notification\Facades\Log::class,
        'LogLocation' => SingPlus\Support\Logs\Facades\Location::class,
        'LogTUDC' => SingPlus\Support\Logs\Facades\TUDC::class,
        'LogReq'  => SingPlus\Support\Logs\Facades\Request::class,
        'LogTXIM' => SingPlus\Support\Logs\Facades\TXIM::class,
        'Location' => Stevebauman\Location\Facades\Location::class,
    ],

    'op_data_fake' => env('OP_DATA_FAKE', false),
];
