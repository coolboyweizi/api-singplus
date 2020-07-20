<?php

namespace SingPlus\Domains\Users\Services;

use Illuminate\Support\Collection;
use Illuminate\Contracts\Hashing\Hasher;
use SingPlus\Contracts\Users\Services\UserService as UserServiceContract;
use SingPlus\Contracts\Users\Models\User as UserContract;
use SingPlus\Contracts\Users\Constants\User as UserConstant;
use SingPlus\Domains\Users\Repositories\UserRepository;
use SingPlus\Domains\Users\Repositories\SocialiteUserRepository;
use SingPlus\Domains\Users\Repositories\TUDCUserRepository;
use SingPlus\Domains\Users\Models\User;
use SingPlus\Domains\Users\Models\TUDCUser;
use SingPlus\Exceptions\AppException;
use SingPlus\Exceptions\Users\UserNotExistsException;
use SingPlus\Exceptions\Users\UserPasswordIncorrectException;
use SingPlus\Exceptions\Users\UserMobileNotBoundException;

class UserService implements UserServiceContract
{
  /**
   * @var Hasher
   */
  private $hasher;

  /**
   * @var UserRepository
   */
  private $userRepo;

  /**
   * @var SocialiteUserRepository
   */
  private $socialiteUserRepo;

  /**
   * @var TUDCUserRepository
   */
  private $TUDCUserRepo;

  public function __construct(
    Hasher $hasher,
    UserRepository $userRepo,
    SocialiteUserRepository $socialiteUserRepo,
    TUDCUserRepository $TUDCUserRepo
  ) {
    $this->hasher = $hasher;
    $this->userRepo = $userRepo;
    $this->socialiteUserRepo = $socialiteUserRepo;
    $this->TUDCUserRepo = $TUDCUserRepo;
  }

  /**
   * @see \SingPlus\Contracts\Users\Services\UserService::userExists()
   */
  public function userExists(string $mobile) : bool
  {
    return $this->userRepo->findOneByMobile($mobile) ? true : false;
  }

  /**
   * {@inheritdoc}
   */
  public function fetchUserByMobile(string $mobile) : ?\stdClass
  {
    $user = $this->userRepo->findOneByMobile($mobile);

    return $user ? (object) [
                      'userId'  => $user->id,
                      'source'  => object_get($user, 'source') ?: UserConstant::SOURCE_MOBILE,
                    ] : null;
  }

  /**
   * @see \SingPlus\Contracts\Users\Services\UserService::fetchUser()
   */
  public function fetchUser(string $userId) : ?UserContract
  {
    $user =  $this->userRepo->findOneById($userId);
    if ($user) {
      $user->isPasswordSet = $user->password ? true : false;
      $user->source = object_get($user, 'source');
      $user->createdAt = $user->created_at;
    }

    return $user;
  }

  /**
   * @see \SingPlus\Contracts\Users\Services\UserService::changeLoginPassword()
   */
  public function changeLoginPassword(
    string $userId,
    string $oldPassword,
    string $password
  ) {
    $user = $this->userRepo->findOneById($userId);    
    if ( ! $user) {
      throw new UserNotExistsException();
    }

    if ( ! $this->hasher->check($oldPassword, $user->password)) {
      throw new UserPasswordIncorrectException();
    }

    $user->password = $this->hasher->make($password);
    $user->save();
  }

  /**
   * @see \SingPlus\Contracts\Users\Services\UserService::initLoginPassword()
   */
  public function initLoginPassword(string $userId, string $password)
  {
    $user = $this->userRepo->findOneById($userId);
    if ( ! $user) {
      throw new UserNotExistsException();
    }

    if ( ! $user->mobile) {
      throw new UserMobileNotBoundException();
    }

    if ($user->password) {
      throw new AppException('password aready init');
    }
    $user->password = $this->hasher->make($password);
    $user->save();
  }

  /**
   * @see \SingPlus\Contracts\Users\Services\UserService::resetPassword()
   */
  public function resetPassword(string $mobile, string $password)
  {
    $user = $this->userRepo->findOneByMobile($mobile);
    $user->password = $this->hasher->make($password);
    $user->save();
  }

  /**
   * @see \SingPlus\Contracts\Users\Services\UserService::bindMobile()
   */
  public function bindMobile(string $userId, int $countryCode, string $mobile)
  {
    $user = $this->userRepo->findOneById($userId);
    $user->mobile = $mobile;
    $user->country_code = $countryCode;
    $user->save();
  }

  /**
   * {@inheritdoc}
   */
  public function fetchUserFromSocialite(
    string $appChannel,
    string $socialiteUserId,
    string $provider
  ) : ?\stdClass {
    $socialiteUser = $this->socialiteUserRepo
                          ->findOneByProvider($appChannel, $socialiteUserId, $provider);
    return $socialiteUser ? (object) [
                                      'userId'          => $socialiteUser->user_id,
                                      'socialiteUserId' => object_get($socialiteUser, 'socialite_user_id'),
                                     ] : null;
  }

  /**
   * {@inheritdoc}
   */
  public function fetchUserFromTUDC(string $appChannel, string $tudcOpenid) : ?\stdClass
  {
    $tudcUser = $this->TUDCUserRepo
                     ->findOneByOpenid($appChannel, $tudcOpenid);

    return $tudcUser ? (object) [
                                  'userId'  => $tudcUser->user_id,
                                ] : null;
  }

  /**
   * {@inheritdoc}
   */
  public function fetchSocialiteUsers(
    string $appChannel,
    array $socialiteUserIds,
    string $provider
  ) : Collection {
    return $this->socialiteUserRepo
                ->findAllBySocialiteUserIdAndProvider($appChannel, $socialiteUserIds, $provider)
                ->map(function ($user, $_) use ($appChannel) {
                   // 注：这里查询socialite_user_id是为了兼容老数据，老数据同步为新数据
                   //     需要时间
                   $key = sprintf('channels.%s.openid', $appChannel);
                   $openid = object_get($user, 'socialite_user_id') ?:
                              array_get(object_get($user, 'channels', []), 'key');

                   return (object) [
                    'userId'          => $user->user_id,
                    'socialiteUserId' => $openid, 
                    'provider'        => $user->provider,
                  ];
                });
  }

  /**
   * {@inheritdoc}
   */
  public function updateUserTUDCInfo(
    string $appChannel,
    string $userId,
    string $tudcOpenid,
    string $tudcToken
  ) {
    return $this->TUDCUserRepo
                ->upsertTUDCUser($appChannel, $userId, $tudcOpenid, $tudcToken);
  }

  /**
   * {@inheritdoc}
   */
  public function bindUserPushAlias(string $appChannel, string $userId, ?string $alias)
  {
    $user = $this->userRepo->findOneById($userId);
    $aliasKeyName = $appChannel == config('tudc.defaultChannel')
                ? 'push_alias' : sprintf('%s_push_alias', $appChannel);
    $user->{$aliasKeyName} = $alias;
    return $user->save();
  }

  /**
   * {@inheritdoc}
   */
  public function getUserPushAlias(string $appChannel, string $userId) : ?string
  {
    $user = $this->userRepo->findOneById($userId);
    $aliasKeyName = $appChannel == config('tudc.defaultChannel')
                ? 'push_alias' : sprintf('%s_push_alias', $appChannel);

    return $user ? object_get($user, $aliasKeyName) : null;
  }

  /**
   * {@inheritdoc}
   */
  public function isPushAliasBound(string $appChannel, string $alias) : bool
  {
    $user = $this->userRepo->findOneByPushAlias($appChannel, $alias);
    return $user ? true : false;
  }

  /**
   * {@inheritdoc}
   */
  public function syncStaleSocialiteUserIntoChannel(
    string $appChannel,
    string $provider,
    string $userId,
    string $socialiteUserId,
    string $userAccessToken,
    ?string $unionToken
  ) {
    // 分渠道前，没有启用unionToken
    // 如果unionToken已经存在，说明该老用户先已经在其他渠道登录过，
    // 因此已经在系统存在对应的账户了，
    // 这时我们不应该对当前用户更新unionToken，因为会导致两个
    // 不同的用户在登录时被认为是同一个用户
    if ($unionToken) {
      $unionTokenUser = $this->socialiteUserRepo
                             ->findOneByUnionToken($provider, $unionToken);
      if ($unionTokenUser) {
        $unionToken = null;
      }
    }

    return $this->socialiteUserRepo
                ->upsertStaleSocialiteUser(
      $appChannel, $userId, $provider, $socialiteUserId, $userAccessToken, $unionToken
    );
  }

  private function checkPassword(string $password, User $user) : bool
  {
    return $this->hasher->check($password, $user->password);
  }
}
