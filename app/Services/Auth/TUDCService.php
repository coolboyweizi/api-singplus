<?php 
namespace SingPlus\Services\Auth;

use Log;
use SingPlus\Contracts\Users\Services\Auth\LoginService as LoginServiceContract;
use SingPlus\Contracts\Users\Services\Auth\RegisterService as RegisterServiceContract;
use SingPlus\Contracts\Users\Services\UserService as UserServiceContract;
use SingPlus\Contracts\Users\Services\UserProfileService as UserProfileServiceContract;
use SingPlus\Contracts\TUDC\Services\Service as TUDCServiceContract;
use SingPlus\Support\Helpers\Mobile as MobileHelper;
use SingPlus\Exceptions\Users\UserAuthFailedException;
use SingPlus\Exceptions\AppException;
use SingPlus\Support\Helpers\Str;

class TUDCService
{
  /**
   * @var LoginServiceContract
   */
  private $loginService;

  /**
   * @var RegisterServiceContract
   */
  private $registerService;

  /**
   * @var UserServiceContract
   */
  private $userService;

  /**
   * @var UserProfileServiceContract
   */
  private $userProfileService;

  /**
   * @var TUDCServiceContract
   */
  private $TUDCService;

  public function __construct(
    LoginServiceContract $loginService,
    RegisterServiceContract $registerService,
    UserServiceContract $userService,
    UserProfileServiceContract $userProfileService,
    TUDCServiceContract $TUDCService
  ) {
    $this->loginService = $loginService;
    $this->registerService = $registerService;
    $this->userService = $userService;
    $this->userProfileService = $userProfileService;
    $this->TUDCService = $TUDCService;
  }

  /**
   * TUDC login
   *
   * @param int $countryCode
   * @param string $mobile
   * @param string $password
   * @param string $tudcTicket
   * @param ?string $remember
   */
  public function login(
    int $countryCode,
    string $mobile,
    string $password,
    string $tudcTicket,
    ?string $remember
  ) : \stdClass {
    // force logout
    $this->loginService->logout();

    $appChannel = config('tudc.currentChannel');
    try {
      $tudcUser = $this->TUDCService->verifyTicket($appChannel, $tudcTicket);
    } catch (\Exception $ex) {
        Log::error('tudc verify ticket error', [
                                        'parms' => [
                                            'mobile'            => $mobile,
                                            'tudcTicket'        => $tudcTicket,
                                        ],
                                        'appChannel'    => $appChannel,
                                        'exception' => [
                                            'message' => $ex->getMessage(),
                                            'file' => $ex->getFile(),
                                            'line' => $ex->getLine(),
                                            'trace' => $ex->getTrace()
                                         ]]);
      throw new AppException('fetch tudc user failed');
    }

    if ( ! $tudcUser) {
      throw new UserAuthFailedException('tudc user login failed');
    }

    $mobile = MobileHelper::genMobileWithCountryCode($mobile, $countryCode);
    $user = $this->userService->fetchUserFromTUDC($appChannel, $tudcUser->openid);
    if ( ! $user) {
      $user = $this->registerService
                   ->registerFromTUDC(
                    $appChannel,
                    $tudcUser->openid,
                    $tudcUser->token,
                    $countryCode,
                    $mobile,
                    $password);
    } else {
      $this->userService
           ->updateUserTUDCInfo($appChannel, $user->userId, $tudcUser->openid, $tudcUser->token);
    }
    $isNewUser = $this->userProfileService->isNewUser($user->userId);

    // auto login
    $this->loginService->loginUsingUserId($user->userId, $remember ?: false);

    // auto complete user nickname 保证客户端在调用autocomplete info 失败的时候，是
    if ($isNewUser)
    {
      $this->userProfileService->autoCompleteUserProfile($user->userId, null, null);
    }

    return (object) [
      'userId'      => $user->userId,
      'isNewUser'   => $isNewUser,
      'tudcOpenid'  => $tudcUser->openid,
    ];
  }

  /**
   * Update user's tudc info
   *
   * @param string $appChannel     such as singplus, boomsing
   * @param string $userId
   * @param string $tudcTicket
   */
  public function updateUserTUDCInfo(
    string $appChannel,
    string $userId,
    string $tudcTicket
  ) : bool {
    try {
      $tudcUser = $this->TUDCService->verifyTicket($appChannel, $tudcTicket);
    } catch (\Exception $ex) {
      throw new AppException('fetch tudc user failed');
    }

    if ( ! $tudcUser) {
      return false;
    }

    return $this->userService
                ->updateUserTUDCInfo($appChannel, $userId, $tudcUser->openid, $tudcUser->token); 
  }
}
