<?php

namespace SingPlus\Services;

use Log;
use Illuminate\Support\Collection;
use SingPlus\Contracts\DailyTask\Constants\DailyTask as DailyTaskConstant;
use SingPlus\Contracts\Users\Services\UserProfileService as UserProfileServiceContract;
use SingPlus\Contracts\Storages\Services\StorageService as StorageServiceContract;
use SingPlus\Contracts\Feeds\Services\FeedService as FeedServiceContract;
use SingPlus\Contracts\Feeds\Constants\Feed as FeedConstant;
use SingPlus\Contracts\Friends\Services\FriendService as FriendServiceContract;
use SingPlus\Contracts\Musics\Services\MusicService as MusicServiceContract;
use SingPlus\Contracts\Works\Services\WorkService as WorkServiceContract;
use SingPlus\Contracts\DailyTask\Services\DailyTaskService as DailyTaskServiceContract;
use SingPlus\Contracts\Gifts\Services\GiftService as GiftServiceContract;
use SingPlus\Domains\DailyTask\Models\DailyTask;
use SingPlus\Exceptions\Works\WorkNotExistsException;
use SingPlus\Exceptions\Feeds\FeedTransmitChannelInvalidException;
use SingPlus\Events\Feeds\FeedReaded as FeedReadedEvent;
use SingPlus\Events\UserCommentWork as UserCommentWorkEvent;
use SingPlus\Events\UserTriggerFavouriteWork as UserTriggerFavouriteWorkEvent;
use SingPlus\Events\FeedCreated as FeedCreatedEvent;

class FeedService
{
  /**
   * @var FeedServiceContract
   */
  private $feedService;

  /**
   * @var StorageServiceContract
   */
  private $storageService;

  /**
   * @var UserProfileServiceContract
   */
  private $userProfileService;

  /**
   * @var MusicServiceContract
   */
  private $musicService;

  /**
   * @var WorkServiceContract
   */
  private $workService;

  /**
   * @var FriendServiceContract
   */
  private $friendService;

    /**
     * @var DailyTaskServiceContract
     */
  private $dailyTaskService;

    /**
     * @var GiftServiceContract
     */
  private $giftService;

  public function __construct(
    FeedServiceContract $feedService,
    StorageServiceContract $storageService,
    UserProfileServiceContract $userProfileService,
    MusicServiceContract $musicService,
    WorkServiceContract $workService,
    FriendServiceContract $friendService,
    DailyTaskServiceContract $dailyTaskService,
    GiftServiceContract $giftService
  ) {
    $this->feedService = $feedService;
    $this->storageService = $storageService;
    $this->userProfileService = $userProfileService;
    $this->musicService = $musicService;
    $this->workService = $workService;
    $this->friendService = $friendService;
    $this->dailyTaskService = $dailyTaskService;
    $this->giftService = $giftService;
  }

  /**
   * Get user comments feeds
   *
   * @param string $userId
   * @param ?string $feedId     for pagination
   * @param bool $isNext        for pagination
   * @param int $size           for pagination
   *
   * @return Collection                 elements are \stdClass, properties as below
   *                                    - feedId string
   *                                    - feedType string   work_comment | work_comment_delete
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
   *                                    - isNormal bool
   *                                    - createdAt \Carbon\Carbon
   *                                    - giftInfo  \stdClass
   *                                        - giftName string the gift name
   *                                        - giftAmount    int the amount of gift
   */
  public function getUserCommentFeeds(
    string $userId,
    ?string $feedId,
    bool $isNext,
    int $size = 20
  ) {
    $feeds = $this->feedService->getUserCommentFeeds($userId, $feedId, $isNext, $size);
    if ($feeds->isEmpty()) {
      return collect();
    }
    $feedIds = $feeds->map(function ($feed, $_) {
      return $feed->detail->commentId;
    })->toArray();

    $comments = $this->workService->getComments($feedIds, true);
    $authorIds = [];
    $musicIds = [];
    $giftFeedIds = [];
    $repliedUserIds = $comments->each(function ($comment, $_) use (&$authorIds, &$musicIds, &$giftFeedIds) {
                        $authorIds[] = $comment->authorId;
                        $musicIds[] = $comment->musicId;
                        $giftFeedIds[] = $comment->giftFeedId;
                      });

    $giftFeeds = $this->feedService->getGiftFeedsDetailByIds($giftFeedIds);
    $giftHistoryIds = [];
    $giftFeedHisMap = $giftFeeds->map(function($giftFeed, $__) use(&$giftHistoryIds){

            if ($giftFeed->detail == null || $giftFeed->detail->giftHistoryId == null){
                return null;
            }
            $giftHistoryIds[] = $giftFeed->detail->giftHistoryId;
            return (object)[
                'feedId' => $giftFeed->feedId,
                'giftHistoryId' => $giftFeed->detail->giftHistoryId
            ];
    })->filter(function ($data, $__){
        return !is_null($data);
    });
    $giftHistorys = $this->giftService->getGiftSendHistoryByIds($giftHistoryIds);


    $authors = $this->userProfileService->getUserProfiles($authorIds);
    $musics = $this->musicService->getMusics($musicIds);

    if ( ! $feedId) {
      event(new FeedReadedEvent($userId, [
        FeedConstant::TYPE_WORK_COMMENT,
        FeedConstant::TYPE_WORK_COMMENT_DELETE,
      ]));
    }

    return $feeds->map(function ($feed, $_) use ($comments, $authors, $musics, $giftFeedHisMap, $giftHistorys) {
      $comment = $comments->where('commentId', $feed->detail->commentId)->first();
      if ( ! $comment) {
        return null;
      }
      $author = $authors->where('userId', $comment->authorId)->first();
      $music = $musics->where('musicId', $comment->musicId)->first();

      if ( ! $music) {
        Log::alert('Data missed. userRelatedComment miss music', [
          'comment_id'  => $comment->commentId,
          'music_id'    => $comment->musicId,
        ]);

        return null;
      }

      $giftFeedId = $comment->giftFeedId;
      $map = $giftFeedHisMap->where('feedId', $giftFeedId)->first();
      $giftHistoryId = $map ? $map->giftHistoryId : null;
      $giftHistory  = $giftHistorys->where('historyId', $giftHistoryId)->first();

      return (object) [
        'feedId'            => $feed->feedId,
        'feedType'          => $feed->type,
        'commentId'         => $comment->commentId,
        'repliedCommentId'  => $comment->repliedCommentId,
        'author'            => (object) [
                                  'userId'    => $author->userId,
                                  'nickname'  => $author->nickname,
                                  'avatar'    => $this->storageService->toHttpUrl($author->avatar),
                                ],
        'music'             => (object) [
                                  'musicId' => $music->musicId,
                                  'name'    => $music->name,
                                ],
        'work'              => (object) [
                                  'workId'    => $comment->work->workId,
                                  'workName'  => $comment->work->workName,
                                ],
        'repliedComment'    => $comment->repliedComment ? (object) [
                                  'commentId' => $comment->repliedComment->commentId,
                                  'content'   => $comment->repliedComment->content,
                                ] : null,
        'content'           => $comment->content,
        'isNormal'          => $comment->isNormal,
        'isRead'            => $feed->isRead,
        'createdAt'         => $feed->createdAt,
        'commentType'       => $comment->commentType,
        'giftInfo'          => $giftHistory ? (object)[
                            'giftName' => $giftHistory->giftName,
                            'giftAmount' => $giftHistory->giftAmount,
                            ] : null,
      ];
    })->filter(function ($feed, $_) {
      return ! is_null($feed); 
    });
  }

  /**
   * Get user comments feeds
   *
   * @param string $userId
   * @param ?string $feedId     for pagination
   * @param bool $isNext        for pagination
   * @param int $size           for pagination
   *
   * @return Collection                 elements are \stdClass, properties as below
   *                                    - feedId string
   *                                    - feedType string   work_comment | work_comment_delete
   *                                    - isNormal bool
   *                                    - createdAt \Carbon\Carbon
   *                                    - author \stdClass
   *                                      - userId string
   *                                      - nickname string
   *                                      - avatar string
   *                                    
   *                                    // only exists if type is comment
   *                                    - commentId string
   *                                    - repliedCommentId ?string    replied comment id
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
   *
   *                                    // only exists if type is chorus join
   *                                    - workName string
   *                                    - workChorusJoinInfo \stdClass
   *                                      - workId string
   *                                      - workName string
   *                                      - workDescription string
   */
  public function getUserMixedFeeds(
    string $userId,
    ?string $feedId,
    bool $isNext,
    int $size = 20
  ) {
    $feeds = $this->feedService->getUserMixedFeeds($userId, $feedId, $isNext, $size);
    if ($feeds->isEmpty()) {
      return collect();
    }
    
    $commentTypes = [
      FeedConstant::TYPE_WORK_COMMENT,
      FeedConstant::TYPE_WORK_COMMENT_DELETE,
    ];
    $commentIds = [];
    $authorIds = [];
    $feedIds = $feeds->each(function ($feed, $_) use (&$commentIds, &$authorIds, $commentTypes) {
      if (in_array($feed->type, $commentTypes)) {
        $commentIds[] = $feed->detail->commentId;
      }
      $authorIds[] = $feed->operatorUserId;
    });

    $comments = $this->workService->getComments($commentIds, true)->filter(function ($comment, $_) {

        return $comment->commentType == 0;
    });
    $musicIds = [];
    $repliedUserIds = $comments->each(function ($comment, $_) use (&$authorIds, &$musicIds) {
                        $musicIds[] = $comment->musicId;
                      });
    $authors = $this->userProfileService->getUserProfiles($authorIds);
    $musics = $this->musicService->getMusics($musicIds);

    if ( ! $feedId) {
      event(new FeedReadedEvent($userId, [
        FeedConstant::TYPE_WORK_COMMENT,
        FeedConstant::TYPE_WORK_COMMENT_DELETE,
        FeedConstant::TYPE_WORK_CHORUS_JOIN,
      ]));
    }

    return $feeds->map(function ($feed, $_) use ($comments, $authors, $musics, $commentTypes) {
      $author = $authors->where('userId', $feed->operatorUserId)->first();

      if (in_array($feed->type, $commentTypes)) {
        $comment = $comments->where('commentId', $feed->detail->commentId)->first();
        if ( ! $comment) {
          return null;
        }
        $music = $musics->where('musicId', $comment->musicId)->first();

        if ( ! $music) {
          Log::alert('Data missed. userRelatedComment miss music', [
            'comment_id'  => $comment->commentId,
            'music_id'    => $comment->musicId,
          ]);

          return null;
        }

        return (object) [
          'feedId'            => $feed->feedId,
          'feedType'          => $feed->type,
          'commentId'         => $comment->commentId,
          'repliedCommentId'  => $comment->repliedCommentId,
          'author'            => (object) [
                                    'userId'    => $author->userId,
                                    'nickname'  => $author->nickname,
                                    'avatar'    => $this->storageService->toHttpUrl($author->avatar),
                                  ],
          'music'             => (object) [
                                    'musicId' => $music->musicId,
                                    'name'    => $music->name,
                                  ],
          'work'              => (object) [
                                    'workId'    => $comment->work->workId,
                                    'workName'  => $comment->work->workName,
                                  ],
          'repliedComment'    => $comment->repliedComment ? (object) [
                                    'commentId' => $comment->repliedComment->commentId,
                                    'content'   => $comment->repliedComment->content,
                                  ] : null,
          'content'           => $comment->content,
          'isNormal'          => $comment->isNormal,
          'isRead'            => $feed->isRead,
          'createdAt'         => $feed->createdAt,
        ];
      } else if ($feed->type == FeedConstant::TYPE_WORK_CHORUS_JOIN) {
        return (object) [
          'feedId'            => $feed->feedId,
          'feedType'          => $feed->type,
          'author'            => (object) [
                                    'userId'    => $author->userId,
                                    'nickname'  => $author->nickname,
                                    'avatar'    => $this->storageService->toHttpUrl($author->avatar),
                                  ],
          'isRead'            => $feed->isRead,
          'createdAt'         => $feed->createdAt,
          'work'              => (object) [
            'workId'    => $feed->detail->workId,
            'workName'  => $feed->detail->workName,
          ],
          'workChorusJoinInfo'  => (object) [
            'workId'          => $feed->detail->workChorusJoinId,
            'workName'        => $feed->detail->workChorusJoinName,
            'workDescription' => $feed->detail->workChorusJoinDescription,
          ],
        ];
      } else {
        return null;
      }
    })->filter(function ($feed, $_) {
      return ! is_null($feed); 
    });
  }

  /**
   * Get user notification feeds
   *
   * @param string $userId
   * @param ?string $feedId     for pagination
   * @param bool $isNext        for pagination
   * @param int $size           for pagination
   *
   * @return Collection         elements are \stdClass, properties as below:
   *                            - feedId string
   *                            - userId string             feed owner
   *                            - type string               feed type, please to see
   *                                                        \SingPlus\Contracts\Feeds\Constants\Feed
   *                            - operator  \stdClass
   *                              - name string             operator name
   *                              - avatar string           operator avatar url
   *                            - detail    \stdClass
   *                              - workId string             work id
   *                              - music ?\stdClass
   *                                - musicId string
   *                                - musicName string        work music name
   *                              - channel string          exists only if type is transmit
   *                            - publishAt \Carbon\Carbon  feed publish time. format: Y-m-d H:i:s 
   */
  public function getUserNotificationFeeds(
    string $userId,
    ?string $feedId,
    bool $isNext,
    int $size = 20,
    ?string $type
  ) : Collection {
    $feeds = $this->feedService->getUserNotificationFeeds($userId, $feedId, $isNext, $size, $type);
    if ($feeds->isEmpty()) {
      return collect();
    }

    $operatorUserIds = [];
    $workIds = [];
    $feeds->each(function ($feed, $_) use (&$operatorUserIds, &$workIds) {
      $operatorUserIds[] = $feed->operatorUserId;
      if ($feed->detail){
          $workIds[] = object_get($feed->detail, 'workId');
      }
    });
    $works = $this->workService->getWorksByIds($workIds, true);
    $musicIds = $works->map(function ($work, $_) {
                          return $work->musicId;
                        })->toArray();

    $musics = $this->musicService->getMusics($musicIds);
    $userProfiles = $this->userProfileService->getUserProfiles($operatorUserIds);

    // 获取用户关系
    $userRelationships= $this->friendService->getUserRelationship($userId, $operatorUserIds);

    if ( ! $feedId) {
      if ($type){
        if ($type == FeedConstant::TYPE_WORK_FAVOURITE){
            event(new FeedReadedEvent($userId, [
                FeedConstant::TYPE_WORK_FAVOURITE,
                FeedConstant::TYPE_WORK_FAVOURITE_CANCEL,
            ]));
        }else if ($type == FeedConstant::TYPE_USER_FOLLOWED){
            event(new FeedReadedEvent($userId, [
                FeedConstant::TYPE_USER_FOLLOWED,
            ]));
        }
      }else {
          event(new FeedReadedEvent($userId, [
              FeedConstant::TYPE_WORK_FAVOURITE,
              FeedConstant::TYPE_WORK_FAVOURITE_CANCEL,
              FeedConstant::TYPE_WORK_TRANSMIT,
          ]));
      }

    }

    return $feeds->map(function ($feed, $_) use ($works, $musics, $userProfiles, $userRelationships) {
              $operator = $userProfiles->where('userId', $feed->operatorUserId)->first();
              $relations = $userRelationships->where('userId', $feed->operatorUserId)->first();
              $work = $feed->detail ?$works->where('workId', object_get($feed->detail, 'workId'))->first() : null;
              $music = $work ? $musics->where('musicId', $work->musicId)->first() : null;
              $musicInfo = null;
              if ($work) {
                $musicId = $work->musicId;
                if ($work->workName) {
                  $musicName = $work->workName;
                } elseif ($music) {
                  $musicName = $music->name;
                } else {
                  $musicName = null;
                }

                $musicInfo = (object) [
                  'musicId'   => $musicId,
                  'musicName' => $musicName,
                ];
              }

              
              return (object) [
                'feedId'        => $feed->feedId,
                'userId'        => $feed->userId,
                'type'          => $feed->type,
                'operator'      => (object) [
                                    'userId'  => $operator->userId,
                                    'name'    => $operator->nickname,
                                    'avatar'  => $this->storageService->toHttpUrl($operator->avatar),
                                  ],
                'detail'        => $feed->detail ?(object) [
                                    'workId'    => object_get($feed->detail, 'workId'),
                                    'channel'   => object_get($feed->detail, 'channel'),
                                    'music'     => $musicInfo,
                                  ] : null,
                'isRead'        => $feed->isRead,
                'publishAt'     => $feed->createdAt,
                'isFollowing'   => $relations->isFollowing,
              ];
            });
  }

  /**
   * Create work transmit feed
   */
  public function createWorkTransmitFeed(
    string $userId,
    string $workId,
    string $channel,
    string $countryAbbr
  ) {
    if ( ! in_array($channel, FeedConstant::$validChannels)) {
      throw new FeedTransmitChannelInvalidException();
    }

    $work = $this->workService->getDetail($workId, true);
    if ( ! $work) {
      throw new WorkNotExistsException();
    }

    $feedId = null;
    // 只有在登录后才创建通知，自己转发自己不收到创建通知
    if ($userId != ""  && $userId != $work->userId)
    {
        $feedId = $this->feedService->createWorkTransmitFeed(
            $work->userId, $userId, $workId, $work->musicId, $channel
        );
        event(new FeedCreatedEvent($feedId));
    }
    //完成每日任务，分享到站外，自己或者别人的作品都可以
    if ($userId != "" &&
        ($channel == FeedConstant::CHANNEL_FACEBOOK || $channel == FeedConstant::CHANNEL_WHATSAPP)){
        $this->dailyTaskService->resetDailyTaskLists($userId, $countryAbbr);
        $this->dailyTaskService->finisheDailyTask($userId, DailyTaskConstant::TYPE_SHARE);
    }

    $this->workService->incrWorkTransmitCount($workId);

    return $feedId;
  }

  /**
   * Create work favourite feed
   */
  public function createWorkFavouriteFeed(string $favouriteId, string $action) : ?string
  {
    $actionTypeMaps = [
      UserTriggerFavouriteWorkEvent::ACTION_ADD    => FeedConstant::TYPE_WORK_FAVOURITE,
      UserTriggerFavouriteWorkEvent::ACTION_CANCEL => FeedConstant::TYPE_WORK_FAVOURITE_CANCEL,
    ];
    if ( ! ($type = array_get($actionTypeMaps, $action))) {
      return null;
    }

    $favourite = $this->workService->getFavourite($favouriteId, true);
    if ( ! $favourite) {
      return null;
    }
    $work = $this->workService->getDetail($favourite->workId, true);
    if ( ! $work) {
      return null;
    }

    $feedId = $this->feedService->createWorkFavouriteFeed(
      $work->userId, $favourite->userId, $favouriteId, $favourite->workId,
      $type == FeedConstant::TYPE_WORK_FAVOURITE ? true : false
    );

    event(new FeedCreatedEvent($feedId));

    return $feedId;
  }

  /**
   * Create work comment feed
   */
  public function createWorkCommentFeed(string $commentId, string $action) : ?string
  {
    $actionTypeMaps = [
      UserCommentWorkEvent::ACTION_NEW    => FeedConstant::TYPE_WORK_COMMENT,
      UserCommentWorkEvent::ACTION_DELETE => FeedConstant::TYPE_WORK_COMMENT_DELETE,
    ];
    if ( ! ($type = array_get($actionTypeMaps, $action))) {
      return null;
    }

    $comment = $this->workService->getComment($commentId, true);
    if ( ! $comment) {
      return null;
    }

    $feedId = $this->feedService->createWorkCommentFeed(
      $comment->repliedUserId, $comment->authorId, $commentId, $comment->workId,
      $type == FeedConstant::TYPE_WORK_COMMENT ? true : false
    );

    event(new FeedCreatedEvent($feedId));

    return $feedId;
  }

  /**
   * Create user followed feed
   */
  public function createUserFollowedFeed(string $userId, string $followedUserId) : ?string
  {
    if ($userId == $followedUserId) {
      return null;
    }

    return $this->feedService->createUserFollowedFeed($followedUserId, $userId);
  }

  /**
   * User set his/her feeds readed by types
   *
   * @param string $userId
   * @param array $feedTypes
   *
   * @return int    affected rows
   */
  public function setUserFeedsReaded(string $userId, array $feedTypes) : int
  {
    return $this->feedService->setUserFeedsReaded($userId, $feedTypes);
  }

    /**
     * @param string $giftHistoryId
     * @return string feedId
     */
  public function createGiftSendForWorkFeed(string $giftHistoryId) :string{
      $giftHistory = $this->giftService->getGiftSendHistory($giftHistoryId);
      if ( ! $giftHistory) {
          return null;
      }

      $feedId = $this->feedService->createGiftSendForWorkFeed($giftHistory->receiverId,
          $giftHistory->senderId,$giftHistoryId, $giftHistory->workId);

      event(new FeedCreatedEvent($feedId));

      return $feedId;
  }


    /**
     * @param string $userId
     * @param null|string $feedId
     * @param bool $isNext
     * @param int $size
     * @return Collection elements are \stdClass, properties as below
     *                                    - feedId string
     *                                    - feedType string   work_comment | work_comment_delete
     *                                    - author \stdClass
     *                                      - userId string
     *                                      - nickname string
     *                                      - avatar string
     *                                    - work  \stdClass
     *                                      - workId string
     *                                      - workName string
     *                                    - music \stdClass
     *                                         - musicId string
     *                                         - musicName  string
     *                                    - gift \stdClass
     *                                      -  giftHistoryId string history id
     *                                      -  giftId string gift'id
     *                                      -  giftName string gift's name
     *                                      -  giftAmount int the amount of gift
     *                                      -  icon \stdClass
     *                                          - small string icon small
     *                                          - big   string icon big
     *                                    - isRead   int
     *                                    - createdAt \Carbon\Carbon
     */
  public function getUserGiftFeeds(
      string $userId,
      ?string $feedId,
      bool $isNext,
      int $size = 20
  ){
      $feeds = $this->feedService->getUserGiftFeeds($userId, $feedId, $isNext, $size);
      if ($feeds->isEmpty()) {
          return collect();
      }

      $senderIds = [];
      $workIds = [];
      $giftHistoryIds = [];
      $musicIds =[];

      $feeds->each(function ($feed, $_) use (&$senderIds, &$workIds, &$giftHistoryIds) {
          if ($feed->operatorUserId && ! in_array($feed->operatorUserId, $senderIds)) {
              $senderIds[] = $feed->operatorUserId;
          }

          if ($feed->detail->workId && !in_array($feed->detail->workId , $workIds)){
              $workIds[] = $feed->detail->workId;
          }

          if ($feed->detail->giftHistoryId && !in_array($feed->detail->giftHistoryId, $giftHistoryIds)){
              $giftHistoryIds[] = $feed->detail->giftHistoryId;
          }
      });

      $authors = $this->userProfileService->getUserProfiles($senderIds);

      $works = $this->workService->getWorksByIds($workIds, true);

      $works->each(function($work, $__) use (&$musicIds){
           if ($work->musicId && ! in_array($work->musicId, $musicIds)){
               $musicIds[] = $work->musicId;
           }
      });

      $musics = $this->musicService->getMusics($musicIds, true);

      $giftHistorys = $this->giftService->getGiftSendHistoryByIds($giftHistoryIds);

      if ( ! $feedId) {
          event(new FeedReadedEvent($userId, [
              FeedConstant::TYPE_GIFT_SEND_FOR_WORK,
          ]));
      }

      return $feeds->map(function ($feed, $_) use ($authors, $works, $giftHistorys, $musics) {
          $author = $authors->where('userId', $feed->operatorUserId)->first();
          $work = $works->where('workId', $feed->detail->workId)->first();
          $giftHistory = $giftHistorys->where('historyId', $feed->detail->giftHistoryId)->first();


          if (!$author){
              return null;
          }

          if (!$work){
              return null;
          }

          if (!$giftHistory){
              return null;
          }

          $music = $musics->where('musicId', $work->musicId)->first();

          return (object)[

                'feedId' => $feed->feedId,
                'feedType' => $feed->type,
                'author'   => (object)[
                    'userId' => $author->userId,
                    'nickname' => $author->nickname,
                    'avatar'    => $this->storageService->toHttpUrl($author->avatar),
                ],
                'work'    => (object)[
                    'workId' => $work->workId,
                    'workName' => $work->workName,
                    'chorusType' => $work->chorusType ? $work->chorusType : 0,
                ],
                'music'   => $music ?(object)[
                    'musicId' => $music->musicId,
                    'musicName' => $music->name
                ] : null,
                'gift'    => (object)[
                    'giftHistoryId' => $giftHistory->historyId,
                    'giftId' => $giftHistory->giftId,
                    'giftName' => $giftHistory->giftName,
                    'giftAmount' => $giftHistory->giftAmount,
                    'icon' => (object)[
                        'small' => $this->storageService->toHttpUrl($giftHistory->icon->small),
                        'big'   => $this->storageService->toHttpUrl($giftHistory->icon->big)
                    ]
                ],
               'isRead'            => $feed->isRead,
               'createdAt'         => $feed->createdAt,
          ];
      })->filter(function ($feed, $_) {
          return ! is_null($feed);
      });

  }

}
