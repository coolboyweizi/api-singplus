<?php

namespace SingPlus\Http\Controllers\Api;

use Illuminate\Http\Request;
use SingPlus\Http\Controllers\Controller;
use SingPlus\Services\UserService;

class UserController extends Controller
{
  /**
   * Create a synthetic user
   */
  public function createSyntheticUser(
    Request $request,
    UserService $userService
  ) {
    $this->validate($request, [
      'countryCode' => 'required|countrycode',
      'mobile'      => 'required|mobile',
      'password'    => 'required|password',
      'nickname'    => 'required|string|max:50',
      'avatar'      => 'required|mimes:jpeg,png,jpg|max:20480',
    ]);

    $userId = $userService->createSyntheticUser(
      (int) $request->request->get('countryCode'),
      $request->request->get('mobile'),
      $request->request->get('password'),
      $request->request->get('nickname'),
      $request->file('avatar')
    );

    return $this->renderInfo('success', [
      'userId'  => $userId,
    ]);
  }
}
