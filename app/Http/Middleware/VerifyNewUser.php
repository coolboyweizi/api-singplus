<?php

namespace SingPlus\Http\Middleware;

use Closure;
use SingPlus\Contracts\Users\Services\UserProfileService as UserProfileServiceContract;
use SingPlus\Exceptions\Users\UserNewException;

class VerifyNewUser 
{
  /**
   * @var UserProfileServiceContract
   */
  private $userProfileService;

  /**
   *
   */
  private $except = [
    'c/page/*',
    'v3/mobile/renew',
    'v3/nationalities',
    'v3/startup',
    'v3/passport/*',
    'v3/user/password/reset',
    'v3/verification/*',
    'v3/user/image/upload',
    'v3/user/image/gallery',
    'v3/user/info/complete',
    'v3/user/common-info',
    'v3/user/mobile-source',
    'v3/notification/user/push-alias',
  ];

  public function __construct(
    UserProfileServiceContract $userProfileService
  ) {
    $this->userProfileService = $userProfileService;
  }

  /**
   * Handle an incoming request.
   *
   * @param  \Illuminate\Http\Request  $request
   * @param  \Closure  $next
   * @return mixed
   */
  public function handle($request, Closure $next)
  {
    if ( ! $this->inExceptArray($request)) {
      if (
        ! $request->session()->has('isNewUser') || 
        $request->session()->get('isNewUser') !== false
      ) {
        $isNewUser = $this->userProfileService->isNewUser($request->user()->id);
        $request->session()->put('isNewUser', $isNewUser);
      }

      if ($request->session()->get('isNewUser') !== false) {
        throw new UserNewException();
      }
    }

    return $next($request);
  }

  /**
   * Determine if the request has a URI that should pass through CSRF verification.
   *
   * @param  \Illuminate\Http\Request  $request
   * @return bool
   */
  protected function inExceptArray($request)
  {
    foreach ($this->except as $except) {
      if ($except !== '/') {
        $except = trim($except, '/');
      }

      if ($request->is($except)) {
        return true;
      }
    }

    return false;
  }
}
