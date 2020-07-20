<?php

namespace SingPlus\Domains\Users\Services\Auth;

use Illuminate\Contracts\Hashing\Hasher;
use SingPlus\Contracts\Users\Services\Auth\RegisterService as RegisterServiceContract;
use SingPlus\Contracts\Users\Constants\User as UserConstant;
use SingPlus\Domains\Users\Models\User;
use SingPlus\Domains\Users\Models\SocialiteUser;
use SingPlus\Domains\Users\Models\TUDCUser;
use SingPlus\Domains\Users\Repositories\UserRepository;
use SingPlus\Domains\Users\Repositories\TUDCUserRepository;
use SingPlus\Domains\Users\Repositories\SocialiteUserRepository;

class RegisterService implements RegisterServiceContract
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
   * @var TUDCUserRepository
   */
  private $TUDCUserRepo;

  /**
   * @var SocialiteUserRepository
   */
  private $socialiteUserRepo;

  public function __construct(
    Hasher $hasher,
    UserRepository $userRepo,
    TUDCUserRepository $TUDCUserRepo,
    SocialiteUserRepository $socialiteUserRepo
  ) {
    $this->hasher = $hasher;
    $this->userRepo = $userRepo;
    $this->TUDCUserRepo = $TUDCUserRepo;
    $this->socialiteUserRepo = $socialiteUserRepo;
  }

  /**
   * @see SingPlus\Contracts\Users\Services\Auth\RegisterService::register()
   */
  public function register(string $countryCode, string $mobile, string $password) : string
  {
    $password = $this->hasher->make($password);

    $user = User::create([
      'mobile'        => $mobile,
      'country_code'  => $countryCode,
      'password'      => $password,
      'source'        => UserConstant::SOURCE_MOBILE,
    ]);

    return $user->id;
  }

  /**
   * {@inheritdoc}
   */
  public function registerFromSocialite(
    string $appChannel,
    string $socialiteUserId,
    string $userToken,
    string $provider,
    ?string $unionToken = null
  ) : \stdClass {
    if ($unionToken &&
        ($socialiteUser = $this->socialiteUserRepo->findOneByUnionToken($provider, $unionToken))
    ) {
      $userId = $socialiteUser->user_id;
    } else {
      $user = User::create([
        'country_code'      => User::DEFAULT_COUNTRY_CODE,
        'source'            => UserConstant::SOURCE_SOCIALITE,
      ]);
      $userId = $user->id;
    }

    $this->socialiteUserRepo
         ->upsertSocialiteUser(
            $appChannel, $userId, $provider, $socialiteUserId, $userToken, $unionToken 
         );

    return (object) [
      'userId'  => $userId,
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function registerFromTUDC(
    string $appChannel,
    string $tudcOpenid,
    string $tudcToken,
    string $countryCode,
    string $mobile,
    string $password
  ) : \stdClass {
    $user = $this->userRepo->findOneByMobile($mobile);
    // 当传音用户mobile和
    if ( ! $user) {
      $user = User::create([
        'mobile'            => $mobile,
        'country_code'      => $countryCode,
        'password'          => $password,
        'source'            => UserConstant::SOURCE_TUDC,
      ]);
    }

    $this->TUDCUserRepo
         ->upsertTUDCUser($appChannel, $user->id, $tudcOpenid, $tudcToken);

    return (object) [
      'userId'  => $user->id,
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function registerSyntheticUser(
    string $countryCode,
    string $mobile,
    string $password
  ) : string {
    $password = $this->hasher->make($password);

    $user = User::create([
      'mobile'        => $mobile,
      'country_code'  => $countryCode,
      'password'      => $password,
      'source'        => UserConstant::SOURCE_SYNTHETIC,
    ]);

    return $user->id;
  }
}
