<?php

namespace SingPlus\Services\Auth;

use SingPlus\Contracts\Users\Services\Auth\RegisterService as RegisterServiceContract;
use SingPlus\Contracts\Users\Services\Auth\LoginService as LoginServiceContract;
use SingPlus\Contracts\Users\Services\UserProfileService as UserProfileContract;
use SingPlus\Contracts\Users\Services\UserService as UserServiceContract;
use SingPlus\Contracts\Verifications\Services\VerificationService as VerificationServiceContract;
use SingPlus\Exceptions\Users\UserExistsException;
use SingPlus\Support\Helpers\Mobile as MobileHelper;
use SingPlus\Support\Helpers\Str;

class RegisterService
{
  /**
   * @var VerificationService
   */
  private $verificationService;

  /**
   * @var RegisterServiceContract
   */
  private $registerService;

  /**
   * @var UserService;
   */
  private $userService;

  /**
   * @var LoginServiceContract
   */
  private $loginService;

  /**
   * @var UserProfileServiceContract
   * */
  private $userProfileService;

  public function __construct(
    VerificationServiceContract $verificationService,
    RegisterServiceContract $registerService,
    LoginServiceContract $loginService,
    UserServiceContract $userService,
    UserProfileContract $userProfileService
  ) {
    $this->verificationService = $verificationService;
    $this->registerService = $registerService;
    $this->userService = $userService;
    $this->loginService = $loginService;
    $this->userProfileService = $userProfileService;
  }

  /**
   * user register logic
   *
   * @param int $countryCode  country code
   * @param string $mobile    user mobile
   * @param string $password
   * @param string $code      sms verification code
   */
  public function register(int $countryCode, string $mobile, string $password, string $code) : \stdClass
  {
    $mobile = MobileHelper::genE164NumberForSending($mobile, $countryCode);

    $this->verificationService->verify($mobile, $code);

    if ($this->userService->userExists($mobile)) {
      throw new UserExistsException($mobile);
    }

    $userId = $this->registerService->register($countryCode, $mobile, $password);

    // auto login
    $this->loginService->loginUsingUserId($userId, true);

    // auto complete user nickname ，确保客户端再调用自动完善昵称和头像的失败的时候，昵称是有的。
    $this->userProfileService->autoCompleteUserProfile($userId, null, null);

    return (object) [
      'userId'  => $userId,
    ];
  }
}
