<?php

namespace SingPlus\Http\Controllers\Auth;

use Illuminate\Http\Request;
use SingPlus\Http\Controllers\Controller;
use SingPlus\Services\Auth\SocialiteService;

class SocialiteController extends Controller
{
  /**
   * Socialite login
   */
  public function login(
    Request $request,
    SocialiteService $socialiteService,
    $provider
  ) {
    $this->validate($request, [
      'userAccessToken' => 'required|string|max:512',
      'tudcTicket'      => 'string|nullable|max:512',
    ]);

    $user = $socialiteService->login(
      $request->request->get('userAccessToken'),
      $provider,
      true,
      $request->request->get('tudcTicket')
    );

    $request->session()->flash('loginFromSocialite', true);
    if ($user->isNewUser && $user->avatar) {
      $request->session()->flash('socialiteAvatar',  $user->avatar);
    }

    return $this->renderInfo('success', [
      'userId'      => $user->userId,
      'isNewUser'   => $user->isNewUser, 
      'nickname'    => $user->isNewUser ? $user->nickname : null,
      'avatar'      => $user->isNewUser ? $user->avatar : null,
    ]);
  }
}
