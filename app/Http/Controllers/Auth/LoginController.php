<?php

namespace SingPlus\Http\Controllers\Auth;
 
use Illuminate\Contracts\Auth\Guard;
use Illuminate\Http\Request;
use SingPlus\Http\Controllers\Controller;
use SingPlus\Services\Auth\LoginService;
use SingPlus\Exceptions\ExceptionCode;

class LoginController extends Controller
{
  /**
   * user login
   */
  public function login(Request $request, LoginService $loginService)
  {
    $this->validate($request, [
      'countryCode' => 'required|countrycode',
      'mobile'      => 'required|mobile',
      'password'    => 'required|password',
    ], [
      'mobile.mobile' => 'Invalid phone number', 
    ]);

    $user = $loginService->login(
      (int) $request->request->get('countryCode'),
      $request->request->get('mobile'),
      $request->request->get('password'),
      true
    );

    return $this->renderInfo('login success', [
                              'userId'      => $user->userId,
                              'isNewUser'   => $user->isNewUser, 
                            ]);
  }

  /**
   * user logout
   */
  public function logout(Guard $guard, LoginService $loginService)
  {
    if ($guard->check()) {
      $loginService->logout();
    }

    return $this->renderInfo('success');
  }
}
