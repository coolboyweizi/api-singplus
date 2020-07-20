<?php

namespace SingPlus\Services\Auth;

use SingPlus\Contracts\Users\Services\Auth\LoginService as LoginServiceContract;
use SingPlus\Contracts\Users\Services\UserService as UserServiceContract;
use SingPlus\Contracts\Users\Services\UserProfileService as UserProfileServiceContract;
use SingPlus\Support\Helpers\Mobile as MobileHelper;
use SingPlus\Exceptions\Users\UserAuthFailedException;
use SingPlus\Exceptions\Users\UserNotExistsException;

class LoginService
{
  /**
    * @var LoginServiceContract
    */
  private $loginService;

  /**
   * @var UserServiceContract
   */
  private $userService;

  /**
   * @var UserProfileServiceContract
   */
  private $userProfileService;

  public function __construct(
    LoginServiceContract $loginService,
    UserServiceContract $userService,
    UserProfileServiceContract $userProfileService
  ) {
    $this->loginService = $loginService;
    $this->userService = $userService;
    $this->userProfileService = $userProfileService;
  }

  /**
   * user login
   *
   * @param string $mobile
   * @param string $password
   * @param ?bool $remember   user login status will be keep indefinitely,
   *                          or until manually logout, if this value is true
   */
  public function login(int $countryCode, string $mobile, string $password, ?bool $remember) : \stdClass 
  {
    $mobile = MobileHelper::genMobileWithCountryCode($mobile, $countryCode);

    if ( ! $this->userService->userExists($mobile)) {
      throw new UserNotExistsException();
    }

    $success = $this->loginService->login($mobile, $password, $remember ?: false);
    if ( ! $success) {
      throw new UserAuthFailedException();
    }

    $user = $this->userService->fetchUserByMobile($mobile);
    $isNewUser = $this->userProfileService->isNewUser($user->userId);

    return (object) [
      'userId'    => $user->userId,
      'isNewUser' => $isNewUser,
    ];
  }

  /**
   * user logout
   */
  public function logout()
  {
    $this->loginService->logout();
  }
}
