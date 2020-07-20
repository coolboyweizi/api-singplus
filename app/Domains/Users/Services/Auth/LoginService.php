<?php

namespace SingPlus\Domains\Users\Services\Auth;

use Auth;
use SingPlus\Contracts\Users\Services\Auth\LoginService as LoginServiceContract;
use SingPlus\Domains\Users\Models\User;

class LoginService implements LoginServiceContract
{
  /**
   * @see \Homer\Contracts\Users\Services\Auth\LoginService::login()
   */
  public function login(string $mobile, string $password, bool $remember) : bool {
    return Auth::attempt([
      'mobile'    => $mobile,
      'password'  => $password,
    ], $remember);
  }    

  /**
   * {@inheritdoc}
   */
  public function loginUsingUserId(string $userId, bool $remember) : \stdClass {
    $user = Auth::loginUsingId($userId, $remember);
    return (object) [
      'userId'  => $userId,
    ];
  }

  /**
   * @see \Homer\Contracts\Users\Services\Auth\LoginService::logout()
   */
  public function logout()
  {
    Auth::logout();
  }
}
