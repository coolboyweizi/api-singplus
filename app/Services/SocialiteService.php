<?php

namespace SingPlus\Services;

use SingPlus\Contracts\Users\Services\UserService;

class SocialiteService
{
  /** 
   * @var UserService
   */
  private $userService;

  public function __construct(
    UserService $userService
  ) {
    $this->userService = $userService;
  }

  /**
   * sync stale socialite user id into channel fields
   *
   * @param string $userId
   * @param string $socialiteUserId
   * @param string $userAccessToken
   * @param ?string $unionToken
   */
  public function syncStaleSocialiteUserIntoChannel(
    string $userId,
    string $socialiteUserId,
    string $userAccessToken,
    ?string $unionToken
  ) {
    return $this->userService->syncStaleSocialiteUserIntoChannel(
      'singplus', 'facebook', $userId, $socialiteUserId,
      $userAccessToken, $unionToken
    );
  }
}
