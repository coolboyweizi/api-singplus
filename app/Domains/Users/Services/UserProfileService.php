<?php

namespace SingPlus\Domains\Users\Services;

use Carbon\Carbon;
use Illuminate\Support\Collection;
use SingPlus\Contracts\Hierarchy\Constants\PopularityCoefs;
use SingPlus\Contracts\Hierarchy\Constants\WealthCoefs;
use SingPlus\Contracts\Users\Models\UserProfile as UserProfileContract;
use SingPlus\Contracts\Users\Services\UserProfileService as UserProfileServiceContract;
use SingPlus\Domains\Hierarchy\Models\Hierarchy;
use SingPlus\Domains\Users\Repositories\UserProfileRepository;
use SingPlus\Domains\Users\Repositories\UserImageRepository;
use SingPlus\Domains\Users\Repositories\UserVerificationRepository;
use SingPlus\Domains\Hierarchy\Repositories\HierarchyRepository;
use SingPlus\Domains\users\Models\UserProfile;
use SingPlus\Exceptions\AppException;
use SingPlus\Support\Helpers\Str;

class UserProfileService implements UserProfileServiceContract
{
  /**
   * @var UserProfileRepository
   */
  private $userProfileRepo;

  /**
   * @var UserImageRepository
   */
  private $userImageRepo;

  /**
   * @var HierarchyRepository
   */
  private $hierarchyRepo;

  /**
   * @var UserVerificationRepository
   */
  private $userVerificationRepo;

  public function __construct(
    UserProfileRepository $userProfileRepo,
    UserImageRepository $userImageRepo,
    HierarchyRepository $hierarchyRepository,
    UserVerificationRepository $userVerificationRepo
  ) {
    $this->userProfileRepo = $userProfileRepo;
    $this->userImageRepo = $userImageRepo;
    $this->hierarchyRepo = $hierarchyRepository;
    $this->userVerificationRepo = $userVerificationRepo;
  }

  /**
   * @see SingPlus\Contracts\Users\Services\UserProfileService::fetchUserProfile()
   */
  public function fetchUserProfile(string $userId) : ?UserProfileContract
  {
    $profile = $this->userProfileRepo->findOneByUserId($userId);
    if ( ! $profile) {
        return null;
    }
    $listenCount = (int) object_get($profile, 'worker_listen_count');
    $workCount  = (int) object_get($profile, 'work_count');
    $workChorusStartCount = array_get(object_get($profile, 'statistics_info',[]), 'work_chorus_start_count', 0);

    $popularityHierarchys = $this->hierarchyRepo->findAllByType(Hierarchy::TYPE_USER);
    $wealthHierarchys = $this->hierarchyRepo->findAllByType(Hierarchy::TYPE_WEALTH);
    $popularityHierarchy= PopularityCoefs::checkPopularityHierarchyInfo($profile, $popularityHierarchys->toArray());
    $wealthHerarchy = WealthCoefs::checkWealthHierarchyInfo($profile, $wealthHierarchys->toArray());

    $profile->work_listen_count = $listenCount;

    $userHierarchyDetail = $popularityHierarchys->where('_id', $popularityHierarchy->hierarchyId)->first();
    $wealthHierarchyDetail = $wealthHierarchys->where('_id', $wealthHerarchy->hierarchyId)->first();
    $profile->popularity_herarchy = (object)[
        'name' => $userHierarchyDetail ? $userHierarchyDetail->name : '',
        'icon' => $userHierarchyDetail ? $userHierarchyDetail->icon : '',
        'iconSmall' => $userHierarchyDetail ? $userHierarchyDetail->icon_small : '',
        'alias' => $userHierarchyDetail ? $userHierarchyDetail->alias : '',
        'gapPopularity' => $popularityHierarchy->gapPopularity,
        'popularity' => $popularityHierarchy->popularity
    ];
    $profile->wealth_herarchy = (object)[
        'name' => $wealthHierarchyDetail ? $wealthHierarchyDetail->name : '',
        'icon' => $wealthHierarchyDetail ? $wealthHierarchyDetail->icon : '',
        'iconSmall' => $wealthHierarchyDetail ? $wealthHierarchyDetail->icon_small : '',
        'alias' => $wealthHierarchyDetail ? $wealthHierarchyDetail->alias : '',
        'gapCoins' => $wealthHerarchy->gapCoins,
        'consumeCoins' => $wealthHerarchy->consumeCoins
    ];
    $profile->work_count = $workCount;
    $profile->work_chorus_start_count = $workChorusStartCount;
    $verification = $this->userVerificationRepo->findOneByUserId($userId);
    $profile->verified = (object) [
        'verified'  => $verification ? true : false,
        'names'     => $verification ? object_get($verification, 'verified_as', []) : [],
    ];
    return $profile;
  }

  /**
   * @see SingPlus\Contracts\Users\Services\UserProfileService::modifyUserProfile()
   */
  public function modifyUserProfile(
    string $userId,
    ?string $nickname = null,
    ?string $gender = null,
    ?string $signature = null,
    ?string $avatar = null,
    ?string $birthDate = null
  ) {
    $profile = $this->userProfileRepo->findOneByUserId($userId);
    if (empty($profile)) {
      $profile = new UserProfile([
        'user_id' => $userId, 
      ]);
    }

    if ( ! is_null($gender) && ! is_null($profile->getGender())) {
      throw new AppException('gender can be set only once', null);
    }

    if ($nickname) {
      $profile->nickname = $nickname;
    }
    if ( ! is_null($gender)) {
      $profile->gender = $gender;
    }
    if ( ! is_null($signature)) {
      $profile->signature = $signature;
    }
    if ( ! is_null($avatar)) {
      $profile->avatar = $avatar;
    }
    if ( ! is_null($birthDate)) {
      $profile->birth_date = $birthDate;
    }

    $profile->save();
  }

  /**
   * {@inheritdoc}
   */
  public function completeUserProfile(
    string $userId,
    string $nickname
  ) {
    $profile = $this->userProfileRepo->findOneByUserId($userId);
    if ($profile) {
      $profile->is_new = false;
      $profile->nickname = $nickname;
    } else {
      $profile = new UserProfile([
        'user_id'   => $userId,
        'nickname'  => $nickname,
        'is_new'    => false,
      ]);
    }

    $profile->save();
  }

  /**
   * {@inheritdoc}
   */
  public function isNickNameUsedByOther(string $userId, string $nickname) : bool
  {
    $profile = $this->userProfileRepo->findOneByNickname($nickname);
    if ( ! $profile || $profile->user_id == $userId) {
      return false;
    }

    return true;
  }

  /**
   * {@inheritdoc}
   */
  public function isNickNameUsed(string $nickname) : bool
  {
    $profile = $this->userProfileRepo->findOneByNickname($nickname);

    return $profile ? true : false;
  }


  public function getUserSimpleProfiles(array $userIds) : Collection{
      return $this->userProfileRepo->findAllByUserIds($userIds)
          ->map(function ($profile, $_){
              return (object)[
                  'userId'    => $profile->user_id,
                  'nickname'  => $profile->nickname,
                  'avatar'    => $profile->avatar,
              ];
          });
  }

  /**
   * @see SingPlus\Contracts\Users\Services\UserProfileService::getUserProfiles()
   */
  public function getUserProfiles(array $userIds) : Collection
  {
    $popularityHierarchys = $this->hierarchyRepo->findAllByType(Hierarchy::TYPE_USER);
    $wealthHierarchys = $this->hierarchyRepo->findAllByType(Hierarchy::TYPE_WEALTH);
    $verifications = $this->userVerificationRepo
                          ->findAllByUserIds($userIds);

    return $this->userProfileRepo->findAllByUserIds($userIds)
                                 ->map(function ($profile, $_) use ($popularityHierarchys, $wealthHierarchys, $verifications) {

                                   $verification = $verifications->where('user_id', $profile->user_id)->first();
                                   $popularityHierarchy= PopularityCoefs::checkPopularityHierarchyInfo($profile, $popularityHierarchys->toArray());
                                   $wealthHerarchy = WealthCoefs::checkWealthHierarchyInfo($profile, $wealthHierarchys->toArray());
                                   $userHierarchyDetail = $popularityHierarchys->where('_id', $popularityHierarchy->hierarchyId)->first();
                                   $wealthHierarchyDetail = $wealthHierarchys->where('_id', $wealthHerarchy->hierarchyId)->first();

                                    return (object) [
                                      'userId'    => $profile->user_id,
                                      'nickname'  => $profile->nickname,
                                      'gender'    => $profile->gender,
                                      'avatar'    => $profile->avatar,
                                      'signature' => $profile->signature,
                                      'birthDate' => $profile->birth_date,
                                      'popularity_herarchy' => (object)[
                                          'name' => $userHierarchyDetail ? $userHierarchyDetail->name : '',
                                          'icon' => $userHierarchyDetail ? $userHierarchyDetail->icon : '',
                                          'iconSmall' => $userHierarchyDetail ? $userHierarchyDetail->icon_small : '',
                                          'alias' => $userHierarchyDetail ? $userHierarchyDetail->alias : '',
                                          'gapPopularity' => $popularityHierarchy->gapPopularity,
                                          'popularity' => $popularityHierarchy->popularity
                                      ],
                                      'wealth_herarchy' => (object)[
                                          'name' => $wealthHierarchyDetail ? $wealthHierarchyDetail->name : '',
                                          'icon' => $wealthHierarchyDetail ? $wealthHierarchyDetail->icon : '',
                                          'iconSmall' => $wealthHierarchyDetail ? $wealthHierarchyDetail->icon_small : '',
                                          'alias' => $wealthHierarchyDetail ? $wealthHierarchyDetail->alias : '',
                                          'gapCoins' => $wealthHerarchy->gapCoins,
                                          'consumeCoins' => $wealthHerarchy->consumeCoins
                                      ],
                                      'verified' => (object) [
                                        'verified'  => $verification ? true : false,
                                        'names'     => $verification ? object_get($verification, 'verified_as', []) : [],
                                      ],
                                    ];
                                 });
  }

  /**
   * {@inheritdoc}
   */
  public function isNewUser(string $userId) : bool
  {
    $profile = $this->userProfileRepo->findOneByUserId($userId);

    return $profile && $profile->is_new === false ? false : true;
  }

  /**
   * {@inheritdoc}
   */
  public function updateUserFollowingCount(string $userId, bool $isIncrement) : bool
  {
    $profile = $this->userProfileRepo->findOneByUserId($userId);
    if ( ! $profile) {
      return false;
    }

    if ($isIncrement) {
      return $profile->increment('following_count') > 0;
    } else {
      return $profile->decrement('following_count') > 0;
    }
  }

  /**
   * {@inheritdoc}
   */
  public function updateUserFollowerCount(string $userId, bool $isIncrement) : bool
  {
    $profile = $this->userProfileRepo->findOneByUserId($userId);
    if ( ! $profile) {
      return false;
    }

    if ($isIncrement) {
      return $profile->increment('follower_count');
    } else {
      return $profile->decrement('follower_count');
    }
  }

  /**
   * {@inheritdoc}
   */
  public function searchUsersByNickname(string $nickname, int $size) : Collection
  {
    $preciseProfile = $this->userProfileRepo->findOneByNickname($nickname);
    $profiles = $this->userProfileRepo
                ->findAllBySearchNickname($nickname, $size)
                ->map(function ($profile, $_) use ($preciseProfile) {
                  if ($preciseProfile && $preciseProfile->user_id == $profile->user_id) {
                    return null;
                  }
                  return (object) [
                    'userId'      => $profile->user_id,
                    'nickname'    => $profile->nickname,
                    'avatar'      => $profile->avatar,
                  ];
                })->filter(function ($profile, $_) {
                  return ! is_null($profile);
                });
    if ($preciseProfile) {
      $profiles->prepend((object) [
        'userId'    => $preciseProfile->user_id,
        'nickname'  => $preciseProfile->nickname,
        'avatar'    => $preciseProfile->avatar,
      ]);
    }

    return $profiles;
  }

  /**
   * {@inheritdoc}
   */
  public function reportUserLocation(
    string $userId,
    ?string $longitude,
    ?string $latitude,
    ?string $countryCode,
    ?string $abbreviation,
    ?string $city,
    ?string $countryName,
    bool $auto
  ) {
    $profile = $this->userProfileRepo->findOneByUserId($userId);
    if ( ! $profile) {
      return; 
    }

    //todo 如果手动设置过后，自动上报的位置信息就忽略
    $modifiedByUser = array_get(isset($profile->location) ? $profile->location: [], 'modified_by_user', false);
    if ($modifiedByUser && $auto){
        return;
    }

    $location = [
      'longitude'     => $longitude,
      'latitude'      => $latitude,
      'country_code'  => $countryCode,
      'modified_at'   => Carbon::now()->format('Y-m-d H:i:s'),
    ];

    if (!$auto){
        $location['modified_by_user'] = true;
        $location['city'] = $city;
        $location['country_name'] = $countryName;
        $location['abbreviation']  = $abbreviation;
    }

    if ($auto){
        // abbreviation 只记录一次
        $currentAbbr = array_get(isset($profile->location) ? $profile->location : [], 'abbreviation');
        $location['abbreviation']  = $currentAbbr ?: $abbreviation;
    }

    $profile->location = $location;
    $profile->save();
  }

  /**
   * {@inheritdoc}
   */
  public function getUserLocation(string $userId) : ?\stdClass
  {
    $profile = $this->userProfileRepo->findOneByUserId($userId);
    if ( ! $profile) {
      return null;
    }

    if ( ! isset($profile->location)) {
      return null;
    }

    $location = (object) $profile->location;
    return (object) [
      'longitude'     => object_get($location, 'longitude'),
      'latitude'      => object_get($location, 'latitude'),
      'countryCode'   => object_get($location, 'country_code'),
      'abbreviation'  => object_get($location, 'abbreviation'),
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function updateUserLastVisitInfo(string $userId, \stdClass $info)
  {
    return $this->userProfileRepo
                ->updateLastVisitInfo($userId, $info->version, $info->lastVisitedAt);
  }

  /**
   * {@inheritdoc}
   */
  public function updateUserLatestWorkPublishedInfo(
    string $userId,
    string $workId,
    Carbon $workPublishedAt
  ) {
    return $this->userProfileRepo
                ->updateUserLatestWorkPublishedInfo($userId, $workId, $workPublishedAt);
  }

    /**
     * Auto Complete user profile
     *
     * if avatarImageId and avatar exists both, only use avatarImageId
     *
     * @param string $userId
     *
     */
    public function autoCompleteUserProfile(
        string $userId,
        ?string $nick,
        ?string $avatar
    )
    {
        $nickname = $nick;
        if ($nickname == null)
        {
            $nickname = Str::generateNickName(9);
            for ($i = 0; $i < 10; $i++)
            {
                if ($this->isNickNameUsedByOther($userId, $nickname))
                {
                    $nickname = Str::generateNickName(9);
                }else {
                    break;
                }
            }
            if ($this->isNickNameUsedByOther($userId, $nickname)){
                $nickname = "Sing_".Str::randUniqStr(10);
            }
        }
        
        $profile = $this->userProfileRepo->findOneByUserId($userId);
        if ($profile) {
            $profile->is_new = false;
        } else {
            $profile = new UserProfile([
                'user_id'   => $userId,
                'nickname'  => $nickname,
                'is_new'    => false,
                'avatar'    => $avatar,
            ]);
        }

        $profile->save();

    }

    /**
     * @param string $userId
     * @param bool $isSync
     * @return int
     */
    public function updateUserImStatus(string $userId, bool $isSync): int
    {
        $profile = $this->userProfileRepo->findOneByUserId($userId);
        if ($profile && $isSync){
            $profile->im_sync = 1;
            $profile->save();
            return 1;
        }else {
            return 0;
        }
    }

    /**
     * @param string $userId
     * @param string $prefName
     * @param bool $on
     * @return mixed
     */
    public function updateUserPreference(string $userId, string $prefName, bool $on)
    {
        return $this->userProfileRepo->updatePreferencConf($userId, $prefName, $on);
    }
}
