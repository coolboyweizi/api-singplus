<?php

namespace SingPlus\Services\Auth;

use Log;
use Socialite;
use SingPlus\Support\Helpers\Str;
use SingPlus\Contracts\Users\Services\Auth\LoginService as LoginServiceContract;
use SingPlus\Contracts\Users\Services\Auth\RegisterService as RegisterServiceContract;
use SingPlus\Contracts\Users\Services\UserService as UserServiceContract;
use SingPlus\Contracts\Users\Services\UserProfileService as UserProfileServiceContract;
use SingPlus\Exceptions\AppException;
use SingPlus\Exceptions\Users\SocialiteUserNotExistsException;
use SingPlus\Jobs\FreshTUDCUser as FreshTUDCUserJob;
use SingPlus\Jobs\SyncStaleSocialiteUserIntoChannel as SyncStaleSocialiteUserIntoChannelJob;

class SocialiteService
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

  public function __construct(
    LoginServiceContract $loginService,
    RegisterServiceContract $registerService,
    UserServiceContract $userService,
    UserProfileServiceContract $userProfileService
  ) {
    $this->loginService = $loginService;
    $this->registerService = $registerService;
    $this->userService = $userService;
    $this->userProfileService = $userProfileService;
  }

  /**
   * socialite login
   *
   * @param string $userAccessToken   user socialite token
   * @param string $provider
   * @param ?bool $remember
   * @param ?string $tudcTicket
   */
  public function login(
    string $userAccessToken,
    string $provider,
    ?bool $remember,
    ?string $tudcTicket = null
  ) : \stdClass {
    // force logout
    $this->loginService->logout();

    $appChannel = config('tudc.currentChannel');
    $driver = $appChannel == config('tudc.defaultChannel')
                ? $provider : sprintf('%s_%s', $provider, $appChannel);
    try {
      $socialiteUser = Socialite::driver($driver)->userFromToken($userAccessToken);
    } catch (\Exception $ex) {
        Log::error('facebook error', [
                                        'parms' => [
                                            'userAccessToken'   => $userAccessToken,
                                            'provider'          => $provider,
                                            'tudcTicket'        => $tudcTicket,
                                        ],
                                        'appChannel'    => $appChannel,
                                        'driver'        => $driver,
                                        'exception' => [
                                            'message' => $ex->getMessage(),
                                            'file' => $ex->getFile(),
                                            'line' => $ex->getLine(),
                                            'trace' => $ex->getTrace()
                                         ]]);
      throw new AppException('fetch user socialite failed');
    }
    if ( ! $socialiteUser->getId()) {
      throw new SocialiteUserNotExistsException();  
    }

    $userInfo = (object) [
      'nickname'  => null,
      'avatar'    => null,
    ];

    // check current user exists or not
    $soUser = $this->userService
                 ->fetchUserFromSocialite(
                   $appChannel, $socialiteUser->getId(), $provider
                 );
    if ( ! $soUser) {
      $soUser = $this->registerService
                   ->registerFromSocialite(
                      $appChannel, $socialiteUser->getId(), $userAccessToken, $provider,
                      object_get($socialiteUser, 'unionToken')
                   );
    }

    $userInfo->userId = $soUser->userId;
    $userInfo->isNewUser = $this->userProfileService->isNewUser($soUser->userId);
    if ($userInfo->isNewUser) {
      $nickname = $socialiteUser->getName();
      if ($nickname) {
        if ($this->userProfileService->isNickNameUsedByOther($soUser->userId, $nickname)) {
          $nickname = $nickname . Str::quickRandom(3, '123456789');
        }
      }
      
      $userInfo->nickname = $nickname;
      $userInfo->avatar = $socialiteUser->getAvatar();
    }

    // auto login
    $this->loginService->loginUsingUserId($userInfo->userId, $remember ?: false);

    // auto complete userprofile
    $this->userProfileService->autoCompleteUserProfile($userInfo->userId, null, null);

    // job
    if ($tudcTicket) {
      dispatch(new FreshTUDCUserJob($userInfo->userId, $tudcTicket, $appChannel));
    }

    $staleSocialiteUserId = object_get($soUser, 'socialiteUserId');
    if ($staleSocialiteUserId == $socialiteUser->getId()) {
      dispatch(new SyncStaleSocialiteUserIntoChannelJob(
          $userInfo->userId, $socialiteUser->getId(),
          $userAccessToken, object_get($socialiteUser, 'unionToken')));
    }

    return $userInfo;
  }
}
