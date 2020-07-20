<?php

namespace SingPlus\Http;

use Illuminate\Foundation\Http\Kernel as HttpKernel;
use SingPlus\Http\Middleware\LastVisitSave;

class Kernel extends HttpKernel
{
    /**
     * The application's global HTTP middleware stack.
     *
     * These middleware are run during every request to your application.
     *
     * @var array
     */
    protected $middleware = [
        \Illuminate\Foundation\Http\Middleware\CheckForMaintenanceMode::class,
        \Illuminate\Foundation\Http\Middleware\ValidatePostSize::class,
        \SingPlus\Http\Middleware\TrimStrings::class,
        \Illuminate\Foundation\Http\Middleware\ConvertEmptyStringsToNull::class,
    ];

    /**
     * The application's route middleware groups.
     *
     * @var array
     */
    protected $middlewareGroups = [
        'web' => [
            \SingPlus\Http\Middleware\EncryptCookies::class,
            \Illuminate\Cookie\Middleware\AddQueuedCookiesToResponse::class,
            \Illuminate\Session\Middleware\StartSession::class,
            // \Illuminate\Session\Middleware\AuthenticateSession::class,
            \Illuminate\View\Middleware\ShareErrorsFromSession::class,
            //\SingPlus\Http\Middleware\VerifyCsrfToken::class,
            \Illuminate\Routing\Middleware\SubstituteBindings::class,
            \SingPlus\Http\Middleware\ETag::class,
            \SingPlus\Http\Middleware\LogRequestResponse::class,
//            \SingPlus\Http\Middleware\VerifyNewUser::class,
            \SingPlus\Http\Middleware\VersionCheck::class,
            \SingPlus\Http\Middleware\LastVisitSave::class,
        ],

        'h5'  => [
            \SingPlus\Http\Middleware\EncryptCookies::class,
            \Illuminate\Cookie\Middleware\AddQueuedCookiesToResponse::class,
            \Illuminate\Session\Middleware\StartSession::class,
            \Illuminate\View\Middleware\ShareErrorsFromSession::class,
            //\SingPlus\Http\Middleware\VerifyCsrfToken::class,
            \Illuminate\Routing\Middleware\SubstituteBindings::class,
            \SingPlus\Http\Middleware\ETag::class,
            \SingPlus\Http\Middleware\LogRequestResponse::class,
        ],

        'api' => [
            'throttle:60,1',
            'bindings',
            \SingPlus\Http\Middleware\LogRequestResponse::class,
        ],
    ];

    /**
     * The application's route middleware.
     *
     * These middleware may be assigned to groups or used individually.
     *
     * @var array
     */
    protected $routeMiddleware = [
        'auth' => \Illuminate\Auth\Middleware\Authenticate::class,
        'auth'        => \SingPlus\Http\Middleware\Authenticate::class,
        'auth.basic'  => \Illuminate\Auth\Middleware\AuthenticateWithBasicAuth::class,
        'bindings' => \Illuminate\Routing\Middleware\SubstituteBindings::class,
        'can' => \Illuminate\Auth\Middleware\Authorize::class,
        'guest' => \SingPlus\Http\Middleware\RedirectIfAuthenticated::class,
        'throttle' => \Illuminate\Routing\Middleware\ThrottleRequests::class,
        'news.throttle' => \SingPlus\Http\Middleware\NewsCreateThrottle::class,
        'api.sign'  => \SingPlus\Http\Middleware\CheckApiSign::class,
        'api.taskid'  => \SingPlus\Http\Middleware\UniqTaskId::class,
        'nation.operation'  => \SingPlus\Http\Middleware\Nationality::class,
        'activity.check'    => \SingPlus\Http\Middleware\CheckActivity::class,
    ];
}
