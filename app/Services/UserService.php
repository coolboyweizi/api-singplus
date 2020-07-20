<?php

namespace SingPlus\Services;

use Carbon\Carbon;
use Cache;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Collection;
use SingPlus\Contracts\Users\Services\UserService as UserServiceContract;
use SingPlus\Contracts\Users\Services\UserProfileService as UserProfileServiceContract;
use SingPlus\Contracts\Users\Services\UserImageService as UserImageServiceContract;
use SingPlus\Contracts\Users\Services\Auth\RegisterService as RegisterServiceContract;
use SingPlus\Contracts\Storages\Services\StorageService as StorageServiceContract;
use SingPlus\Contracts\Verifications\Services\VerificationService as VerificationServiceContract;
use SingPlus\Contracts\Works\Services\WorkService as WorkServiceContract;
use SingPlus\Contracts\Friends\Services\FriendService as FriendServiceContract;
use SingPlus\Contracts\Users\Constants\User as UserConstant;
use SingPlus\Support\Helpers\Mobile as MobileHelper;
use SingPlus\Exceptions\AppException;
use SingPlus\Exceptions\Users\UserMobileBoundException;
use SingPlus\Exceptions\Users\UserMobileNotBoundException;
use SingPlus\Exceptions\Users\UserExistsException;
use SingPlus\Exceptions\Users\UserNotExistsException;
use SingPlus\Exceptions\Users\UserImageUploadFailedException;
use SingPlus\Exceptions\Users\UserNicknameBeUsedByOtherException;
use SingPlus\Events\UserImageUploaded as EventUserImageUploaded;
use SingPlus\Jobs\SaveAuthUserLastVisitInfo as SaveAuthUserLastVisitInfoJob;
use SingPlus\Support\Helpers\Str;

class UserService
{
  /**
   * @var UserServiceContract
   */
  private $userService;

  /**
   * @var UserProfileServiceContract
   */
  private $userProfileService;

  /**
   * @var VerificationServiceContract
   */
  private $verificationService;

  /**
   * @var StorageServiceContract
   */
  private $storageService;

  /**
   * @var UserImageServiceContract
   */
  private $userImageService;

  /**
   * @var WorkServiceContract
   */
  private $workService;

  /**
   * @var FriendServiceContract
   */
  private $friendService;

  /**
   * @var RegisterServiceContract
   */
  private $registerService;

  public function __construct(
    UserServiceContract $userService,
    UserProfileServiceContract $userProfileService,
    FriendServiceContract $friendService,
    VerificationServiceContract $verificationService,
    StorageServiceContract $storageService,
    UserImageServiceContract $userImageService,
    WorkServiceContract $workService,
    RegisterServiceContract $registerService
  ) {
    $this->userService = $userService;
    $this->userProfileService = $userProfileService;
    $this->verificationService = $verificationService;
    $this->storageService = $storageService;
    $this->userImageService = $userImageService;
    $this->workService = $workService;
    $this->friendService = $friendService;
    $this->registerService = $registerService;
  }

  /**
   * User reset his/her password
   *
   * @param int $countryCode
   * @param string $localMobile   mobile without country code part
   * @param string $password      new password will be set
   * @param string $code          sms verify code
   */
  public function resetPassword(
    int $countryCode,
    string $localMobile,
    string $password,
    string $code
  ) {
    $mobile = MobileHelper::genMobileWithCountryCode($localMobile, $countryCode);

    if ( ! $this->userService->userExists($mobile)) {
      throw new UserNotExistsException();
    }

    $this->verificationService->verify($mobile, $code);
    $this->userService->resetPassword($mobile, $password);
  }

  /**
   * Chage user password
   */
  public function changeLoginPassword(
    string $userId,
    string $oldPassword,
    string $password
  ) {
    $this->userService->changeLoginPassword($userId, $oldPassword, $password);
  }

  /**
   * Get user profile
   */
  public function getUserProfile(string $userId, string $targetUserId) : \stdClass
  {
    $userProfile = null;
    $user = $this->userService->fetchUser($targetUserId);
    if ($user) {
      $userProfile = $this->userProfileService->fetchUserProfile($targetUserId);
      $userProfile->avatar = $this->storageService->toHttpUrl($userProfile->avatar);
      // fetch work listen number
      $userProfile->workListenNum = $userProfile->work_listen_count + $this->workService->getUserWorkListenNum($targetUserId);

      $friend = null;

      if ($userId != NULL && $userId != $targetUserId) {
        $friend = $this->friendService
                       ->getUserRelationship($userId, [$targetUserId])
                       ->first();
      }

      $userProfile->friend = $friend ? (object) [
                          'isFollowing' => $friend->isFollowing,
                          'followAt'    => $friend->followAt,
                          'isFollower'  => $friend->isFollower,
                          'followedAt'  => $friend->followedAt,
                        ] : null;

      $userProfile->coinBalance = $userProfile->coins ? array_get($userProfile->coins, 'balance', 0) : 0;
      $userProfile->herarchyIcon = $this->storageService->toHttpUrl($userProfile->popularity_herarchy->icon);
      $userProfile->herarchyLogo = $this->storageService->toHttpUrl($userProfile->popularity_herarchy->iconSmall);
      $userProfile->herarchyAlias = $userProfile->popularity_herarchy->alias;
      $userProfile->herarchyName = $userProfile->popularity_herarchy->name;
      $userProfile->popularity  = $userProfile->popularity_herarchy->popularity;

      $userProfile->consumeCoins = $userProfile->wealth_herarchy->consumeCoins;
      $userProfile->wealthHerarchyName = $userProfile->wealth_herarchy->name;
      $userProfile->wealthHerarchyIcon = $this->storageService->toHttpUrl($userProfile->wealth_herarchy->icon);
      $userProfile->wealthHerarchyLogo = $this->storageService->toHttpUrl($userProfile->wealth_herarchy->iconSmall);
      $userProfile->wealthHerarchyAlias = $userProfile->wealth_herarchy->alias;
      $userProfile->prefConf = $this->getPreferenceConf(object_get($userProfile, 'preferences_conf', []));
      $userProfile->modifiedLocation = $this->getModifiedLocationInfo($userProfile);  
    }

    return (object) [
      'user'        => $user,
      'userProfile' => $userProfile,
    ];
  }

  /**
   * Modify user's profile
   */
  public function modifyUserProfile(
    string $userId,
    ?string $nickname,
    ?string $gender,
    ?string $signature,
    ?string $birthDate
  ) {
    if ($nickname && $this->userProfileService->isNickNameUsedByOther($userId, $nickname)) {
      throw new UserNicknameBeUsedByOtherException();
    }

    $this->userProfileService->modifyUserProfile(
      $userId, $nickname, $gender, $signature, null, $birthDate
    );
  }

  /**
   * Complete user's profile
   *
   * @param string $userId
   * @param string $nickname
   * @param string $avatarImageId     avatar imageId
   */
  public function completeUserProfile(
    string $userId,
    string $nickname,
    string $avatarImageId
  ) {
    if ($this->userProfileService->isNickNameUsedByOther($userId, $nickname)) {
      throw new UserNicknameBeUsedByOtherException();
    }
    $this->userProfileService->completeUserProfile($userId, $nickname);
    $this->userImageService->setAvatar($userId, $avatarImageId);
  }

  /**
   * Complete user's profile, which register from socialite
   *
   * @param string $userId
   * @param string $nickname
   * @param ?string $avatarImageId        avatar imageId, which uploaded by user
   * @param ?string $socialiteAvatar      socialite image avatar
   */
  public function completeUserProfileFromSocialite(
    string $userId,
    string $nickname,
    ?string $avatarImageId,
    ?string $socialiteAvatar
  ) {
    if ($this->userProfileService->isNickNameUsedByOther($userId, $nickname)) {
      throw new UserNicknameBeUsedByOtherException();
    }

    // download avatar and store in user image gallery
    if ( ! $avatarImageId) {
      $imagePath = $this->downloadAvatar($socialiteAvatar, $userId);
      if ( ! $imagePath) {
        throw new UserImageUploadFailedException('avatar not found, please upload manual');
      }

      $avatar = $this->storageService->store($imagePath, [
        'prefix'  => sprintf('pizzas/images-origin/%s', $userId),
      ]);
      unlink($imagePath);

      $avatarImageId = $this->userImageService->addUserImage($userId, $avatar);
    }

    $this->userProfileService->completeUserProfile($userId, $nickname);
    $this->userImageService->setAvatar($userId, $avatarImageId);
  }

  /**
   * User bind his/her mobile, only user not bound allow.
   *
   * @param string $userId
   * @param int $countryCode
   * @param string $localMobile   local mobile without country code part
   * @param string $code          verification code
   */
  public function bindMobile(string $userId, int $countryCode, string $localMobile, string $code)
  {
    $user = $this->userService->fetchUser($userId);
    if ($user->getMobile()) {
      throw new UserMobileBoundException();
    }
    $mobile = MobileHelper::genMobileWithCountryCode($localMobile, $countryCode);
    if ($this->userService->userExists($mobile)) {
      throw new UserExistsException($mobile, 'mobile aready bound');
    }

    $this->verificationService->verify($mobile, $code);
    $this->userService->bindMobile($userId, $countryCode, $mobile);

    // todo log
  }

  /**
   * User rebind (change) his/her mobile, two ceritiea must be matched:
   *      1) both old mobile verify code and new mobile
   *      2) user aready has bound mobile 
   * verify code must be verified.
   *
   * @param string $userId
   * @param int $countryCode
   * @param string $localMobile   mobile without country code part
   * @param string $unbindCode    verification code for user exists mobile
   * @param string $rebindCode    verification code for new mobile
   */
  public function rebindMobile(
    string $userId,
    int $countryCode,
    string $localMobile,
    string $unbindCode,
    string $rebindCode
  ) {
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

    $this->verificationService->verify($user->getMobile(), $unbindCode);
    $this->verificationService->verify($mobile, $rebindCode);

    $this->userService->bindMobile($userId, $countryCode, $mobile);

    // todo log
  }

  /**
   * Update user profile following count & follower count after user follow | unfollow others
   *
   * @param string $userId            user who trigger follow | unfollow action
   * @param string $followUserId      user who be followed or unfollowed
   * @param bool $isFollow            true if action is flollow, false or else
   */
  public function updateUserFollowCount(string $userId, string $followUserId, bool $isFollow) : bool
  {
    $isIncrement = $isFollow;
    $followingSuccess = $this->userProfileService->updateUserFollowingCount($userId, $isIncrement);
    $followerSuccess = $this->userProfileService->updateUserFollowerCount($followUserId, $isIncrement);
    return $followingSuccess && $followerSuccess;
  }

  /**
   * Create a synthetic user
   *
   * @param int $countryCode
   * @param string $mobile
   * @param string $password
   * @param string $nickname
   * @param \Illuminate\Http\UploadedFile $avatar
   * @param ?int status
   */
  public function createSyntheticUser(
    int $countryCode,
    string $mobile,
    string $password,
    string $nickname,
    UploadedFile $avatar
  ) : string {
    // create user
    $mobile = MobileHelper::genMobileWithCountryCode($mobile, $countryCode);
    if ($this->userService->userExists($mobile)) {
      throw new UserExistsException($mobile, 'Mobile aready exists');
    }
    if ($this->userProfileService->isNickNameUsed($nickname)) {
      throw new UserNicknameBeUsedByOtherException();
    }

    $userId = $this->registerService->registerSyntheticUser($countryCode, $mobile, $password);

    // upload avatar
    $avatarUri = $this->storageService->store($avatar->path(), [
      'prefix'  => sprintf('pizzas/images-origin/%s', $userId),
      'mime'    => $avatar->getClientMimeType(),
    ]);
    $avatarImageId = $this->userImageService->addUserImage($userId, $avatarUri);
    if ( ! $avatarImageId) {
      $this->storageService->remove($avatarUri);
      throw new UserImageUploadFailedException();
    }
    $avatarUrl = $this->storageService->toHttpUrl($avatarUri);

    // complete user profile
    $this->userProfileService->completeUserProfile($userId, $nickname);
    $this->userImageService->setAvatar($userId, $avatarImageId);

    event(new EventUserImageUploaded($avatarUrl));
    return $userId; 
  }

  /**
   * report user geo location
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
    $this->userProfileService
         ->reportUserLocation($userId, $longitude, $latitude, $countryCode, $abbreviation, $city, $countryName, $auto);
  }

  /**
   * Save auth user last visit info
   */
  public function saveAuthUserLastVisitInfo(string $userId, ?string $version)
  {
    // get lock
    if ( ! $this->getLastVisitLock($userId)) {
      return null;
    }

    dispatch((new SaveAuthUserLastVisitInfoJob(
      $userId, (object) [
                    'version' => $version,
                  ])));
  }

  /**
   * Save user's latest published work info
   *
   * @param string $workId
   */
  public function saveUserLatestWorkPublishedInfo(string $workId)
  {
    $work = $this->workService->getDetail($workId, true, true);
    if ( ! $work) {
      return null;
    }
    return $this->userProfileService
                ->updateUserLatestWorkPublishedInfo(
                  $work->userId, $workId, $work->createdAt
                );

  }

  /**
   * @param int $countryCode 
   * @param string $mobile
   *
   * @return string
   */
  public function getMobileUserSource(int $countryCode, string $mobile)
  {
    $mobile = MobileHelper::genMobileWithCountryCode($mobile, $countryCode);
    $user = $this->userService->fetchUserByMobile($mobile);

    if ( ! $user) {
      return null;
    }

    return $user->source == UserConstant::SOURCE_TUDC
              ? $user->source
              : UserConstant::SOURCE_MOBILE;
  }

  public function autoCompleteUserInfo(string $userId, ?string $nick, ?string $avatarUrl)
  {
      $defaultAvatar = $this->storageService->toHttpUrl(config('image.default_avatar'));
      $avatar = $avatarUrl ? $avatarUrl : $defaultAvatar;
      $nickname = $nick ? $nick : Str::generateNickName(9);
      for ($i = 0; $i < 10; $i++)
      {
          if ($this->userProfileService->isNickNameUsedByOther($userId, $nickname))
          {
              $nickname = Str::generateNickName(9);
          }else {
              break;
          }
      }
      if ($this->userProfileService->isNickNameUsedByOther($userId, $nickname)){
          $nickname = "Sing_".Str::randUniqStr(10);
      }

      $imagePath = $this->downloadAvatar($avatar, $userId);
      if ( ! $imagePath) {
          $imagePath = $this->downloadAvatar($defaultAvatar, $userId);
      }
      $this->userProfileService->completeUserProfile($userId, $nickname);
      if ($imagePath)
      {
          $avatar = $this->storageService->store($imagePath, [
              'prefix'  => sprintf('pizzas/images-origin/%s', $userId),
          ]);
          unlink($imagePath);
          $avatarImageId = $this->userImageService->addUserImage($userId, $avatar);
          $this->userImageService->setAvatar($userId, $avatarImageId);
      }

  }

  /**
  * @param $userId
  * @param $targetUserIds
  * @return Collection
  */

  public function getUsersProfiles($userId, $targetUserIds) : Collection{
      $userProfile = null;
      $userProfiles = $this->userProfileService->getUserProfiles($targetUserIds);

      return $userProfiles->map(function($userProfile, $__) use($userId) {
          $userProfile->avatar = $this->storageService->toHttpUrl($userProfile->avatar);
          $friend = $this->friendService->getUserRelationship($userId, [$userProfile->userId])->first();
          $userProfile->friend = $friend ? (object)[
              'isFollowing' => $friend->isFollowing,
              'followAt'    => $friend->followAt,
              'isFollower'  => $friend->isFollower,
              'followedAt'  => $friend->followedAt,
          ] : null;
          return $userProfile;
      });
  }

    public function getUsersSimpleProfiles($userIds){
        $profiles = $this->userProfileService->getUserSimpleProfiles($userIds);
        $pendIds=[];
        foreach ($userIds as $userId){
            $match=false;
            foreach ($profiles as $profile) {
                if($profile->userId=$userId){
                    $match=true;
                }
            }
            if(!$match){
                $pendIds[]=$userId;
            }
        }
        foreach ($pendIds as $userId){
            $profiles[]=(object)[
                'userId'=>$userId,
                'nickname'=>'',
                'avatar'=>'',
            ];
        }
        return $profiles;
    }

  /**
   * @param string $userId
   * @param bool $status
   * @return int
   */
  public function updateUserImStatus(string $userId, bool $status):int {
      return $this->userProfileService->updateUserImStatus($userId, $status);
  }

  /**
   * @param string $userId
   * @param string $name
   * @param bool $on
   * @return mixed
   */
  public function updateUserPref(string $userId, string $name, bool $on){
      return $this->userProfileService->updateUserPreference($userId, $name, $on);
  }


  private function downloadAvatar(string $avatarUrl, string $userId) : ?string
  {
    $file = fopen($avatarUrl, 'r');
    if ( ! $file) {
      return null;
    }

    $targetFilePath = $this->getAvatarPath($userId);
    $dir = dirname($targetFilePath); 
    if ( ! is_dir($dir)) {
      mkdir($dir, 0700, true);
    }

    $target = fopen($targetFilePath, 'w');
    while ( ! feof($file)) {
      $bytes = fgets($file, 1024);
      fputs($target, $bytes, strlen($bytes));
    }
    fclose($file);
    fclose($target);

    return $targetFilePath;
  }

  private function getAvatarPath(string $userId)
  {
    return storage_path(sprintf('app/tmp/avatar/%s', $userId));
  }

  private function getLastVisitLock(string $userId) : bool
  {
    $expires = 5; // 5 minutes
    $key = sprintf('user:lastvist:lock:%s', $userId);

    return Cache::add($key, $userId, $expires);
  }

  private function getPreferenceConf(? array $confArr) : \stdClass {

      return (object)[
          'followed' => $confArr ? (bool)array_get($confArr, 'notify_followed', true): true,
          'favourite' => $confArr ? (bool)array_get($confArr, 'notify_favourite', true):true,
          'comment' =>$confArr ? (bool)array_get($confArr, 'notify_comment', true): true,
          'gift' => $confArr ? (bool)array_get($confArr, 'notify_gift', true):true,
          'imMsg' => $confArr ? (bool)array_get($confArr, 'notify_im_msg', true):true,
          'unfollowedMsg' => $confArr ? (bool)array_get($confArr, 'privacy_unfollowed_msg', true):true,
      ];
  }

    /**
     * get modified location info
     * @param $userProfile
     * @return null|\stdClass
     */
  private function getModifiedLocationInfo($userProfile): ?\stdClass {
      $location = isset($userProfile->location) ? $userProfile->location : [];
      $locationModifiedByUser = array_get($location, 'modified_by_user', false);
      if ($locationModifiedByUser){

          $countryCode = array_get($location, 'country_code');
          $abbr = array_get($location, 'abbreviation', '');
          $city = array_get($location, 'city', '');
          $countryName = array_get($location, 'country_name');

          $countryInfo = collect(config('countrycode'))->filter(function ($item, $_) use ($abbr) {
              return $item[0] == $abbr;
          })->first();

          if ($countryInfo && !$countryName){
              $countryName = $countryInfo[4];
          }

          if (!$countryCode){
              $countryCode = $countryInfo ? $countryInfo[2] :'';
          }

          return (object)[
              'countryCode' => $countryCode,
              'abbr' => $abbr,
              'countryName' => $countryName,
              'city' => $city,
          ];
      }
      return null;
  }
}
