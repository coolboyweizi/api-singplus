<?php

namespace SingPlus\Services;

use SingPlus\Contracts\Users\Services\UserService as UserServiceContract;
use SingPlus\Contracts\Verifications\Services\VerificationService as VerificationServiceContract;
use SingPlus\Support\Helpers\Mobile as MobileHelper;
use SingPlus\Exceptions\AppException;
use SingPlus\Exceptions\Users\UserExistsException;
use SingPlus\Exceptions\Users\UserNotExistsException;
use SingPlus\Exceptions\Users\UserMobileBoundException;
use SingPlus\Exceptions\Users\UserMobileNotBoundException;

class VerificationService
{
  /**
   * @var UserServiceContract
   */
  private $userService;

  /**
   * @var VerificationServiceContract
   */
  private $verificationService;

  public function __construct(
    VerificationServiceContract $verificationService,
    UserServiceContract $userService
  ) {
    $this->verificationService = $verificationService;
    $this->userService = $userService;
  }

  /**
   * Send verify sms code for user register
   *
   * @return \stdClass      properties as below:
   *                        - code string         code for send to user
   *                        - interval int        after this seconds, send allowd
   */
  public function sendRegisterVerifyCode(int $countryCode, string $localMobile) : \stdClass
  {
    $mobile = MobileHelper::genE164NumberForSending($localMobile, $countryCode);

    if ($this->userService->userExists($mobile)) {
      throw new UserExistsException($mobile);
    }

    return $this->verificationService->sendCode($mobile, $mobile);
  }

  /**
   * Send verify code for user reset his/her password
   *
   * @return \stdClass      properties as below:
   *                        - code string         code for send to user
   *                        - interval int        after this seconds, send allowd
   */
  public function sendPasswordResetVerifyCode(int $countryCode, string $localMobile) : \stdClass
  {
    $mobile = MobileHelper::genMobileWithCountryCode($localMobile, $countryCode);

    if ( ! $this->userService->userExists($mobile)) {
      throw new UserNotExistsException();
    }

    $sendMobile = MobileHelper::genE164NumberForSending($localMobile, $countryCode);
    return $this->verificationService->sendCode($mobile, $sendMobile);
  }

  /**
   * Send verify sms code for user bind his/her mobile
   */
  public function sendMobileBindVerifyCode(
    string $userId,
    int $countryCode,
    string $localMobile
  ) : \stdClass {
    $user = $this->userService->fetchUser($userId);
    if ($user->getMobile()) {
      throw new UserMobileBoundException();
    }

    $mobile = MobileHelper::genMobileWithCountryCode($localMobile, $countryCode);

    if ($this->userService->userExists($mobile)) {
      throw new UserMobileBoundException(sprintf('%s aready bound by someone else', $mobile));
    }

    $sendMobile = MobileHelper::genE164NumberForSending($localMobile, $countryCode);
    return $this->verificationService->sendCode($mobile, $sendMobile);
  }

  /**
   * Send verify code for user un-bind his/her exist mobile,
   * when user change his/her mobile.
   * Then, user should fetch re-bind verify code for new mobile
   */
  public function sendMobileUnbindVerifyCode(string $userId) : \stdClass
  {
    $user = $this->userService->fetchUser($userId);
    if ( ! $user->getMobile()) {
      throw new UserMobileNotBoundException();
    }

    $localMobile = MobileHelper::genLocalMobile($user->getMobile(), $user->getCountryCode());
    $sendMobile = MobileHelper::genE164NumberForSending($localMobile, $user->getCountryCode());
    return $this->verificationService->sendCode($user->getMobile(), $sendMobile);
  }

  /**
   * Send verify code for user rebind his/her mobile.
   */
  public function sendMobileRebindVerifyCode(
    string $userId,
    int $countryCode,
    string $localMobile
  ) : \stdClass {
    $user = $this->userService->fetchUser($userId);
    if ( ! $user->getMobile()) {
      throw new UserMobileNotBoundException();
    }

    $mobile = MobileHelper::genMobileWithCountryCode($localMobile, $countryCode);
    if ($user->getMobile() == $mobile) {
      throw new AppException('mobile aready bound by you');
    }

    if ($this->userService->userExists($mobile)) {
      throw new UserMobileBoundException(sprintf('%s aready bound by someone else', $mobile));
    }

    $sendMobile = MobileHelper::genE164NumberForSending($localMobile, $countryCode);
    return $this->verificationService->sendCode($mobile, $sendMobile);
  }
}
