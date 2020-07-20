<?php

namespace SingPlus\Http\Controllers\Auth;

use Illuminate\Http\Request;
use SingPlus\Http\Controllers\Controller;
use SingPlus\Services\Auth\TUDCService;

class TUDCController extends Controller
{
  /**
   * @tudc login
   */
  public function login(
    Request $request,
    TUDCService $TUDCService
  ) {
    $this->validate($request, [
      'countryCode' => 'required|countrycode',
      'mobile'      => 'required|mobile',
      'password'    => 'required|password',
      'tudcTicket'  => 'required|max:512',
    ], [
      'mobile.mobile' => 'Invalid phone number', 
    ]);

    $user = $TUDCService->login(
      (int) $request->request->get('countryCode'),
      $request->request->get('mobile'),
      $request->request->get('password'),
      $request->request->get('tudcTicket'),
      true
    );

    return $this->renderInfo('login success', [
      'userId'      => $user->userId,
      'isNewUser'   => $user->isNewUser,
      'tudcOpenid'  => $user->tudcOpenid,
    ]);
  }
}
