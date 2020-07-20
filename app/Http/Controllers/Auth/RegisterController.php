<?php

namespace SingPlus\Http\Controllers\Auth;

use Illuminate\Http\Request;
use SingPlus\Http\Controllers\Controller;
use SingPlus\Services\Auth\RegisterService;

class RegisterController extends Controller
{
  /**
   * user register
   */
  public function register(Request $request, RegisterService $registerService)
  {
    $this->validate($request, [
      'countryCode' => 'required|countrycode',
      'mobile'      => 'required|mobile',
      'password'    => 'required|password',
      'code'        => 'required|size:4',
    ], [
      'mobile.mobile' => 'Invalid phone number', 
    ]);

    $user = $registerService->register(
      (int) $request->request->get('countryCode'),
      $request->request->get('mobile'),
      $request->request->get('password'),
      $request->request->get('code')
    );

    return $this->renderInfo('success', [
      'userId'  => $user->userId, 
    ]);
  }
}
