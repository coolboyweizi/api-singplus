<?php
/**
 * Created by PhpStorm.
 * User: karl
 * Date: 2017/12/14
 * Time: 上午11:25
 */


namespace SingPlus\Http\Middleware;
use Closure;
use SingPlus\Services\UserService;
use Illuminate\Contracts\Auth\Factory as Auth;

class LastVisitSave
{

    /**
     * @var UserService
     */
    private $userService;
    private $auth;

    public function __construct(Auth $auth,UserService $userService)
    {
        $this->userService = $userService;
        $this->app = app();
        $this->auth = $auth;
    }

    public function handle($request, Closure $next)
    {
        return $next($request);
    }

    public function terminate($request, $response)
    {
        if ( !$this->shouldSkipSave() && $this->auth->check()) {
            $userId = $this->auth->id();
            $version = $request->headers->get('X-Version');
            $this->userService->saveAuthUserLastVisitInfo($userId, $version);
        }
    }

    public function shouldSkipSave()
    {
        // Just for testing
        return app()->bound('middleware.authUser.lastVistInfoSave.disable') &&
            app()->make('middleware.authUser.lastVistInfoSave.disable') === true;
    }
}