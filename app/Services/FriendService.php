<?php

namespace SingPlus\Services;

use Log;
use Cache;
use LogTXIM;
use Illuminate\Support\Collection;
use SingPlus\Contracts\Users\Services\UserService as UserServiceContract;
use SingPlus\Contracts\Users\Services\UserProfileService as UserProfileServiceContract;
use SingPlus\Contracts\Friends\Services\FriendService as FriendServiceContract;
use SingPlus\Contracts\Musics\Services\MusicService as MusicServiceContract;
use SingPlus\Contracts\Works\Services\WorkService as WorkServiceContract;
use SingPlus\Contracts\Storages\Services\StorageService as StorageServiceContract;
use SingPlus\Contracts\Works\Constants\WorkConstant;
use SingPlus\Exceptions\Users\UserNotExistsException;
use SingPlus\Events\Friends\UserFollowed as UserFollowedEvent;
use SingPlus\Events\Friends\UserTriggerFollowed as UserFollowedTriggerEvent;
use SingPlus\Events\Friends\UserUnfollowed as UserUnfollowedEvent;
use SingPlus\Events\Friends\GetRecommendUserFollowingAction as GetRecommendUserFollowingActionEvent;

class FriendService
{
  /**
   * @var UserServiceContract
   */
  private $userService;

  /**
   * @var FriendServiceContract
   */
  private $friendService;

  /**
   * @var UserProfileServiceContract
   */
  private $userProfileService;

  /**
   * @var StorageServiceContract
   */
  private $storageService;

  /**
   * @var MusicServiceContract
   */
  private $musicService;

  /**
   * @var WorkServiceContract
   */
  private $workService;

  public function __construct(
    UserServiceContract $userService,
    UserProfileServiceContract $userProfileService,
    FriendServiceContract $friendService,
    StorageServiceContract $storageService,
    MusicServiceContract $musicService,
    WorkServiceContract $workService
  ) {
    $this->userService = $userService;
    $this->userProfileService = $userProfileService;
    $this->friendService = $friendService;
    $this->storageService = $storageService;
    $this->musicService = $musicService;
    $this->workService = $workService;
  }

  /**
   * user follow some one
   *
   * @param string $userId
   * @param string $followedUserId      be followed user id
   *
   * @return bool
   */
  public function follow(string $userId, string $followedUserId) : bool
  {
    if (is_null($this->userService->fetchUser($followedUserId))) {
      throw new UserNotExistsException('The user you will follow is not exists');
    }

    $success = $this->friendService->follow($userId, $followedUserId);
    $isUnfollowed = $this->friendService->isUnfollowedUser($userId, $followedUserId);
    if ($success) {
      if (!$isUnfollowed){
          event(new UserFollowedEvent($userId, $followedUserId));
          LogTXIM::debug('FriendService  event UserFollowedEvent', []);
      }else {
          event(new UserFollowedTriggerEvent($userId, $followedUserId));
          LogTXIM::debug('FriendService  event UserTriggerFollowed', []);
      }
    }

    return $success;
  }

  /**
   * user unfollow some one
   *
   * @param string $userId
   * @param string $followedUserId      be followed user id
   *
   * @return bool
   */
  public function unfollow(string $userId, string $followedUserId) : bool
  {
    $success = $this->friendService->unfollow($userId, $followedUserId);
    if ($success) {
        $this->friendService->addUnfollowed($userId, $followedUserId);
        event(new UserUnfollowedEvent($userId, $followedUserId));
    }

    return $success;
  }

  /**
   * Get user followers (fans)
   *
   * Tips: 关注关系为当前登录用户与查询用户followers之间的关系
   *
   * @param string $userId
   * @param string $targetUserId      target user who's followers will be fetch
   * @param ?string $id               for pagination
   * @param bool $isNext              for pagination
   * @param int $size                 for pagination
   * @param ?int $page                如果有值，则表示使用page分页
   *
   * @return Collection               elements as below
   *                                  - id string                for pagination
   *                                  - userId string            目标用户userId
   *                                  - nickname ?string
   *                                  - avatar  ?string follower avatar url
   *                                  - isFollowing bool    是否是当前登录用户的following
   *                                  - followAt \Carbon\Carbon  当前登录用户关注目标用户的时间
   *                                  - isFollower bool     是否是当前登录用户的follower
   *                                  - followedAt ?\Carbon\Carbon  当前用户被目标用户关注的时间
   */
  public function getFollowers(
    string $userId,
    string $targetUserId,
    ?string $id,
    bool $isNext,
    int $size,
    ?int $page = null
  ) : Collection {
    $followers = $this->friendService->getFollowers($targetUserId, $id, $isNext, $size, $page);
    $userIds = $followers->map(function ($follower, $_) {
                  return $follower->userId;
                })->toArray();
    // 如果查看别人的followers，需要获取当前登录用户与别人followers的关注关系
    if ($userId != "" && $userId != $targetUserId) {
      $followers = $this->friendService
                        ->getUserRelationship($userId, $userIds)
                        ->map(function ($user, $_) use ($followers) {
                          $follower = $followers->where('userId', $user->userId)->first();
                          $user->id = $follower->id;
                          return $user;
                        });
    }else if ($userId == ""){
        // 未登录的时候与别人followers的关注关系全未未关注和未被关注
        $followers = $followers->map(function($friend, $_){
            $friend->isFollowing = false;
            $friend->followAt = null;
            $friend->isFollower = false;
            $friend->followedAt = null;
            return $friend;
        });
    }

    $profiles = $this->userProfileService->getUserProfiles($userIds);

    return $this->buildFriends($followers, $profiles);
  }

  /**
   * Get user followings
   *
   * Tips: 关注关系为当前登录用户与查询用户followings之间的关系
   *
   * @param string $userId
   * @param string $targetUserId      target user who's followers will be fetch
   * @param ?int $page                如果page有值，表示使用page分页
   * @param ?int size
   *
   * @return Collection       elements as below
   *                          - userId string            目标用户userId
   *                          - nickname ?string
   *                          - avatar  ?string follower avatar url
   *                          - isFollowing bool    是否是当前登录用户的following
   *                          - followAt \Carbon\Carbon  当前登录用户关注目标用户的时间
   *                          - isFollower bool     是否是当前登录用户的follower
   *                          - followedAt ?\Carbon\Carbon  当前登录用户被目标用户关注的时间
   */
  public function getFollowings(
    string $userId,
    $targetUserId,
    ?int $page = null,
    ?int $size = null
  ) : Collection {
    $followings = $this->friendService->getFollowings($targetUserId, $page, $size);
    $userIds = $followings->map(function ($following, $_) {
                  return $following->userId;
                })->toArray();

    if ($userId != "" && $userId != $targetUserId) {
      $followings = $this->friendService->getUserRelationship($userId, $userIds);
    }else if ($userId == "")
    {
        // 未登录的时候与别人following的关注关系全未未关注和未被关注
        $followings = $followings->map(function($friend, $_){
            $friend->isFollowing = false;
            $friend->followAt = null;
            $friend->isFollower = false;
            $friend->followedAt = null;
            return $friend;
        });
    }
    $profiles = $this->userProfileService->getUserProfiles($userIds);
    return $this->buildFriends($followings, $profiles);
  }

  /**
   * Search users by nickname
   *
   * @param string $userId
   * @param string $nickname
   *
   * @return Collection       elements as below:
   *                          - userId string            目标用户userId
   *                          - nickname ?string
   *                          - avatar  ?string follower avatar url
   *                          - isFollowing bool    是否是给定用户的following
   *                          - followAt \Carbon\Carbon  给定用户关注目标用户的时间
   *                          - isFollower bool     是否是给定用户的follower
   *                          - followedAt ?\Carbon\Carbon  给定用户被目标用户关注的时间
   */
  public function searchUsers(string $userId, string $nickname) : Collection
  {
    if (strlen($nickname) < 3) {
      return collect();
    }

    $limitation = 50;
    $users = $this->userProfileService
                  ->searchUsersByNickname($nickname, $limitation)
                  ->filter(function ($user, $_) use ($userId) {
                    return $user->userId != $userId;    // exclude current user self
                  });
    $targetUserIds = $users->map(function ($user, $_) {
                        return $user->userId;
                      })->toArray();
    $relationships = $this->friendService
                          ->getUserRelationship($userId, $targetUserIds);
    return $this->buildFriends($relationships, $users);
  }

  protected function buildFriends(Collection $friends, Collection $profiles) : Collection
  {
    return $friends->map(function ($friend, $_) use ($profiles) {
      $profile = $profiles->where('userId', $friend->userId)->first();
      return (object) [
        'id'          => object_get($friend, 'id'),
        'userId'      => $friend->userId,
        'nickname'    => object_get($profile, 'nickname'),
        'avatar'      => $this->storageService->toHttpUrl(object_get($profile, 'avatar')),
        'isFollowing' => $friend->isFollowing,
        'followAt'    => $friend->followAt,
        'isFollower'  => $friend->isFollower,
        'followedAt'  => $friend->followedAt,
      ];
    });
  }

  /**
   * Get user following latest works
   *
   * @param string $userId
   * @param ?string $id         for pagination 
   * @param bool $isNext        for pagination
   * @param int $size           for pagination
   *
   * @return Collection         properties as below:
   *                            - id string     for pagination
   *                            - workId string         work id
   *                            - workName ?string
   *                            - user \stdClass
   *                              - userId string
   *                              - avatar string         user avatar url
   *                              - nickname string       user nickname
   *                            - description string    work's description, default share text
   *                            - music \stdClass
   *                              - name string           music nmae
   *                            - cover string          music cover url
   *                            - chorusType ?int       work chorus type
   *                            - chorusCount int
   *                            - listenCount int       work be listened count
   *                            - favouriteCount int    work favourite count
   *                            - commentCount int      total comments count
   *                            - transmitCount int     total transmit count from sing+
   *                            - resource string       work resource url
   *                            - createdAt string      Datetime, format: Y-m-d H:i:s
   */
  public function getUserFollowingLatestWorks(
    string $userId,
    ?string $id,
    bool $isNext,
    int $size
  ) : Collection {
    $followingIds = $this->friendService
                         ->getFollowings($userId)
                         ->map(function ($following, $_) {
                            return $following->userId;
                         })->toArray();

    $works = $this->workService->getUsersWorks($followingIds, $id, $isNext, $size);
    $userIds = [];
    $musicIds = [];
    $originWorkIds = [];
    $originWorkUserMap = [];
    $works->each(function ($work, $_) use (&$userIds, &$musicIds, &$originWorkIds) {
      $userIds[] = $work->userId;
      $musicIds[] = $work->musicId;
      if ($work->originWorkId && ! in_array($work->originWorkId, $originWorkIds)) {
        $originWorkIds[] = $work->originWorkId;
      }
    });
    $originWorks = $this->workService->getWorksByIds(array_unique($originWorkIds));
    $originWorks->each(function ($work, $_) use (&$userIds, &$originWorkUserMap) {
      if ( ! in_array($work->userId, $userIds)) {
        $userIds[] = $work->userId;
      }
      $originWorkUserMap[$work->workId] = $work->userId;
    });
    $users = $this->userProfileService->getUserProfiles(array_unique($userIds));
    $musics = $this->musicService->getMusics(array_unique($musicIds), true);

    return $works->map(function ($work, $_) use ($users, $musics, $originWorkUserMap) {
      // get origin work user
      $originWorkUser = null;
      if ($work->chorusType == WorkConstant::CHORUS_TYPE_JOIN) {
        $originWorkUserId = $originWorkUserMap[$work->originWorkId];
        $originWorkUser = $users->where('userId', $originWorkUserId)->first();
      }

      $user = $users->where('userId', $work->userId)->first();
      if ( ! $user) {
        Log::alert('Data missed. work miss user profile', [
          'work_id' => $work->workId,
          'user_id' => $work->userId,
        ]);

        return null;
      }

      $music = $musics->where('musicId', $work->musicId)->first();
      if ( ! $music) {
        Log::alert('Data missed. work miss music', [
          'work_id'   => $work->workId,
          'music_id'  => $work->musicId,
        ]);

        return null;
      }

      $res = (object) [
        'id'              => $work->id,
        'workId'          => $work->workId,
        'workName'        => $work->workName,
        'user'            => (object) [
                                'userId'    => $user->userId,
                                'avatar'    => $this->storageService->toHttpUrl($user->avatar),
                                'nickname'  => $user->nickname,
                              ],
        'music'           => (object) [
                                'musicId' => $music->musicId,
                                'name'    => $music->name,
                              ],
        'cover'           => $this->storageService->toHttpUrl($work->cover), 
        'chorusType'      => $work->chorusType,
        'chorusCount'     => $work->chorusCount,
        'originWorkUser'  => null,
        'description'     => $work->description,
        'resource'        => $this->storageService->toHttpUrl($work->resource),
        'listenCount'     => $work->listenCount,
        'favouriteCount'  => $work->favouriteCount,
        'commentCount'    => $work->commentCount,
        'transmitCount'   => $work->transmitCount,
        'shareLink'       => secure_url(sprintf('c/page/works/%s', $work->workId)),
        'createdAt'       => $work->createdAt,
      ];

      if ($originWorkUser) {
        $res->originWorkUser = (object) [
          'userId'    => $originWorkUser->userId,
          'avatar'    => $this->storageService->toHttpUrl($originWorkUser->avatar),
          'nickname'  => $originWorkUser->nickname,
        ];
      }

      return $res;
    })->filter(function ($work, $_) {
      return ! is_null($work); 
    });
  }

  /**
   * Get user following latest works
   *
   * @param string $userId
   * @param int $page
   * @param int $size           for pagination
   *
   * @return Collection         properties as below:
   *                            - workId string         work id
   *                            - workName ?string
   *                            - user \stdClass
   *                              - userId string
   *                              - avatar string         user avatar url
   *                              - nickname string       user nickname
   *                            - description string    work's description, default share text
   *                            - music \stdClass
   *                              - name string           music nmae
   *                            - cover string          music cover url
   *                            - chorusType ?int       work chorus type
   *                            - chorusCount int
   *                            - listenCount int       work be listened count
   *                            - favouriteCount int    work favourite count
   *                            - commentCount int      total comments count
   *                            - transmitCount int     total transmit count from sing+
   *                            - resource string       work resource url
   *                            - createdAt string      Datetime, format: Y-m-d H:i:s
   */
  public function getUserFollowingLatestWorks_graph(
    string $userId,
    int $page,
    int $size
  ) : Collection {
    $graphService = app()->make(\SingPlus\Domains\Friends\Services\GraphFriendService::class);
    $works = $graphService->getFollowingLatestWorks($userId, $page, $size);
    $works = $this->workService->getWorksByIds($works->toArray());

    $userIds = [];
    $musicIds = [];
    $originWorkIds = [];
    $originWorkUserMap = [];
    $works->each(function ($work, $_) use (&$userIds, &$musicIds, &$originWorkIds) {
      $userIds[] = $work->userId;
      $musicIds[] = $work->musicId;
      if ($work->originWorkId && ! in_array($work->originWorkId, $originWorkIds)) {
        $originWorkIds[] = $work->originWorkId;
      }
    });
    $originWorks = $this->workService->getWorksByIds(array_unique($originWorkIds));
    $originWorks->each(function ($work, $_) use (&$userIds, &$originWorkUserMap) {
      if ( ! in_array($work->userId, $userIds)) {
        $userIds[] = $work->userId;
      }
      $originWorkUserMap[$work->workId] = $work->userId;
    });
    $users = $this->userProfileService->getUserProfiles(array_unique($userIds));
    $musics = $this->musicService->getMusics(array_unique($musicIds), true);

    return $works->map(function ($work, $_) use ($users, $musics, $originWorkUserMap) {
      // get origin work user
      $originWorkUser = null;
      if ($work->chorusType == WorkConstant::CHORUS_TYPE_JOIN) {
        $originWorkUserId = $originWorkUserMap[$work->originWorkId];
        $originWorkUser = $users->where('userId', $originWorkUserId)->first();
      }

      $user = $users->where('userId', $work->userId)->first();
      if ( ! $user) {
        Log::alert('Data missed. work miss user profile', [
          'work_id' => $work->workId,
          'user_id' => $work->userId,
        ]);

        return null;
      }

      $music = $musics->where('musicId', $work->musicId)->first();
      if ( ! $music) {
        Log::alert('Data missed. work miss music', [
          'work_id'   => $work->workId,
          'music_id'  => $work->musicId,
        ]);

        return null;
      }

      $res = (object) [
        'id'              => $work->workId,
        'workId'          => $work->workId,
        'workName'        => $work->workName,
        'user'            => (object) [
                                'userId'    => $user->userId,
                                'avatar'    => $this->storageService->toHttpUrl($user->avatar),
                                'nickname'  => $user->nickname,
                              ],
        'music'           => (object) [
                                'musicId' => $music->musicId,
                                'name'    => $music->name,
                              ],
        'cover'           => $this->storageService->toHttpUrl($work->cover), 
        'chorusType'      => $work->chorusType,
        'chorusCount'     => $work->chorusCount,
        'originWorkUser'  => null,
        'description'     => $work->description,
        'resource'        => $this->storageService->toHttpUrl($work->resource),
        'listenCount'     => $work->listenCount,
        'favouriteCount'  => $work->favouriteCount,
        'commentCount'    => $work->commentCount,
        'transmitCount'   => $work->transmitCount,
        'shareLink'       => secure_url(sprintf('c/page/works/%s', $work->workId)),
        'createdAt'       => $work->createdAt,
      ];

      if ($originWorkUser) {
        $res->originWorkUser = (object) [
          'userId'    => $originWorkUser->userId,
          'avatar'    => $this->storageService->toHttpUrl($originWorkUser->avatar),
          'nickname'  => $originWorkUser->nickname,
        ];
      }

      return $res;
    })->filter(function ($work, $_) {
      return ! is_null($work); 
    });
  }

  /**
   * Get socialite user's friends info
   *
   * @param string $userId
   * @param array $socialiteUserIds     socialite user id
   * @param string $provider            socialite provider, such as facebook
   *
   * @return Collection       elements as below:
   *                          - socialiteUserId string
   *                          - provider string
   *                          - userId string            目标用户userId
   *                          - nickname ?string
   *                          - avatar  ?string follower avatar url
   *                          - isFollowing bool    是否是给定用户的following
   *                          - followAt \Carbon\Carbon  给定用户关注目标用户的时间
   *                          - isFollower bool     是否是给定用户的follower
   *                          - followedAt ?\Carbon\Carbon  给定用户被目标用户关注的时间
   */
  public function getSocialiteUsersFriends(
    string $userId,
    array $socialiteUserIds,
    string $provider
  ) {
    $appChannel = config('tudc.currentChannel');
    $socialiteUsers = $this->userService
                           ->fetchSocialiteUsers($appChannel, $socialiteUserIds, $provider);
    $targetUserIds = $socialiteUsers->map(function ($user, $_) {
                        return $user->userId;
                      })->toArray();
                           
    $profiles = $this->userProfileService->getUserProfiles($targetUserIds);
    $relationships = $this->friendService
                          ->getUserRelationship($userId, $targetUserIds);
    return $this->buildFriends($relationships, $profiles)
                ->map(function ($friend, $_) use ($socialiteUsers) {
                  $socialiteUser = $socialiteUsers->where('userId', $friend->userId)->first();
                  $friend->socialiteUserId = $socialiteUser->socialiteUserId;
                  $friend->provider = $socialiteUser->provider;

                  return $friend;
                });

  }

  /**
   * @param string $loginUserId
   * @param ?string $id           for pagination
   * @param bool $isNext          for pagination
   * @param int $size             for pagination
   *
   * @return Collection           elements are \stdClass:
   *                              - id string
   *                              - userId string
   *                              - avatar string
   *                              - nickname string
   *                              - isFollowing bool
   *                              - isFollower bool
   *                              - isAutoRecommend bool
   */
  public function getRecommendUserFollowings(
    string $loginUserId,
    ?string $id,
    bool $isNext,
    int $size
  ) {
    // 使用lock，防止太多的获取用户推荐关注者事件触发
    $lockKey = sprintf('notify:user:%s:recommendfollowing', $loginUserId);
    $lockTime = 10;  // 10 minutes
    if (Cache::add($lockKey, time(), $lockTime)) {
      event(new GetRecommendUserFollowingActionEvent($loginUserId));
    }

    $recommends = $this->friendService
                       ->getRecommendUserFollowings($loginUserId, $id, $isNext, $size);

    $userIds = $recommends->map(function ($recommend, $_) {
      return $recommend->followingUserId;
    })->toArray();

    $profiles = $this->userProfileService->getUserProfiles($userIds);
    $relationships = $this->friendService
                          ->getUserRelationship($loginUserId, $userIds);
    return $recommends->map(function ($recommend, $_) use ($relationships, $profiles) {
      $profile = $profiles->where('userId', $recommend->followingUserId)->first();
      $relationship = $relationships->where('userId', $recommend->followingUserId)->first();
      if ( ! $profile || ! $relationship) {
        return null;
      }

      return (object) [
        'id'              => $recommend->id,
        'userId'          => $recommend->followingUserId,
        'avatar'          => $this->storageService->toHttpUrl($profile->avatar),
        'nickname'        => object_get($profile, 'nickname'),
        'isFollowing'     => $relationship->isFollowing,
        'isFollower'      => $relationship->isFollower,
        'isAutoRecommend' => $recommend->isAutoRecommend,
      ];
    })->filter(function ($recommend, $_) {
      return ! is_null($recommend); 
    });
  }

  /**
   * Get recommend works by country for user who has no followings
   *
   * @param string $loginUserId
   * @param string $countryAbbr
   * @param ?string $id           for pagination
   * @param bool $isNext          for pagination
   * @param int $size             for pagination
   *
   * @return Collection           elements are \stdClass, properties as below:
   *                              - id string
   *                              - user \stdClass
   *                                - userId string
   *                                - avatar string
   *                                - nickname string
   *                                - signature string
   *                                - isFollowing bool
   *                                - isFollower bool
   *                              - isAutoRecommend bool
   *                              - works array   elements are \stdClass
   *                                - workId string
   *                                - cover string
   *                                - musicName string
   *                                - listenNum int
   *                                - chorusType ?int
   */
  public function getRecommendWorksByCountry(
    string $loginUserId,
    string $countryAbbr,
    ?string $id,
    bool $isNext,
    int $size
  ) : Collection {
    $userWorks = $this->friendService
                      ->getRecommendWorksByCountry($countryAbbr, $id, $isNext, $size);
    $userIds = [];
    $workIds = [];
    $userWorks->each(function ($info, $_) use (&$userIds, &$workIds) {
      $userIds[] = $info->userId;
      $workIds = array_merge($workIds, $info->workIds);
    });
    $userIds = array_unique($userIds);
    $workIds = array_unique($workIds);
    $profiles = $this->userProfileService->getUserProfiles($userIds);
    $relationships = $this->friendService
                          ->getUserRelationship($loginUserId, $userIds);
    $works = $this->workService->getWorksByIds($workIds);
    $musicIds = $works->map(function ($work, $_) {
      return $work->musicId;
    })->unique()->toArray();
    $musics = $this->musicService->getMusics($musicIds);

    return $userWorks->map(function (
              $info, $_
           ) use (
              $loginUserId, $profiles, $relationships, $works, $musics
           ) {
      $profile = $profiles->where('userId', $info->userId)->first();
      $relationship = $relationships->where('userId', $info->userId)->first();
      if ( ! $profile || ! $relationship) {
        return null;
      }
      // login user should be exclude in following list
      if ($loginUserId && $info->userId == $loginUserId) {
        return null;
      }

      $currWorks = collect();
      if ( ! empty($info->workIds)) {
        $currWorks = collect($info->workIds)->map(function ($workId, $_) use ($works, $musics) {
          $work = $works->where('workId', $workId)->first();
          $music = $musics->where('musicId', $work->musicId)->first();
          if ( ! $work) {
            return null;
          }

          return (object) [
            'workId'      => $work->workId,
            'cover'       => $this->storageService->toHttpUrl($work->cover),
            'musicName'   => $music ? $music->name : '',
            'listenCount' => $work->listenCount,
            'chorusType'  => $work->chorusType,
          ];
        })->filter(function ($work, $_) {
          return ! is_null($work); 
        });

      }

      return (object) [
        'id'      => $info->id,
        'user'    => (object) [
          'userId'      => $profile->userId,
          'avatar'      => $this->storageService->toHttpUrl($profile->avatar),
          'nickname'    => $profile->nickname,
          'signature'   => $profile->signature,
          'isFollowing'     => $relationship->isFollowing,
          'isFollower'      => $relationship->isFollower,
        ],
        'isAutoRecommend' => $info->isAutoRecommend,
        'works'           => $currWorks,
      ];
    })->filter(function ($info, $_) {
      return ! is_null($info); 
    });
  }
}
