<?php

namespace SingPlus\Http\Middleware;

use Closure;
use Illuminate\Contracts\Auth\Factory as Auth;
use Illuminate\Auth\Middleware\Authenticate as BaseAuthenticate;
use Illuminate\Foundation\Testing\Concerns\InteractsWithAuthentication;
use SingPlus\Services\UserService;
use SingPlus\Domains\Users\Models\User;

class Authenticate extends BaseAuthenticate
{
  use InteractsWithAuthentication;

  /**
   * @var UserService
   */
  private $userService;

  public function __construct(Auth $auth, UserService $userService)
  {
    parent::__construct($auth);
    $this->userService = $userService;
    $this->app = app();
  }

  public function handle($request, Closure $next, ...$guards)
  {
    $this->authenticate($guards);
    $this->beUser($request, $guards);

    return $next($request);
  }

  /**
   * Do some work after the HTTP response has been sent to the client
   */
  public function terminate($request, $response)
  {
      // 记录已登录用户的最后一次访问的工作交给LastVisitSave这个中间件了
//    if ( ! $this->shouldSkipSave() && $this->auth->check()) {
//      $userId = $this->auth->id();
//      $version = $request->headers->get('X-Version');
//      $this->userService->saveAuthUserLastVisitInfo($userId, $version);
//    }
  }

  public function shouldSkipSave()
  {
    // Just for testing
    return app()->bound('middleware.authUser.lastVistInfoSave.disable') &&
           app()->make('middleware.authUser.lastVistInfoSave.disable') === true;
  }

  /**
   * For debug: as any user
   */
  protected function beUser($request, $guards)
  {
    if ( ! empty($guards) && ! in_array('web', $guards)) {
      return null;
    }

    if ( ! config('auth.guards.web.godmode')) {
      return null;
    }

    $debugUserId = $request->query->get('asUserForDebug');
    if ( ! $debugUserId) {
      return null;
    }
    $debugUser = User::find($debugUserId);
    if ( ! $debugUser) {
      return null;
    }

    $this->actingAs($debugUser);
  }
}
