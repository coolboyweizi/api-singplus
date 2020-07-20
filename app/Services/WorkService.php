<?php

namespace SingPlus\Services;

use Log;
use Cache;
use Illuminate\Support\Collection;
use Illuminate\Http\UploadedFile;
use SingPlus\Contracts\DailyTask\Constants\DailyTask;
use SingPlus\Contracts\News\Constants\News;
use SingPlus\Contracts\Works\Services\WorkService as WorkServiceContract;
use SingPlus\Contracts\Users\Services\UserProfileService as UserProfileServiceContract;
use SingPlus\Contracts\Users\Services\UserImageService as UserImageServiceContract;
use SingPlus\Contracts\Musics\Services\MusicService as MusicServiceContract;
use SingPlus\Contracts\Storages\Services\StorageService as StorageServiceContract;
use SingPlus\Contracts\Friends\Services\FriendService as FriendServiceContract;
use SingPlus\Contracts\Supports\UrlShortener as UrlShortenerContract;
use SingPlus\Contracts\Users\Constants\User as UserConstant;
use SingPlus\Contracts\Users\Services\UserService as UserServiceContract;
use SingPlus\Contracts\Works\Constants\WorkConstant;
use SingPlus\Contracts\Feeds\Services\FeedService as FeedServiceContract;
use SingPlus\Contracts\News\Services\NewsServices as NewsServiceContract;
use SingPlus\Contracts\DailyTask\Services\DailyTaskService as DailyServiceContract;
use SingPlus\Contracts\Gifts\Services\GiftService as GiftServiceContract;
use SingPlus\Domains\Works\Models\Comment;
use SingPlus\Events\Works\WorkUpdateUserFavouriteCacheData as WorkUpdateUserFavouriteCacheDataEvent;
use SingPlus\Exceptions\Users\UserNotExistsException;
use SingPlus\Exceptions\Musics\MusicNotExistsException;
use SingPlus\Exceptions\Works\WorkIsPrivateException;
use SingPlus\Exceptions\Works\WorkUploadFailedException;
use SingPlus\Exceptions\Works\WorkAreadyUploadedException;
use SingPlus\Exceptions\Works\WorkUploadTaskNotExistsException;
use SingPlus\Exceptions\Works\WorkNotExistsException;
use SingPlus\Exceptions\Works\WorkSheetNotExistsException;
use SingPlus\Exceptions\Works\WorkActionForbiddenException;
use SingPlus\Exceptions\Works\CommentNotExistsException;
use SingPlus\Exceptions\Works\CommentActionForbiddenException;
use SingPlus\Exceptions\Users\UserImageNotExistsException;
use SingPlus\Events\UserCommentWork as UserCommentWorkEvent;
use SingPlus\Events\UserTriggerFavouriteWork as UserTriggerFavouriteWorkEvent;
use SingPlus\Events\Works\WorkPublished as WorkPublishedEvent;
use SingPlus\Events\Works\WorkDeleted as WorkDeletedEvent;
use SingPlus\Events\Works\WorkUpdateTags as WorkUpdateTagsEvent;
use SingPlus\Events\FeedCreated as FeedCreatedEvent;
use SingPlus\Jobs\UpdateWorkPopularity as UpdateWorkPopularityJob;
use SingPlus\Support\Helpers\Arr;
use SingPlus\Events\Works\WorkUpdateCommentGiftCacheData as WorkUpdateCommentGiftCacheDataEvent;

class WorkService
{
  /**
   * @var WorkServiceContract
   */
  private $workService;

  /**
   * @var MusicServiceContract
   */
  private $musicService;

  /**
   * @var StorageServiceContract
   */
  private $storageService;

  /**
   * @var UserServiceContract
   */
  private $userService;

  /**
   * @var UserProfileServiceContract
   */
  private $userProfileService;

  /**
   * @var UserImageServiceContract
   */
  private $userImageService;

  /**
   * @var UrlShortenerContract
   */
  private $urlShortener;

  /**
   * @var FriendServiceContract
   */
  private $friendService;

  /**
   * @var FeedServiceContract
   */
  private $feedService;

  /**
   * @var NewsServiceContract
   */
  private $newsService;

    /**
     * @var DailyServiceContract
     */
  private $dailyTaskService;

  private $giftService;

  public function __construct(
    WorkServiceContract $workService,
    MusicServiceContract $musicService,
    StorageServiceContract $storageService,
    UserServiceContract $userService,
    UserProfileServiceContract $userProfileService,
    UserImageServiceContract $userImageService,
    UrlShortenerContract $urlShortener,
    FriendServiceContract $friendService,
    FeedServiceContract $feedService,
    NewsServiceContract $newsServices,
    DailyServiceContract $dailyTaskService,
    GiftServiceContract $giftService
  ) {
    $this->workService = $workService;
    $this->musicService = $musicService;
    $this->storageService = $storageService;
    $this->userService = $userService;
    $this->userProfileService = $userProfileService;
    $this->userImageService = $userImageService;
    $this->urlShortener = $urlShortener;
    $this->friendService = $friendService;
    $this->feedService = $feedService;
    $this->newsService = $newsServices;
    $this->dailyTaskService = $dailyTaskService;
    $this->giftService = $giftService;
  }

  //==============================================
  //  2-steps upload workflows:
  //  one step:
  //    create upload task
  //  two step:
  //    upload work (only work file, exclude anly other arguments).
  //    Server will process work storage and create work record in db
  //
  //  note:
  //    system will clear expired task (eg: created before on day)
  //==============================================
  /**
   * Step one: create upload task
   *
   * @param string $userId          user id
   * @param ?string $musicId        music id which work belongs to
   * @param ?string $workName       workName must be set, if musicId
   *                                is empty
   * @param int $duration           how much time the works will play
   * @param ?string $coverImageId   cover image id
   * @param array $slides           elements are slide image id
   * @param string $description     work description
   * @param bool $needGetUploadInfo indicate whether out client upload to file server directly
   * @param bool $isPrivate         whether set work private            
   * @param ?int $chorusType        chorus type only exists if work is chorus
   * @param ?string $originWorkId   chorus start work id, only exists if work is chorus join work
   *
   * @return stdClass             properties as below:
   *                              - taskId  string  taskId
   *                              - presinged ?\stdClass
   *                                - url string    upload url
   *                                - method string upload http method
   *                                - contentType string upload http header
   *                                - formData  array upload form data
   *
   * @throw MusicNotExistsException
   */
  public function createTwoStepUploadTask(
    string $userId,
    ?string $musicId,
    ?string $workName,
    int $duration,
    ?string $coverImageId,
    array $slides,
    string $description,
    bool $needGetUploadInfo = false,
    bool $isPrivate = false,
    ?string $workMimeType,
    ?int $chorusType,
    ?string $originWorkId
  ) {
    $noAccompaniment = false;
    $musicCover = null;
    if ($musicId) {
      if ( ! ($music = $this->musicService->getMusic($musicId))) {
        throw new MusicNotExistsException();
      }
      if (count($music->covers) > 0) {
        $musicCover = $music->covers[0];
      }
    } else {
      $music = $this->musicService->getFakeMusic();
      $musicId = $music->musicId;
      $noAccompaniment = true;
    }

    // process cover
    if ($coverImageId) {
      // 如果用户上传了图片，使用该图片作为封面
      $isDefaultCover = false;
      $image = $this->userImageService
                     ->getImages([$coverImageId])
                     ->first();
      if ( ! $image) {
        throw new UserImageNotExistsException();
      }
      $cover = $image->uri;
    } else {
      // 如果用户没有上传，则:
      //    1) 非清唱作品，使用作品对应歌曲封面
      //    2) 清唱作品，使用默认图片作为封面
      $isDefaultCover = true;
      $cover = $musicCover ?: config('image.default_work_cover');
    }

    // process slides
    if ($slides) {
      $slideUris = $this->userImageService
                     ->getImages($slides)
                     ->map(function ($image) {
                        return $image->uri;
                     })->toArray();
    } else {
      $slideUris = $this->userImageService
                        ->getGallery($userId, null, true, 15)
                        ->map(function ($image) {
                          return $image->uri;
                        })->toArray();
    }

    // 如果客户端选择获取直接上传到S3 server的参数，则需要为之生成
    $presigned = null;
    if ($needGetUploadInfo) {
      $storagePrefix = sprintf('works/%s', $userId);
      $presigned = $this->storageService
                        ->getS3PresignedPost($storagePrefix, $workMimeType);
    }
    $task = $this->workService
                 ->createTwoStepUploadTask(
                    $userId, $musicId, $workName, $cover, $isDefaultCover, $slideUris,
                    $duration, $description, $noAccompaniment,
                    isset($presigned) ? $presigned->key : null,
                    $isPrivate, $chorusType, $originWorkId
                 );

    return (object) [
      'taskId'    => $task->taskId,
      'presigned' => $presigned ? (object) [
                        'url'     => $presigned->presinged->formAttributes['action'],
                        'method'  => $presigned->presinged->formAttributes['method'],
                        'contentType' => $presigned->presinged->formAttributes['enctype'],
                        'formData'  => $presigned->presinged->formInputs,
                      ] : null,
    ];
  }

  /**
   * Second step of 2-step work upload workflow
   *
   * @param string $userId
   * @param string $taskId          upload task id
   * @param ?UploadedFile $work     user work file
   * @param ?string $countryAbbr
   */
  public function makeTwoStepUpload(
    string $userId,
    string $taskId,
    ?UploadedFile $work,
    ?string $countryAbbr,
    ?string $realCountryAbbr
  ) {
    $task = $this->workService->getUserUploadTask($userId, $taskId);
    if ( ! $task) {
      throw new WorkUploadTaskNotExistsException();  
    }

    $uri = null;
    if ($task->resource && $this->storageService->has($task->resource)) {
      $uri = $task->resource;
    }
    // user uploaded work has higher priority
    if ($work) {
      $uri = $this->storageService->store($work->path(), [
        'prefix'  => sprintf('works/%s', $userId),
        'mime'    => $work->getClientMimeType(),
      ]);
    }
    if ( ! $uri) {
      throw new WorkUploadFailedException('upload work failed');
    }

    $work = $this->workService
                 ->addUserWork(
                    $userId, $task->musicId, $task->workName,
                    $uri, $task->cover, $task->isDefaultCover,
                    $task->slides, $task->duration, $task->description,
                    $task->noAccompaniment, $task->isPrivate,
                    $task->chorusType, $task->originWorkId,
                    $countryAbbr
                  );

    if ( ! $work) {
      throw new WorkUploadFailedException();
    }

    $this->workService->deleteTaskAfterUploaded($taskId);
    if ( ! $task->isPrivate) {
      $this->newsService->createNews($userId, News::TYPE_PUBLISH, $work->workId, null);
      // 完成每日任务
      $this->dailyTaskService->resetDailyTaskLists($userId, $realCountryAbbr);
      $this->dailyTaskService->finisheDailyTask($userId, DailyTask::TYPE_PUBLISH);
      event(new WorkPublishedEvent($work->workId));
    }

    // 更新作品和用户的人气值
    $this->updateWorkRelatedPopularity($work->workId);

    // 更新作品标签相关信息
    $this->updateWorkTagInfo($work->workId,$task->description);
    return (object) [
      'workId'  => $work->workId,
      'url'     => $this->storageService->toHttpUrl($uri),
    ];
  }

  /**
   * User upload his/her work
   *
   * @param string $userId          user id
   * @param string $musicId         music id which work belongs to
   * @param UploadedFile $work      user work file
   * @param string $clientId        generated by client, prevent user from uploading more than once
   * @param int $duration           how much time the works will play
   * @param ?string $coverImageId   cover image id
   * @param array $slides           elements are slide image id
   * @param string $description     work description
   *
   * @throw MusicNotExistsException
   */
  public function upload(
    string $userId,
    string $musicId,
    UploadedFile $work,
    string $clientId,
    int $duration,
    ?string $coverImageId,
    array $slides,
    string $description
  ) {
    $workId = $this->workService->getUploadedWork($clientId);
    if ($workId) {
      throw new WorkAreadyUploadedException();
    }

    if ( ! $this->musicService->musicExists($musicId)) {
      throw new MusicNotExistsException();
    }

    // process cover
    if ($coverImageId) {
      $isDefaultCover = false;
      $image = $this->userImageService
                     ->getImages([$coverImageId])
                     ->first();
      if ( ! $image) {
        throw new UserImageNotExistsException();
      }
      $cover = $image->uri;
    } else {
      $isDefaultCover = true;
      $cover = $this->userImageService->getAvatar($userId);
    }

    // process slides
    if ($slides) {
      $slideUris = $this->userImageService
                     ->getImages($slides)
                     ->map(function ($image) {
                        return $image->uri;
                     })->toArray();
    } else {
      $slideUris = $this->userImageService
                        ->getGallery($userId, null, true, 15)
                        ->map(function ($image) {
                          return $image->uri;
                        })->toArray();
    }

    $uri = $this->storageService->store($work->path(), [
      'prefix'  => sprintf('works/%s', $userId),
      'mime'    => $work->getClientMimeType(),
    ]);

    $work = $this->workService
                 ->addUserWork(
                    $userId, $musicId, null, $uri, $cover, $isDefaultCover,
                    $slideUris, $duration, $description, false);

    if ( ! $work) {
      $this->storageService->remove($uri);
      throw new WorkUploadFailedException();
    }

    return (object) [
      'workId'  => $work->workId,
      'url'     => $this->storageService->toHttpUrl($uri),
    ];
  }

  /**
   * Get work upload status
   *
   * @param string $clientId
   *
   * @return \stdClass          properties as below:
   *                            - isFinished bool       is upload finished
   *                            - workId ?string        work id if work uploaded finished, or null
   */
  public function getWorkUploadStatus(string $clientId) : \stdClass
  {
    $workId = $this->workService->getUploadedWork($clientId);

    return (object) [
      'isFinished'  => $workId ? true : false,
      'workId'      => $workId,
    ];
  }

  /**
   * User delete his/her self work
   *
   * @param string $userId
   * @param string $workId
   */
  public function deleteWork(string $userId, string $workId)
  {
    $work = $this->workService->getDetail($workId, true);
    if ( ! $work) {
      throw new WorkNotExistsException();
    }

    if ($work->userId != $userId) {
      throw new WorkActionForbiddenException();
    }

    event(new WorkDeletedEvent($workId));

    $this->workService->deleteWork($workId);
  }

  /**
   * Get h5 selected works
   *
   * @param string $countryAbbr
   *
   * @return Collection         properties as below:
   *                            - selectionId string  work selection id
   *                            - workId string       work id
   *                            - user \stdClass      user info
   *                              - userId string     user id
   *                              - avatar array      user avatar url
   *                              - nickname string   user nickname
   *                            - music \stdClass
   *                              - name string       music name
   *                            - cover string        cover image url
   *                            - listenCount int     work listened count by users
   *                            - commentCount  int
   *                            - favouriteCount int
   *                            - transmitCount int
   *                            - description string
   *                            - createdAt Carbon
   *                            - artists string
   *                            - shareLink string
   */
  public function getH5Selections(string $countryAbbr) : Collection
  {
    $selections = $this->workService->getH5Selections($countryAbbr);
    $userIds = [];
    $musicIds = [];
    $selections->each(function ($work, $_) use (&$userIds, &$musicIds, &$imageIds) {
      $userIds[] = $work->userId;
      $musicIds[] = $work->musicId;
    });

    $users = $this->userProfileService->getUserProfiles($userIds);
    $musics = $this->musicService->getMusics($musicIds);

    $self = $this;
    return $selections->map(function ($work, $_) use ($users, $musics, $self) {
      $user = $users->where('userId', $work->userId)->first();
      $music = $musics->where('musicId', $work->musicId)->first();
      if ( ! $music) {
        Log::alert('Data missed. work miss music', [
          'work_id'     => $work->workId,
          'music_id'    => $work->musicId,
        ]);

        return null;
      }

      return (object) [
        'selectionId' => $work->selectionId,
        'workId'      => $work->workId,
        'workName'    => $work->workName,
        'user'        => (object) [
                            'userId'    => $user->userId,
                            'avatar'    => $user->avatar
                                            ? $self->storageService->toHttpUrl($user->avatar) : null,
                            'nickname'  => $user->nickname,
                          ],
        'music'       => (object) [
                            'musicId'   => $music->musicId,
                            'name'      => $music->name,
                          ],
        'cover'       => $self->storageService->toHttpUrl($work->cover),
        'listenCount' => $work->listenCount,
        'commentCount'  => $work->commentCount,
        'favouriteCount'  => $work->favouriteCount,
        'transmitCount' => $work->transmitCount,
        'description'   => $work->description,
        'shareLink'     => secure_url(sprintf('c/page/works/%s', $work->workId)),
        'createdAt'     => $work->createdAt,
      ];
    })->filter(function ($work, $_) {
      return ! is_null($work); 
    });
  }

  /**
   * Get selection works
   *
   * @param string $userId
   * @param ?string $selectionId  for pagination
   * @param bool $isNext          for pagination
   * @param int $size             for pagination
   * @param ?string $countryAbbr  open nation operation if not null
   *
   * @return Collection         properties as below:
   *                            - selectionId string  work selection id
   *                            - workId string       work id
   *                            - user \stdClass      user info
   *                              - userId string     user id
   *                              - avatar array      user avatar url
   *                              - nickname string   user nickname
   *                            - music \stdClass
   *                              - name string       music name
   *                            - cover string        cover image url
   *                            - listenCount int     work listened count by users
   *                            - commentCount  int
   *                            - favouriteCount int
   *                            - transmitCount int
   *                            - description string
   *                            - resource string       work resource url
   *                            - createdAt Carbon
   *                            - artists string
   *                            - shareLink string
   *                            - chorusType ?int       work chorus type
   *                            - chorusCount int
   *                            - originWorkUser ?\stdClass
   *                              - userId string
   *                              - avatar string
   *                              - nickname string
   *                           - giftAmount      int gifts received
   *                           - giftCoinAmount   int coins amount received from gifts
   *                           - giftPopularity   popularity amount from gift
   *                           - workPopularity    total popularity
   */
  public function getSelections(
    string $userId,
    ?string $selectionId,
    bool $isNext,
    int $size,
    ?string $countryAbbr
  ) : Collection {
    $selections = $this->workService->getSelections($selectionId, $isNext, $size, $countryAbbr);
    $userIds = [];
    $musicIds = [];
    $originWorkIds = [];
    $selections->each(function ($work, $_) use (&$userIds, &$musicIds, &$imageIds, &$originWorkIds) {
      $userIds[] = $work->userId;
      $musicIds[] = $work->musicId;
      if ($work->originWorkId && ! in_array($work->originWorkId, $originWorkIds)) {
        $originWorkIds[] = $work->originWorkId;
      }
    });

    $originWorkUserMap = [];
    $originWorks = $this->workService->getWorksByIds(array_unique($originWorkIds));
    $originWorks->each(function ($work, $_) use (&$userIds, &$originWorkUserMap) {
      if ( ! in_array($work->userId, $userIds)) {
        $userIds[] = $work->userId;
      }
      $originWorkUserMap[$work->workId] = $work->userId;
    });
    $users = $this->userProfileService->getUserProfiles(array_unique($userIds));
    $relationships = $this->friendService
                          ->getUserRelationship($userId, array_unique($userIds));
    $musics = $this->musicService->getMusics(array_unique($musicIds), true);

    return $selections->map(function ($work, $_) use ($users, $relationships, $musics, $originWorkUserMap) {
      // get origin work user
      $originWorkUser = null;
      if ($work->chorusType == WorkConstant::CHORUS_TYPE_JOIN) {
        $originWorkUserId = $originWorkUserMap[$work->originWorkId];
        $originWorkUser = $users->where('userId', $originWorkUserId)->first();
      }
      $user = $users->where('userId', $work->userId)->first();
      $relationship = $relationships->where('userId', $work->userId)->first();
      $music = $musics->where('musicId', $work->musicId)->first();
      if ( ! $music) {
        Log::alert('Data missed. work miss music', [
          'work_id'     => $work->workId,
          'music_id'    => $work->musicId,
        ]);

        return null;
      }

      $res = (object) [
        'selectionId' => $work->selectionId,
        'workId'      => $work->workId,
        'workName'    => $work->workName,
        'user'        => (object) [
                            'userId'    => $user->userId,
                            'avatar'    => $user->avatar
                                            ? $this->storageService->toHttpUrl($user->avatar) : null,
                            'nickname'  => $user->nickname,
                            'popularity' => $user->popularity_herarchy->popularity,
                            'hierarchyIcon' => $this->storageService->toHttpUrl($user->popularity_herarchy->icon),
                            'hierarchyName' => $user->popularity_herarchy->name,
                            'hierarchyGap' => $user->popularity_herarchy->gapPopularity,
                            'hierarchyLogo' => $this->storageService->toHttpUrl($user->popularity_herarchy->iconSmall),
                            'hierarchyAlias' => $user->popularity_herarchy->alias,
                            'isFollowing' => object_get($relationship, 'isFollowing', false),
                            'isFollower'  => object_get($relationship, 'isFollower', false),
                            'verified'  => $user->verified,
                          ],
        'music'       => (object) [
                            'musicId'   => $music->musicId,
                            'name'      => $music->name,
                          ],
        'cover'       => $this->storageService->toHttpUrl($work->cover),
        'listenCount' => $work->listenCount,
        'commentCount'  => $work->commentCount,
        'favouriteCount'  => $work->favouriteCount,
        'transmitCount' => $work->transmitCount,
        'description'   => $work->description,
        'resource'      => $this->storageService->toHttpUrl($work->resource),
        'shareLink'     => secure_url(sprintf('c/page/works/%s', $work->workId)),
        'chorusType'    => $work->chorusType,
        'chorusCount'   => $work->chorusCount,
        'originWorkUser'  => null,
        'createdAt'     => $work->createdAt,
        'giftAmount'      => $work->giftAmount,
        'giftCoinAmount'  => $work->giftCoinAmount,
        'giftPopularity'  => $work->giftPopularity,
        'workPopularity'  => $work->workPopularity,
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
   * Get all latest works
   *
   * @param string $userId        current login user id
   * @param ?string $workId       work id for pagination
   * @param bool $isNext          for pagination
   * @param int $size
   *
   * @return \Collection          elements are \stdClass, properties as below
   *                              - workId string         work id
   *                              - workName ?string
   *                              - user \stdClass
   *                                - userId string
   *                                - avatar string         user avatar url
   *                                - nickname string       user nickname
   *                                - popularity    int
   *                                - hierarchyIcon string
   *                                - hierarchyName string
   *                                - hierarchyGap  int
   *                                - hierarchyLogo string
   *                                - hierarchyAlias    string
   *                              - description string    work's description, default share text
   *                              - resource string       work resource url
   *                              - music \stdClass
   *                                - name string           music nmae
   *                              - cover string          music cover url
   *                              - listenCount int       work be listened count
   *                              - favouriteCount int    work favourite count
   *                              - commentCount int      total comments count
   *                              - transmitCount int     total transmit count from sing+
   *                              - createdAt string      Datetime, format: Y-m-d H:i:s
   *                              - friend \stdClass
   *                                - isFollowing bool
   *                                - isFollower bool
   *                            - chorusType ?int       work chorus type
   *                            - chorusCount int
   *                           - giftAmount      int gifts received
   *                           - giftCoinAmount   int coins amount received from gifts
   *                           - giftPopularity   popularity amount from gift
   *                           - workPopularity    total popularity
   */
  public function getLatestWorks(string $userId, ?string $workId, bool $isNext, int $size) : Collection
  {
    $works = $this->workService->getWorks($workId, $isNext, $size);

    $latestWorks =  $this->assembledWorks($works);

    // get friend info
    $authorIds = $latestWorks->map(function ($work, $_) {
                    return $work->user->userId;
                  })->toArray();
    $friends = $userId != "" ? $this->friendService->getUserRelationship($userId, $authorIds) : null;

    return $latestWorks->map(function ($work, $_) use ($friends) {
      $friend = $friends ? $friends->where('userId', $work->user->userId)->first() :null;
      $work->friend = (object) [
        'isFollowing' => $friend ? $friend->isFollowing : false,
        'isFollower'  => $friend ? $friend->isFollower : false,
      ];
      $work->shareLink = secure_url(sprintf('c/page/works/%s', $work->workId));

      return $work;
    });
  }

  /**
   * Get user all works
   *
   * @param string $invokeUserId
   * @param string $userId
   * @param ?string $workId       work id for pagination
   * @param bool $isNext          for pagination
   * @param int $size
   *
   * @return \Collection          elements are \stdClass, properties as below
   *                              - workId string         work id
   *                              - user \stdClass
   *                                - userId string
   *                                - avatar string         user avatar url
   *                                - nickname string       user nickname
   *                                - popularity    int
   *                                - hierarchyIcon string
   *                                - hierarchyName string
   *                                - hierarchyGap  int
   *                                - hierarchyLogo string
   *                                - hierarchyAlias    string
   *                              - description string    work's description, default share text
   *                              - music \stdClass
   *                                - name string           music nmae
   *                              - cover string          music cover url
   *                              - listenCount int       work be listened count
   *                              - favouriteCount int    work favourite count
   *                              - commentCount int      total comments count
   *                              - transmitCount int     total transmit count from sing+
   *                              - isPrivate bool
   *                              - createdAt string      Datetime, format: Y-m-d H:i:s
   *                              - chorusType ?int       work chorus type
   *                              - chorusCount int
   *                              - giftAmount      int gifts received
   *                              - giftCoinAmount   int coins amount received from gifts
   *                              - giftPopularity   popularity amount from gift
   *                              - workPopularity    total popularity
   */
  public function getUserWorks(
    string $invokeUserId,
    string $userId,
    ?string $workId,
    bool $isNext,
    int $size
  ) : Collection {
    $works = $this->workService->getUserWorks($invokeUserId, $userId, $workId, $isNext, $size);

    return $this->assembledWorks($works);
  }

  /**
   * @param string $workId
   */
  public function incrWorkListenCount(string $workId)
  {
    $this->workService->incrWorkListenCount($workId);
  }

  /**
   * Get works
   *
   * @param string $invokeUserId
   * @param string $workdId  used for pagination
   *
   * @return \stdClass        properties as below:
   *                          - workId string         work id
   *                          - user \stdClass
   *                            - userId string       user id
   *                            - avatar string       avatar uri
   *                            - nickname string     user nickname
   *                            - popularity    int
   *                            - hierarchyIcon string
   *                            - hierarchyName string
   *                            - hierarchyGap  int
   *                            - hierarchyLogo string
   *                            - hierarchyAlias    string
   *                            - followerCount int
   *                          - music \stdClass
   *                            - musicId string      music id
   *                            - name string         music name
   *                          - isFollow bool
   *                          - cover string          cover image uri
   *                          - slides array          elements are image uri
   *                          - description string    work description, default for share text
   *                          - listenCount int       work listen count by others
   *                          - commentCount int      work comment count by others (not include replies)
   *                          - favouriteCount int    work favourite count by others
   *                          - isFavourite bool      indicate this work aready be favourite by current user
   *                          - transmitCount int     work be transmit count by others from sing+
   *                          - noAccompaniment bool
   *                          - favourites Collection
   *                            - userId string
   *                            - avatar string
   *                          - chorusType ?int       null stands for solo
   *                          - chorusStartInfo ?\stdClass  exists only if work is chorus start
   *                            - chorusCount int
   *                          - chorusJoinInfo ?\stdClass   exists only if work is chorus join
   *                            - originWorkId
   *                            - author \stdClass
   *                              - userId string
   *                              - nickname string
   *                              - avatar string
   *                              - signature string
   *                              - popularity    int
   *                              - hierarchyIcon string
   *                              - hierarchyName string
   *                              - hierarchyGap  int
   *                              - hierarchyLogo string
   *                              - hierarchyAlias    string
   *                              - friend ?\stdClass
   *                                - isFollowing bool
   *                                - isFollower bool
   *                          - createdAt string      datetime, format: Y-m-d H:i:s
   *                          - giftAmount int amount of gifts received
   *                          - giftCoinAmount int amount of coins received from gifts
   *                          - giftPopularity   int amount of popularity from gifts
   *                          - workPopularity   int the total popularity of work
   */
  public function getDetail(string $workId, bool $isH5Page, ?string $userId = "") : ?\stdClass
  {
    $work = $this->workService->getDetail($workId);
    if ( ! $work) {
      return null;
    }

    // 如果作品被设置为私密作品后，提示设置为了私密作品, 且不是h5页面访问时
    if ($work->isPrivate && $work->userId != $userId && !$isH5Page){

        throw new WorkIsPrivateException();
    }

    $user = $this->userProfileService->fetchUserProfile($work->userId);
    $music = $this->musicService->getMusics((array) $work->musicId, true)->first();
    $assembledWork = $this->assembleWork($work, $user, $music);

    if ( ! $assembledWork) {
      throw new WorkNotExistsException('work song not exists');
    }

    $self = $this;
    $assembledWork->slides = $work->slides->map(function ($uri, $_) use ($self) {
                              return $self->storageService->toHttpUrl($uri); 
                            });
    // todo check follow info
    $assembledWork->user->isFollow = false;
    $assembledWork->user->followerCount = $this->friendService->countUserFollowers($work->userId);

    $assembledWork->isFavourite = ($userId != "")
                                  ? $this->workService->isFavourite($userId, $workId)
                                  : false;

    // 客户端需要传长链接，满足分国家运营需求，客户端对此链接增加query参数，国家码和userid
//    $assembledWork->shareLink = $this->getShareLink($workId);
    $assembledWork->shareLink = secure_url(sprintf('c/page/works/%s', $workId));

    // get favourite list
    $favourites = $this->workService->getWorkFavourite($workId, null, true, 7);
    $userIds = $favourites->map(function ($favourite) {
                  return $favourite->userId;
                })->toArray();
    $profiles = $this->userProfileService->getUserProfiles($userIds);
    $favourites = $favourites->map(function ($favourite) use ($profiles) {
                  $profile = $profiles->where('userId', $favourite->userId)->first();
                  return (object) [
                    'userId'  => $favourite->userId,
                    'avatar'  => $this->storageService->toHttpUrl($profile->avatar),
                  ];
                });
    $assembledWork->favourites = $favourites;

    // handle chorus join
    $originWorkUserId = null;
    if ($originWorkId = object_get($assembledWork, 'chorusJoinInfo.originWorkId')) {
      $originWork = $this->workService->getDetail($originWorkId, true, true);  
      $originWorkUserId = $originWork->userId;
      $originWorkProfile = $this->userProfileService->fetchUserProfile($originWorkUserId);
      $assembledWork->chorusJoinInfo->description = $originWork->description;
      $assembledWork->chorusJoinInfo->author = (object) [
        'userId'    => $originWorkUserId,
        'nickname'  => $originWorkProfile->nickname,
        'avatar'    => $this->storageService->toHttpUrl($originWorkProfile->avatar),
        'signature' => $originWorkProfile->signature,
        'popularity' => $originWorkProfile->popularity_herarchy->popularity,
        'hierarchyIcon' => $this->storageService->toHttpUrl($originWorkProfile->popularity_herarchy->icon),
        'hierarchyName' => $originWorkProfile->popularity_herarchy->name,
        'hierarchyGap' => $originWorkProfile->popularity_herarchy->gapPopularity,
        'hierarchyLogo' => $this->storageService->toHttpUrl($originWorkProfile->popularity_herarchy->iconSmall),
        'hierarchyAlias' => $originWorkProfile->popularity_herarchy->alias,
      ];
    }

    // get friend info
      $targetUserIds = [$work->userId];
      if ($originWorkUserId && $work->userId != $originWorkUserId) {
          array_push($targetUserIds, $originWorkUserId);
      }
      $friends = $this->friendService
          ->getUserRelationship($userId, $targetUserIds);
      $workFriend = $friends->where('userId', $work->userId)->first();
      $assembledWork->friend = (object) [
          'isFollowing' => $workFriend->isFollowing,
          'isFollower'  => $workFriend->isFollower,
      ];
      if ($originWorkUserId) {
          if ($originWorkUserId != $work->userId) {
              $originWorkFriend = $friends->where('userId', $originWorkUserId)->first();
              $assembledWork->chorusJoinInfo->friend = (object) [
                  'isFollowing' => $originWorkFriend ?
                      $originWorkFriend->isFollowing : false,
                  'isFollower'  => $originWorkFriend ?
                      $originWorkFriend->isFollower : false,
              ];
          } else {
              $assembledWork->chorusJoinInfo->friend = $assembledWork->friend;
          }
      }

    return $assembledWork;
  }

  private function getShareLink(string $workId) : string
  {
    $url = secure_url(sprintf('c/page/works/%s', $workId));
    try {
      $shortUrlKey = sprintf('work:%s:surl', $workId);
      $shortUrl = Cache::get($shortUrlKey);
      
      if ( ! $shortUrl) {
        $shortUrl = $this->urlShortener->shorten($url);
        Cache::forever($shortUrlKey, $shortUrl);
      }

      return $shortUrl;
    } catch (\Exception $ex) {
      return $url;
    }
  }

  /**
   * User comment work
   *
   * @param string $userId
   * @param string $comment
   * @param string $workId      work which comment belongs to
   * @param string $commentId   comment which new comment belongs to
   * @param int $commentType   type of comment
   * @param string $countryAbbr   string the national abbr of user
   * @param string $repliedId      string the replied userid for SendGift Comment
   * @param string $giftFeedId     string the gift feed id
   *                                valaible with commentType for TYPE_SEND_GIFT
   *
   * @return \stdClass          properties as below:
   *                            - commentId string new comment id
   */
  public function commentWork(
    string $userId,
    string $comment,
    string $workId,
    ?string $commentId,
    ?int $commentType,
    ?string $countryAbbr,
    ?string $repliedId,
    ?string $giftFeedId
  ) : \stdClass {
    $comment =  $this->workService->comment($userId, $comment, $workId, $commentId,$commentType, $repliedId,$giftFeedId);
    // 是否触发推送通知, 自己评论自己不再收到通知
    $repliedUserId  = $comment->repliedUserId;
    if ($repliedUserId != $userId){
        event(new UserCommentWorkEvent($comment->commentId, UserCommentWorkEvent::ACTION_NEW));
    }

    // 完成每日评论任务，回复评论不算，自己和别人都作品都可以，不包括转发自动生成的评论
    if ($commentType == null || $commentType == Comment::TYPE_NORMAL){
        if ($commentId == null){
            $this->dailyTaskService->resetDailyTaskLists($userId, $countryAbbr);
            $this->dailyTaskService->finisheDailyTask($userId, DailyTask::TYPE_COMMENT);
        }
    }

    // 更新作品和用户的人气值
    $this->updateWorkRelatedPopularity($workId);
    // 更新缓存的评论 for getMultiWorksCommentsAndGifts
    event(new WorkUpdateCommentGiftCacheDataEvent($workId, null, $comment->commentId));
    return $comment;
  }

  /**
   * Synthetic user comment work
   *
   * @param string $syntheticUserId
   * @param string $comment
   * @param string $workId      work which comment belongs to
   * @param string $commentId   comment which new comment belongs to
   *
   * @return \stdClass          properties as below:
   *                            - commentId string new comment id
   */
  public function syntheticUserCommentWork(
    string $syntheticUserId,
    string $comment,
    string $workId,
    ?string $commentId
  ) {
    $this->checkSyntheticUser($syntheticUserId);

    $comment =  $this->workService->comment($syntheticUserId, $comment, $workId, $commentId, null, null);
    $repliedUserId  = $comment->repliedUserId;
    if ($repliedUserId != $syntheticUserId){
        event(new UserCommentWorkEvent($comment->commentId, UserCommentWorkEvent::ACTION_NEW));
    }
    // 更新作品和用户的人气值
    $this->updateWorkRelatedPopularity($workId);
    return $comment;
  }

  /**
   * User delete his/her comment
   *
   * @param string $userId
   * @param string $commentId
   */
  public function deleteWorkComment(string $userId, string $commentId)
  {
    $comment = $this->workService->getComment($commentId);
    if ( ! $comment) {
      throw new CommentNotExistsException();
    }
    if ($comment->authorId != $userId) {
      throw new CommentActionForbiddenException();
    }

    $this->workService->deleteComment($commentId);
    // 删除评论不再收到通知
    //event(new UserCommentWorkEvent($comment->commentId, UserCommentWorkEvent::ACTION_DELETE));
  }

  private function assembleWork($work, $user, $music) : ?\stdClass
  {
    if ( ! $music) {
      Log::alert('Data missed. work miss music', [
        'work_id'   => $work->workId,
        'music_id'  => $work->musicId,
      ]);

      return null;
    }
    $res = (object) [
      'workId'  => $work->workId,
      'workName'  => $work->workName,
      'user'    => (object) [
                    'userId'  => $user->getUserId(),
                    'avatar'  => $this->storageService->toHttpUrl($user->avatar),
                    'nickname'  => $user->nickname,
                    'verified'  => $user->verified,
                    'popularity' => $user->popularity_herarchy->popularity,
                    'hierarchyIcon' => $this->storageService->toHttpUrl($user->popularity_herarchy->icon),
                    'hierarchyName' => $user->popularity_herarchy->name,
                    'hierarchyGap' => $user->popularity_herarchy->gapPopularity,
                    'hierarchyLogo' => $this->storageService->toHttpUrl($user->popularity_herarchy->iconSmall),
                    'hierarchyAlias' => $user->popularity_herarchy->alias,
                  ],
      'music' => (object) [
                    'musicId'   => $music->musicId,
                    'name'      => $music->name,
                    'lyric'     => $this->storageService->toHttpUrl($music->lyric),
                    'artists'   => $music->artists,
                  ],
      'cover'   => $this->storageService->toHttpUrl($work->cover),
      'resource'  => $this->storageService->toHttpUrl($work->resource),
      'description' => $work->description,
      'listenCount' => $work->listenCount,
      'favouriteCount'  => $work->favouriteCount,
      'commentCount'    => $work->commentCount,
      'transmitCount'   => $work->transmitCount,
      'createdAt'       => $work->createdAt,
      'duration'        => $work->duration,
      'noAccompaniment' => $work->noAccompaniment,
      'chorusType'      => $work->chorusType,
      'giftAmount'      => $work->giftAmount,
      'giftCoinAmount'  => $work->giftCoinAmount,
      'giftPopularity'  => $work->giftPopularity,
      'workPopularity'  => $work->workPopularity,
      'isPrivate'       => $work->isPrivate,
    ];

    if (object_get($work, 'chorusStartInfo')) {
      $res->chorusStartInfo = (object) [
        'chorusCount' => $work->chorusStartInfo->chorusCount,
      ];
    }
    if (object_get($work, 'chorusJoinInfo')) {
      $res->chorusJoinInfo = (object) [
        'originWorkId'  => $work->chorusJoinInfo->originWorkId,
      ];
    }

    return $res;
  }

  /**
   * Get work comments
   *
   * @param string $userId
   * @param string $workId      all comments belong to this work
   * @param ?string $commentId  for pagination
   * @param bool $isNext        for pagination
   * @param int $size           for pagination
   *
   * @return Collection         elements are \stdClass, properties as below:
   *                            - commentId string
   *                            - repliedCommentId ?string    replied comment id
   *                            - author
   *                              - userId string
   *                              - avatar string     author avatar url
   *                              - nickname string   author nickname
   *                            - date string       comment creatd at, format: Y-m-d H:i:s
   *                            - content string    comment content
   *                            - repliedUser
   *                              - userId          replied user id
   *                              - nickname string replied user nickname
   */
  public function getWorkComments(
    string $workId,
    ?string $commentId,
    bool $isNext,
    int $size
  ) : Collection {
    $comments = $this->workService->getWorkComments(
      $workId, $commentId, $isNext, $size
    );

    $userIds = [];
    $comments->each(function ($comment, $_) use (&$userIds) {
      $userIds[] = $comment->authorId;
      $userIds[] = $comment->repliedUserId;
    });

    $users = $this->userProfileService->getUserProfiles($userIds);

    $self = $this;
    return $comments->map(function ($comment, $_) use ($users, $self) {
      $author = $users->where('userId', $comment->authorId)->first();
      $repliedUser = $users->where('userId', $comment->repliedUserId)->first();
      return (object) [
        'commentId'     => $comment->commentId,
        'repliedCommentId'  => $comment->repliedCommentId,
        'author'  => (object) [
                        'userId'    => $author->userId,
                        'avatar'    => $author->avatar
                            ? $self->storageService->toHttpUrl($author->avatar)
                            : '',
                        'nickname'  => $author->nickname,
                      ],
        'createdAt' => $comment->createdAt,
        'content'   => $comment->content,
        'repliedUser' => (object) [
                        'userId'    => $repliedUser->userId,
                        'nickname'  => $repliedUser->nickname,
                      ],
        'commentType' => $comment->commentType,
      ];
    });
  }

  /**
   * Get user related comments
   *
   * @param string $userId
   * @param ?string $commentId          used for pagination
   * @param bool $isNext                used for pagination
   * @param int $size                   used for pagination
   *
   * @return Collection                 elements are \stdClass, properties as below
   *                                    - commentId string
   *                                    - repliedCommentId ?string    replied comment id
   *                                    - author \stdClass
   *                                      - userId string
   *                                      - nickname string
   *                                      - avatar string
   *                                    - music \stdClass
   *                                      - musicId string
   *                                      - name string
   *                                    - work  \stdClass
   *                                      - workId string
   *                                    - repliedComment ?\stdClass   this field not null if it's
   *                                                                  some comment's reply
   *                                      - commentId string
   *                                      - content string
   *                                    - content string
   *                                    - createdAt \Carbon\Carbon
   *                                      
   */
  public function getUserRelatedComments(
    string $userId,
    ?string $commentId,
    bool $isNext,
    int $size
  ) : Collection {
    $comments = $this->workService
                     ->getUserRelatedComments($userId, $commentId, $isNext, $size);
    $authorIds = [];
    $musicIds = [];
    $repliedUserIds = $comments->each(function ($comment, $_) use (&$authorIds, &$musicIds) {
                        $authorIds[] = $comment->authorId;
                        $musicIds[] = $comment->musicId;
                      });

    $authors = $this->userProfileService->getUserProfiles($authorIds);
    $musics = $this->musicService->getMusics($musicIds);

    $self = $this;
    return $comments->map(function ($comment, $_) use ($authors, $musics, $self) {
      $author = $authors->where('userId', $comment->authorId)->first();
      $music = $musics->where('musicId', $comment->musicId)->first();

      if ( ! $music) {
        Log::alert('Data missed. userRelatedComment miss music', [
          'comment_id'  => $comment->commentId,
          'music_id'    => $comment->musicId,
        ]);

        return null;
      }

      $comment->author = (object) [
                            'userId'  => $author->userId,
                            'avatar'  => $author->avatar
                                          ? $self->storageService->toHttpUrl($author->avatar)
                                          : null,
                            'nickname'  => $author->nickname,
                          ];
      $comment->music = (object) [
                            'musicId'   => $music->musicId,
                            'name'      => $music->name,
                          ];
      return $comment;
    })->filter(function ($comment, $_) {
      return ! is_null($comment); 
    });
  }

  /**
   * User favourit work
   *
   * @param string $userId
   * @param string $workId
   *
   * @return integer            favourite number in this action
   *                            postive integer stand for add favourite number. eg: 1
   *                            negative integer stand for cancel favourite number. eg: -1
   *                            zero stand for nothing happend
   */
  public function favouriteWork(string $userId, string $workId) : int
  {
    $favourite = $this->workService->favouriteWork($userId, $workId);
    $action = $favourite->increments > 0 ?
                  UserTriggerFavouriteWorkEvent::ACTION_ADD :
                  UserTriggerFavouriteWorkEvent::ACTION_CANCEL;
    //是否触发推送通知
    $isCanceled = $favourite->isCanceled;
    $workUserId = $favourite->workUserId;
    if ($userId != $workUserId && $action == UserTriggerFavouriteWorkEvent::ACTION_ADD
        && $isCanceled == false){
        event(new UserTriggerFavouriteWorkEvent($favourite->favouriteId, $action));
        // 更新作品和用户的人气值
        $this->updateWorkRelatedPopularity($workId);
    }

    // 更新缓存该用户是否喜欢了这个作品
    event(new WorkUpdateUserFavouriteCacheDataEvent($workId, $userId, $favourite->increments > 0));

    return $favourite->increments;
  }

  /**
   * Synthetic user favourit work
   *
   * @param string $syntheticUserId
   * @param string $workId
   *
   * @return void
   */
  public function syntheticUserFavouriteWork(string $syntheticUserId, string $workId)
  {
    $this->checkSyntheticUser($syntheticUserId);

    if ($this->workService->isFavourite($syntheticUserId, $workId)) {
      return null;
    }
    $favourite = $this->workService->favouriteWork($syntheticUserId, $workId);
    if ($favourite->increments > 0) {
      event(new UserTriggerFavouriteWorkEvent(
        $favourite->favouriteId,
        UserTriggerFavouriteWorkEvent::ACTION_ADD
      ));
      // 更新作品和用户的人气值
      $this->updateWorkRelatedPopularity($workId);
    }

    return null;
  }

  /**
   * Get multi works comments and favourite for list
   *
   * @param array $workIds
   *
   * @return collect        key is work id, value is \stdClass, as below:
   *                        - workId string
   *                        - favourites array    elements as below:
   *                          - userId string
   *                          - avatar string
   *                        - comments array      elements as below:
   *                          - commentId string
   *                          - repliedCommentId  ?string   stand for replied to a comment if not null
   *                          - author \stdClass
   *                            - userId string
   *                            - nickname string
   *                          - repliedUser \stdClass
   *                            - userId string
   *                            - nickname string
   *                          - content string
   */
  public function getMultiWorksCommentsAndFavourite(string $userId, array $workIds) : Collection 
  {
    $size = 3;
    $res = collect();
    $userIds = [];
    $expireAfter = 0;   // minutes
    foreach ($workIds as $workId) {
      $cacheKey = sprintf('worklist:%s:favourite:comment', $workId);
      if ($expireAfter && ($cacheData = Cache::get($cacheKey))) {
        $data = $cacheData; 
      } else {
        $userFavouriteWorksStatus = $userId != "" ? $this->workService
                                         ->getUserFavouriteStatusOfMultiWorks(
                                            $userId, $workIds) : null;
                                              
        $favourites = $this->workService->getWorkFavourite($workId, null, true, $size);
        $comments = $this->workService->getWorkComments($workId, null, true, $size);
        $favourites->each(function ($favourite) use (&$userIds) {
          $userIds[] = $favourite->userId;
        });
        $comments->each(function ($comment) use (&$userIds) {
          $userIds[] = $comment->authorId;
          $userIds[] = $comment->repliedUserId;
        });

        $users = $this->userProfileService->getUserProfiles($userIds);

        $favouritesRes = [];
        foreach ($favourites as $favourite) {
          $user = $users->where('userId', $favourite->userId)->first();
          if ( ! $user) {
            continue;
          }

          $favouritesRes[] = (object) [
            'userId'  => $user->userId,
            'avatar'  => $this->storageService->toHttpUrl($user->avatar),
          ];
        }
        $commentsRes = [];
        foreach ($comments as $comment) {
          $author = $users->where('userId', $comment->authorId)->first();
          $repliedUser = $users->where('userId', $comment->repliedUserId)->first();
          if ( ! $author) {
            continue;
          }

          $commentsRes[] = (object) [
            'commentId'         => $comment->commentId,
            'repliedCommentId'  => $comment->repliedCommentId,
            'author'            => (object) [
                                      'userId'    => $comment->authorId,
                                      'nickname'  => $author->nickname,
                                  ],
            'repliedUser'       => (object) [
                                      'userId'    => $comment->repliedUserId,
                                      'nickname'  => $repliedUser->nickname,
                                  ],
            'content'           => $comment->content,
          ];
        }

        $data = (object) [
          'isFavourite' => $userFavouriteWorksStatus ?array_get($userFavouriteWorksStatus, $workId, false):false,
          'workId'      => $workId,
          'favourites'  => $favouritesRes,
          'comments'    => $commentsRes,
        ];

        if ($expireAfter > 0) {
          Cache::put($cacheKey, $data, $expireAfter);
        }
      }

      $res->push($data);
    }

    return $res;
  }

  /**
   * Get work favourites
   *
   * @param string $userId
   * @param string $workId
   * @param ?string $id           for pagination
   * @param bool $isNext          for pagination
   * @param int $size             for pagination
   *
   * @return Collection           elements as below:
   *                              - id string         for pagination
   *                              - favouriteId string
   *                              - userId string
   *                              - nickname string
   *                              - avatar string
   *                              - signature string
   */
  public function getWorkFavourites(
    string $userId,
    string $workId, 
    ?string $id,
    bool $isNext,
    int $size
  ) : Collection {
    $favourites = $this->workService->getWorkFavourite($workId, $id, $isNext, $size);
    $userIds = $favourites->map(function ($favourite) {
                    return $favourite->userId;         
                  })->toArray();
    $profiles = $this->userProfileService->getUserProfiles($userIds);

    return $favourites->map(function ($favourite) use ($profiles) {
              $profile = $profiles->where('userId', $favourite->userId)->first(); 

              return (object) [
                'id'          => $favourite->favouriteId,
                'favouriteId' => $favourite->favouriteId,
                'userId'      => $favourite->userId,
                'avatar'      => $this->storageService->toHttpUrl($profile->avatar),
                'nickname'    => $profile->nickname,
                'signature'   => $profile->signature,
              ];
            });
  }

  /**
   * Get chorus start work accompaniment
   *
   * @param string $workId
   *
   * @return \stdClass        elements as below:
   *                          - author \stdClass
   *                            - userId string
   *                            - avatar string   author avatar url
   *                          - resource string   accompaniment resource url
   */
  public function getChorusStartAccompaniment(string $workId) : \stdClass
  {
    $work = $this->workService->getChorusStartAccompaniment($workId);
    $profile = $this->userProfileService->fetchUserProfile($work->userId);

    return (object) [
      'author'    => (object) [
                      'userId'  => $work->userId,
                      'avatar'  => $this->storageService->toHttpUrl($profile->avatar),
                    ],
      'resource'  => $this->storageService->toHttpUrl($work->resource),
    ];
  }

  /**
   * Get user chorus start works
   *
   * @param string $authUserId
   * @param string $userId
   * @param ?string $id           for pagination
   * @param bool $isNext          for pagination
   * @param int $size             for pagination
   *
   * @return Collection           elements as below
   *                              - id string     for pagination
   *                              - workId string
   *                              - cover string  work cover
   *                              - musicId string  work music id
   *                              - musicName string
   *                              - chorusCount int
   */
  public function getUserChorusStartWorks(
    string $authUserId,
    string $userId,
    ?string $id,
    bool $isNext,
    int $size
  ) {
    $works = $this->workService->getUserChorusStartWorks(
      $authUserId, $userId, $id, $isNext, $size
    );
    
    $musicIds = $works->map(function ($work, $_) {
                    return $work->musicId;
                  })->toArray();

    $musics = $this->musicService->getMusics($musicIds, true);

    return $works->map(function ($work, $_) use ($musics) {
      $music = $musics->where('musicId', $work->musicId)->first();
      if ( ! $music) {
        return null;
      }
      return (object) [
        'id'          => $work->workId,
        'workId'      => $work->workId,
        'cover'       => $this->storageService->toHttpUrl($work->cover),
        'musicId'     => $music->musicId,
        'musicName'   => $music->name,
        'chorusCount' => $work->chorusCount,
      ];
    })->filter(function ($work, $_) {
      return ! is_null($work); 
    });
  }

  /**
   * Get user chorus start works
   *
   * @param string $authUserId
   * @param string $userId
   * @param ?string $id           for pagination
   * @param bool $isNext          for pagination
   * @param int $size             for pagination
   *
   * @return Collection           elements as below
   *                              - id string     for pagination
   *                              - workId string
   *                              - author \stdClass
   *                                - userId string
   *                                - avatar string   author user avatar url
   *                                - nickname string
   *                              - createdAt \Carbon\Carbon
   */
  public function getChorusJoinsOfChorusStart(
    string $userId,
    string $chorusStartWorkId,
    ?string $id,
    bool $isNext,
    int $size
  ) {
    $works = $this->workService
                  ->getChorusJoinsOfChorusStart($chorusStartWorkId, $id, $isNext, $size);
    $userIds = $works->map(function ($work, $_) {
                  return $work->userId;
                })->toArray();
    $profiles = $this->userProfileService->getUserProfiles($userIds);

    return $works->map(function ($work, $_) use ($profiles) {
      $profile = $profiles->where('userId', $work->userId)->first();
      return (object) [
        'id'        => $work->workId,
        'workId'    => $work->workId,
        'author'    => (object) [
                        'userId'    => $profile->userId,
                        'avatar'    => $this->storageService->toHttpUrl($profile->avatar),
                        'nickname'  => $profile->nickname,
                      ],
        'createdAt' => $work->createdAt,
      ];
    });
  }

  /**
   * @param string $workId
   */
  public function handleChorusJoinWorkPublished(string $workId) : ?string
  {
    $work = $this->workService->getDetail($workId, true);
    // if work not chorus join work, do nothing
    if ( ! $work || $work->chorusType != WorkConstant::CHORUS_TYPE_JOIN) {
      return null;
    }
    $originWork = $this->workService->getDetail($work->chorusJoinInfo->originWorkId, true);
    if ( ! $originWork || $originWork->chorusType != WorkConstant::CHORUS_TYPE_START) {
      return null;
    }

    $musics = $this->musicService->getMusics([$work->musicId, $originWork->musicId]);

    // Add count for origin work
    $this->workService->incrWorkChorusCount($originWork->workId);

    // 自己参与自己的合唱不再收到通知和推送消息
    if ($originWork->userId == $work->userId){
        return null;
    }

    // create work join feed
    $workName = $work->workName ?: $musics->where('musicId', $work->musicId)->first()->name;
    $originWorkName = $originWork->workName ?: $musics->where('musicId', $originWork->musicId)->first()->name;
    $feedId = $this->feedService->createWorkChorusJoinFeed(
      $originWork->userId,
      $work->userId,
      $originWork->workId,
      $originWorkName,
      $work->workId,
      $workName,
      $work->description
    );

    // chorus join feed create event
    event(new FeedCreatedEvent($feedId));

    return $feedId;
  }

  /**
   * Get recommend work sheet
   *
   * @param string $userId
   * @param string $sheetId     sheet id
   *
   * @return ?\stdClass         elements as below
   *                            - cover string         cover image uri
   *                            - title string
   *                            - works  Collection  properties as below:
   *                              - workId string     work id
   *                              - name string       work name
   *                              - cover string      work cover url
   *                              - description string
   *                              - listenCount int
   *                              - commentCount int
   *                              - transmitCount int
   *                              - favouriteCount int
   *                              - resource string
   *                              - music \stdClass
   *                                - musicId string
   *                                - name string
   *                              - user \stdClass
   *                                - userId string
   *                                - nickname string
   *                                - verified \stdClass
   *                                    - verified bool
   *                                    - names array
   *                                - avatar string
   *                                - popularity    int
   *                                - hierarchyIcon string
   *                                - hierarchyName string
   *                                - hierarchyGap  int
   *                                - hierarchyLogo string
   *                                - hierarchyAlias    string
   *                                - friend \stdClass
   *                                  - isFollowing bool
   *                                  - isFollower bool
   *                              - originWorkUser ?\stdClass
   *                                - userId string
   *                                - avatar string
   *                                - nickname string
   *                              - chorusType ?int
   *                              - chorusCount int
   *                              - shareLink string
   *                            - recommendText string
   *                            - createdAt \Carbon\Carbon
   *                           - giftAmount      int gifts received
   *                           - giftCoinAmount   int coins amount received from gifts
   *                           - giftPopularity   popularity amount from gift
   *                           - workPopularity    total popularity
   *
   */
  public function getRecommendWorkSheet(string $userId, string $sheetId) : ?\stdClass
  {
    $sheet = $this->workService->getRecommendWorkSheet($sheetId);
    if ( ! $sheet) {
      throw new WorkSheetNotExistsException();
    }

    $sheet->cover = $this->storageService->toHttpUrl($sheet->cover);
    $userIds = $sheet->works->map(function ($work, $_) {
                return $work->userId;
              })->toArray();
    $friends = $userId != "" ?$this->friendService->getUserRelationship($userId, array_unique($userIds)) : null;
    $sheet->works = $this->assembledWorks($sheet->works)
                         ->map(function ($work, $_) use ($friends) {
                            $friend = $friends ? $friends->where('userId', $work->user->userId)->first() : null;
                            $work->user->friend = (object) [
                              'isFollowing' => $friend ? $friend->isFollowing : false,
                              'isFollower' => $friend ? $friend->isFollower : false,
                            ];
                            $work->shareLink = secure_url(sprintf('c/page/works/%s', $work->workId));

                            return $work;
                         })
                         ->filter(function ($work, $_) {
                            return ! $work->isPrivate;
                         });
    return $sheet;
  }

    /**
     * Get multi works comments and favourite for list
     *
     * @param array $workIds
     *
     * @return collect        key is work id, value is \stdClass, as below:
     *                        - workId string
     *                        - gifts array    elements as below:
     *                          - userId string
     *                          - avatar string
     *                        - comments array      elements as below:
     *                          - commentId string
     *                          - repliedCommentId  ?string   stand for replied to a comment if not null
     *                          - author \stdClass
     *                            - userId string
     *                            - nickname string
     *                          - repliedUser \stdClass
     *                            - userId string
     *                            - nickname string
     *                          - content string
     */
    public function getMultiWorksCommentsAndGifts(string $userId, array $workIds) : Collection
    {
        $size = 3;
        $res = collect();
        $userIds = [];
        $expireAfter = 60 * 24;   // minutes
        foreach ($workIds as $workId) {
            $cacheKey = sprintf('worklist:%s:gifts:comment', $workId);
            if ($expireAfter && ($cacheData = Cache::get($cacheKey))) {
                $data = $cacheData;
            } else {

                $contributions = $this->giftService->getWorkGiftContribution($workId, null, true, $size);
                $comments = $this->workService->getWorkComments($workId, null, true, $size);
                $contributions->each(function ($contrib) use (&$userIds) {
                    $userIds[] = $contrib->userId;
                });
                $comments->each(function ($comment) use (&$userIds) {
                    $userIds[] = $comment->authorId;
                    $userIds[] = $comment->repliedUserId;
                });

                $users = $this->userProfileService->getUserProfiles(Arr::quickUnique($userIds));

                $contribRes = [];
                foreach ($contributions as $contrib) {
                    $user = $users->where('userId', $contrib->userId)->first();
                    if ( ! $user) {
                        continue;
                    }

                    $contribRes[] = (object) [
                        'userId'  => $user->userId,
                        'avatar'  => $this->storageService->toHttpUrl($user->avatar),
                    ];
                }
                $commentsRes = [];
                foreach ($comments as $comment) {
                    $author = $users->where('userId', $comment->authorId)->first();
                    $repliedUser = $users->where('userId', $comment->repliedUserId)->first();
                    if ( ! $author) {
                        continue;
                    }

                    $commentsRes[] = (object) [
                        'commentId'         => $comment->commentId,
                        'repliedCommentId'  => $comment->repliedCommentId,
                        'author'            => (object) [
                            'userId'    => $comment->authorId,
                            'nickname'  => $author->nickname,
                        ],
                        'repliedUser'       => (object) [
                            'userId'    => $comment->repliedUserId,
                            'nickname'  => $repliedUser->nickname,
                        ],
                        'content'           => $comment->content,
                    ];
                }

                $data = (object) [
                    'workId'      => $workId,
                    'gifts'  => $contribRes,
                    'comments'    => $commentsRes,
                ];

                if ($expireAfter > 0) {
                    Cache::put($cacheKey, $data, $expireAfter);
                }
            }
            $favouriteCacheKey = sprintf('workfavourite:user:%s:work:%s', $userId, $workId);
            $cacheFavourite = Cache::get($favouriteCacheKey);
            if (!$cacheFavourite){
                $data->isFavourite = $this->workService->isFavourite($userId, $workId);
                $this->updateWorkFavouriteCache($workId, $userId, $data->isFavourite);
            }else {
                $data->isFavourite = ($cacheFavourite == true);
            }
            $res->push($data);
        }

        return $res;
    }

    /**
     * update the cached data for work commentsAndGifts data
     *
     * @param string $workId
     * @param null|string $senderId
     * @param null|string $commentId
     */
    public function updateWorkCommentGiftCacheData(string $workId, ?string $senderId, ?string $commentId){
        $cacheKey = sprintf('worklist:%s:gifts:comment', $workId);
        $cacheData = Cache::get($cacheKey);
        $expireAfter = 60 * 24;
        if ($cacheData){
            if ($senderId){
                $gifts = $cacheData->gifts;
                if (collect($gifts)->contains('userId', $senderId)){
                    return;
                }
                $userProfile = $this->userProfileService->fetchUserProfile($senderId);
                if (!$userProfile){
                    return;
                }
                $contribRes =(object)[
                    'userId' => $senderId,
                    'avatar'  => $this->storageService->toHttpUrl($userProfile->avatar),
                ];
                array_unshift($gifts, $contribRes);
                $cacheData->gifts = collect($gifts)->take(3)->toArray();
                Cache::put($cacheKey, $cacheData, $expireAfter);
            }

            if ($commentId){
                $comments = $cacheData->comments;
                if (collect($comments)->contains('commentId', $commentId)){
                    return;
                }

                $comment = $this->workService->getComment($commentId);
                if (!$comment){
                    return;
                }

                $author = $this->userProfileService->fetchUserProfile($comment->authorId);
                if (!$author){
                    return;
                }
                $repliedUser = $this->userProfileService->fetchUserProfile($comment->repliedUserId);
                if (!$repliedUser){
                    return;
                }

                $commentsRes = (object) [
                    'commentId'         => $comment->commentId,
                    'repliedCommentId'  => $comment->repliedCommentId,
                    'author'            => (object) [
                        'userId'    => $comment->authorId,
                        'nickname'  => $author->nickname,
                    ],
                    'repliedUser'       => (object) [
                        'userId'    => $comment->repliedUserId,
                        'nickname'  => $repliedUser->nickname,
                    ],
                    'content'           => $comment->content,
                ];

                array_unshift($comments, $commentsRes);
                $cacheData->comments = collect($comments)->take(3)->toArray();
                Cache::put($cacheKey, $cacheData, $expireAfter);
            }
        }
    }

    /**
     * @param string $workId
     */
    public function doCleanAfterWorkDeleted(string $workId)
    {
        $work = $this->workService->getDetail($workId, true, true);
        if ($work && $work->chorusJoinInfo) {
            // 如果是参与合唱，则更新发起合唱作品的被唱次数
            $chorusStartWorkId = $work->chorusJoinInfo->originWorkId;
            $this->workService->decrWorkChorusCount($chorusStartWorkId);
        }
    }


  private function checkSyntheticUser(string $userId)
  {
    $user = $this->userService->fetchUser($userId);
    if ( ! $user) {
      throw new UserNotExistsException();
    }
    if ($user->source != UserConstant::SOURCE_SYNTHETIC) {
      throw new UserNotExistsException('synthetic user not exists');
    }
  }

  private function assembledWorks($works) : Collection
  {
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

      $workObject = (object) [
        'workId'  => $work->workId,
        'workName'  => $work->workName,
        'user'    => (object) [
                        'userId'    => $user->userId,
                        'avatar'    => $user->avatar
                                        ? $this->storageService->toHttpUrl($user->avatar) : null,
                        'nickname'  => $user->nickname,
                        'verified'  => $user->verified,
                        'popularity' => $user->popularity_herarchy->popularity,
                        'hierarchyIcon' => $this->storageService->toHttpUrl($user->popularity_herarchy->icon),
                        'hierarchyName' => $user->popularity_herarchy->name,
                        'hierarchyGap' => $user->popularity_herarchy->gapPopularity,
                        'hierarchyLogo' => $this->storageService->toHttpUrl($user->popularity_herarchy->iconSmall),
                        'hierarchyAlias' => $user->popularity_herarchy->alias,
                      ],
        'music'   => (object) [
                        'musicId' => $music->musicId,
                        'name'    => $music->name,
                      ],
        'cover'   => $work->cover
                      ? $this->storageService->toHttpUrl($work->cover) 
                      : null,
        'description' => $work->description,
        'listenCount' => $work->listenCount,
        'favouriteCount'  => $work->favouriteCount,
        'commentCount'    => $work->commentCount,
        'transmitCount'   => $work->transmitCount,
        'createdAt'       => $work->createdAt,
        'chorusType'      => $work->chorusType,
        'chorusCount'     => $work->chorusCount,
        'originWorkUser'  => null,
        'giftAmount'      => $work->giftAmount,
        'giftCoinAmount'  => $work->giftCoinAmount,
        'giftPopularity'  => $work->giftPopularity,
        'workPopularity'  => $work->workPopularity,
      ];
      if (isset($work->isPrivate)) {
        $workObject->isPrivate = $work->isPrivate;
      }
      if (isset($work->resource)) {
        $workObject->resource = $this->storageService->toHttpUrl($work->resource);
      }
      if ($originWorkUser) {
        $workObject->originWorkUser = (object) [
          'userId'    => $originWorkUser->userId,
          'avatar'    => $this->storageService->toHttpUrl($originWorkUser->avatar),
          'nickname'  => $originWorkUser->nickname,
        ];
      }

      return $workObject;
    })->filter(function ($work, $_) {
      return ! is_null($work); 
    });
  }

    /**
     * update popularity of work and user
     * @param $workId
     */
  private function updateWorkRelatedPopularity($workId){
        $job = (new UpdateWorkPopularityJob($workId))->onQueue('sing_plus_hierarchy_update');
        dispatch($job);
  }

    /**
     * @param string $userId
     * @param string $workId
     * @param bool|null $isPrivate
     * @param null|string $cover
     * @param null|string $desc
     */
  public function modifiedWorkInfo(
      string $userId,
      string $workId,
      ?bool $isPrivate,
      ?string $coverImageId,
      ?string $desc
  ){
      if ($coverImageId) {
          $image = $this->userImageService
              ->getImages([$coverImageId])
              ->first();
          if ( ! $image) {
              throw new UserImageNotExistsException();
          }
          $cover = $image->uri;
      }else {
          $cover = null;
      }
      $this->workService->updateWorkInfo($userId, $workId, $isPrivate, $cover, $desc);
      // 更新作品标签相关信息
      $this->updateWorkTagInfo($workId, $desc);
  }

    /**
     *  trigger an event to update work's tag info
     * @param $workId
     */
  private function updateWorkTagInfo($workId, $desc){
        if ($desc){
            event(new WorkUpdateTagsEvent($workId));
        }
  }

  // 更新用户对某作品是否喜欢的缓存
  public function updateWorkFavouriteCache(string $workId, string $userId, bool $isFavourite){
      $expireAfter = 60 * 24;
      $favouriteCacheKey = sprintf('workfavourite:user:%s:work:%s', $userId, $workId);
      Cache::put($favouriteCacheKey, $isFavourite, $expireAfter);
  }
}
