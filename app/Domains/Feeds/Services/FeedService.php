<?php

namespace SingPlus\Domains\Feeds\Services;

use Illuminate\Support\Collection;
use SingPlus\Contracts\Feeds\Services\properties;
use SingPlus\Support\Database\SeqCounter;
use SingPlus\Contracts\Feeds\Services\FeedService as FeedServiceContract;
use SingPlus\Contracts\Feeds\Constants\Feed as FeedConstant;
use SingPlus\Domains\Feeds\Repositories\FeedRepository;
use SingPlus\Domains\Feeds\Models\Feed;

class FeedService implements FeedServiceContract
{
  /**
   * @var FeedRepository
   */
  private $feedRepo;

  public function __construct(
    FeedRepository $feedRepo
  ) {
    $this->feedRepo = $feedRepo;
  }

  /**
   * {@inheritdoc}
   */
  public function getUserCommentFeeds(
    string $userId,
    ?string $feedId,
    bool $isNext,
    int $size
  ) : Collection {
    $displayOrder = null;
    if ($feedId) {
      $feed = $this->feedRepo->findOneById($feedId);
      $displayOrder = $feed ? (int) $feed->display_order : null;
    }

    $condition = [
      'userId'  => $userId,
      'type'    => [FeedConstant::TYPE_WORK_COMMENT],
    ];

    return $this->feedRepo
                ->findAllForPagination($condition, $displayOrder, $isNext, $size)
                ->map(function ($feed, $_) {
                  return (object) [
                    'feedId'            => $feed->id,
                    'userId'            => $feed->user_id,
                    'operatorUserId'    => $feed->operator_user_id,
                    'type'              => $feed->type,
                    'detail'            => (object) [
                                            'workId'  => array_get($feed->detail, 'work_id'),
                                            'commentId' => array_get($feed->detail, 'comment_id'),
                                          ],
                    'createdAt'         => $feed->created_at,
                    'isRead'            => (bool) $feed->is_read,
                  ];
                });
  }

  /**
   * {@inheritdoc}
   */
  public function getUserMixedFeeds(
    string $userId,
    ?string $feedId,
    bool $isNext,
    int $size
  ) : Collection {
    $displayOrder = null;
    if ($feedId) {
      $feed = $this->feedRepo->findOneById($feedId);
      $displayOrder = $feed ? (int) $feed->display_order : null;
    }

    $condition = [
      'userId'  => $userId,
      'type'    => [
                      FeedConstant::TYPE_WORK_COMMENT,
                      FeedConstant::TYPE_WORK_CHORUS_JOIN,
                    ],
    ];

    return $this->feedRepo
                ->findAllForPagination($condition, $displayOrder, $isNext, $size)
                ->map(function ($feed, $_) {
                  $res = (object) [
                    'feedId'            => $feed->id,
                    'userId'            => $feed->user_id,
                    'operatorUserId'    => $feed->operator_user_id,
                    'type'              => $feed->type,
                    'createdAt'         => $feed->created_at,
                    'isRead'            => (bool) $feed->is_read,
                  ];

                  switch ($feed->type) {
                    case FeedConstant::TYPE_WORK_COMMENT :
                    case FeedConstant::TYPE_WORK_COMMENT_DELETE :
                      $res->detail = (object) [
                        'workId'  => array_get($feed->detail, 'work_id'),
                        'commentId' => array_get($feed->detail, 'comment_id'),
                      ];
                      break;
                    case FeedConstant::TYPE_WORK_CHORUS_JOIN :
                      $res->detail = (object) [
                        'workId'        => array_get($feed->detail, 'work_id'),
                        'workName'      => array_get($feed->detail, 'work_name'),
                        'workChorusJoinId'  => array_get($feed->detail, 'work_chorus_join_id'),
                        'workChorusJoinName'  => array_get($feed->detail, 'work_chorus_join_name'),
                        'workChorusJoinDescription'  => array_get($feed->detail, 'work_chorus_join_description'),
                      ];
                      break;
                  }

                  return $res;
                });
  }

  /**
   * {@inheritdoc}
   */
  public function getUserNotificationFeeds(
    string $userId,
    ?string $feedId,
    bool $isNext,
    int $size,
    ?string $type
  ) : Collection {
    $displayOrder = null;
    if ($feedId) {
      $feed = $this->feedRepo->findOneById($feedId);
      $displayOrder = $feed ? (int) $feed->display_order : null;
    }

    $condition = [
      'userId'  => $userId,
      'type'    => $type ? [$type] :[FeedConstant::TYPE_WORK_FAVOURITE, FeedConstant::TYPE_WORK_TRANSMIT],
    ];
    return $this->feedRepo
                ->findAllForPagination($condition, $displayOrder, $isNext, $size)
                ->map(function ($feed, $_) {
                  return (object) [
                    'feedId'            => $feed->id,
                    'userId'            => $feed->user_id,
                    'operatorUserId'    => $feed->operator_user_id,
                    'type'              => $feed->type,
                    'detail'            => $feed->detail ? (object) [
                                            'channel' => array_get($feed->detail, 'channel'),
                                            'workId'  => array_get($feed->detail, 'work_id'),
                                            'musicId' => array_get($feed->detail, 'music_id'),
                                          ] : null,
                    'createdAt'         => $feed->created_at,
                    'isRead'            => (bool) $feed->is_read,
                  ];
                });
  }

  /**
   * {@inheritdoc}
   */
  public function createWorkTransmitFeed(
    string $userId, string $operatorUserId, string $workId, string $musicId, string $channel
  ) : string {
    $feed = $this->createFeed(
      $userId,
      $operatorUserId,
      FeedConstant::TYPE_WORK_TRANSMIT,
      [
        'work_id'   => $workId,
        'music_id'  => $musicId,
        'channel'   => $channel,
      ]
    );

    return $feed->id;
  }

  /**
   * {@inheritdoc}
   */
  public function createWorkFavouriteFeed(
    string $userId, string $operatorUserId, string $favouriteId, string $workId, bool $isAdd
  ) : string {
    $feed = $this->createFeed(
      $userId,
      $operatorUserId,
      $isAdd ? FeedConstant::TYPE_WORK_FAVOURITE : FeedConstant::TYPE_WORK_FAVOURITE_CANCEL,
      [
        'work_id'     => $workId,
        'favourit_id' => $favouriteId,
      ]
    );

    return $feed->id;
  }

  /**
   * {@inheritdoc}
   */
  public function createWorkChorusJoinFeed(
    string $userId,
    string $operatorUserId,
    string $workId,
    string $workName,
    string $workChorusJoinId,
    string $workChorusJoinName,
    string $workChorusJoinDescription
  ) : string {
    $feed = $this->createFeed(
      $userId,
      $operatorUserId,
      FeedConstant::TYPE_WORK_CHORUS_JOIN,
      [
        'work_id'                       => $workId,
        'work_name'                     => $workName,
        'work_chorus_join_id'           => $workChorusJoinId,
        'work_chorus_join_name'         => $workChorusJoinName,
        'work_chorus_join_description'  => $workChorusJoinDescription
      ]
    );
    return $feed->id;
  }

  /**
   * {@inheritdoc}
   */
  public function createWorkCommentFeed(
    string $userId,
    string $operatorUserId,
    string $commentId,
    string $workId,
    bool $isNew
  ) : string {
    $feed = $this->createFeed(
      $userId,
      $operatorUserId,
      $isNew ? FeedConstant::TYPE_WORK_COMMENT : FeedConstant::TYPE_WORK_COMMENT_DELETE,
      [
        'work_id'     => $workId,
        'comment_id'  => $commentId,
      ]
    );

    return $feed->id;
  }

  /**
   * {@inheritdoc}
   */
  public function createUserFollowedFeed(string $userId, string $operatorUserId) : string
  {
    $feed = $this->createFeed(
      $userId,
      $operatorUserId,
      FeedConstant::TYPE_USER_FOLLOWED
    );

    return $feed->id;
  }

  /**
   * {@inheritdoc}
   */
  public function getUserFeedCounts(string $userId) : \stdClass
  {
    $countTypes = [
      FeedConstant::TYPE_WORK_FAVOURITE   => 'workFavourite',
      FeedConstant::TYPE_WORK_TRANSMIT    => 'workTransmit',
      FeedConstant::TYPE_WORK_COMMENT     => 'workComment',
      FeedConstant::TYPE_USER_FOLLOWED    => 'followed',
      FeedConstant::TYPE_WORK_CHORUS_JOIN => 'workChorusJoin',
      FeedConstant::TYPE_GIFT_SEND_FOR_WORK => 'giftSendForWork',
    ];

    $counts = $this->feedRepo
                   ->countByUserAndType($userId, array_keys($countTypes));

    $feedCounts = [];
    foreach ($countTypes as $type => $key) {
      $data = $counts->where('type', $type)->first();
      $feedCounts[$key] = (int) object_get($data, 'count');
    }

    return (object) $feedCounts;
  }

  /**
   * {@inheritdoc}
   */
  public function setUserFeedsReaded(string $userId, array $types)
  {
    return $this->feedRepo->updateAllByUserIdAndTypes($userId, $types);
  }

  /**
   * {@inheritdoc}
   */
  public function getFeedForNotification(string $feedId) : ?\stdClass
  {
    $feed = $this->feedRepo->findOneById($feedId);

    $detail = (array) $feed->detail;
    switch ($feed->type) {
      case FeedConstant::TYPE_WORK_FAVOURITE :
        $detailInfo = (object) [
                    'workId'      => array_get($detail, 'work_id'),
                    'favouriteId' => array_get($detail, 'favourit_id'),
                  ];
        break;

      case FeedConstant::TYPE_WORK_TRANSMIT :
        $detailInfo = (object) [
                    'workId'      => array_get($detail, 'work_id'),
                    'musicId'     => array_get($detail, 'music_id'),
                    'channel'     => array_get($detail, 'channel'),
                  ];
        break;

      case FeedConstant::TYPE_WORK_COMMENT :
        $detailInfo = (object) [
                    'workId'      => array_get($detail, 'work_id'),
                    'commentId'   => array_get($detail, 'comment_id'),
                  ];
        break;

      case FeedConstant::TYPE_WORK_CHORUS_JOIN :
        $detailInfo = (object) [
                  'workId'                    => array_get($detail, 'work_id'),
                  'workName'                  => array_get($detail, 'work_name'),
                  'workChorusJoinId'          => array_get($detail, 'work_chorus_join_id'),
                  'workChorusJoinName'        => array_get($detail, 'work_chorus_join_name'),
                  'workChorusJoinDescription' => array_get($detail, 'work_chorus_join_description'),
                ];
        break;
      case FeedConstant::TYPE_GIFT_SEND_FOR_WORK:
        $detailInfo = (object)[
                'workId' => array_get($detail, 'work_id'),
                'giftHistoryId' => array_get($detail, 'giftHistory_id')
            ];
        break;

      default:
        $detailInfo = null;
    }

    return $feedId ? (object) [
      'feedId'          => $feed->id,
      'userId'          => $feed->user_id,
      'operatorUserId'  => $feed->operator_user_id,
      'type'            => $feed->type,
      'detail'          => $detailInfo,
    ] : null;
  }

  private function createFeed(
    string $userId, string $operatorUserId, string $type, array $detail = []
  ) : Feed {
    $feed = new Feed([
      'user_id'           => $userId,
      'operator_user_id'  => $operatorUserId,
      'type'              => $type,
      'detail'            => $detail,
      'status'            => Feed::STATUS_NORMAL,
      'is_read'           => Feed::READ_NO,
      'display_order'     => SeqCounter::getNext('feeds'),
    ]);

    $feed->save();

    return $feed;
  }

    /**
     * Create work comment feed
     *
     * @param string $userId feed owner user id
     * @param string $operatorUserId gift sender user id
     * @param string $giftHistoryId gift send history id
     * @param string $workId work id which the gift belongs to
     *
     * @return string                   feed id
     */
    public function createGiftSendForWorkFeed(
        string $userId,
        string $operatorUserId,
        string $giftHistoryId,
        string $workId
    ): string
    {
        $feed = $this->createFeed(
            $userId,
            $operatorUserId,
            FeedConstant::TYPE_GIFT_SEND_FOR_WORK ,
            [
                'work_id'     => $workId,
                'giftHistory_id'  => $giftHistoryId,
            ]
        );

        return $feed->id;
    }

    /**
     *
     * @param string $userId user id who's feed owner
     * @param ?string $feedId         used for pagination
     * @param bool $isNext used for pagination
     * @param int $size used for pagination
     *
     * @param Collection              properties as below
     *                                - feedId  string
     *                                - userId  string          feed owner id
     *                                - operatorUserId  string  user id who trigger this operation
     *                                - createdAt Carbon        feed creation time
     *                                - type      string        please see \SingPlus\Contracts\Feeds\Constants\Feed;
     *                                - detail    ?\stdClass    different by type
     *                                  - workId    string        work id
     *                                  - giftHistoryId string      giftHistory id
     *                                - isRead bool
     */
    public function getUserGiftFeeds(
        string $userId,
        ?string $feedId,
        bool $isNext,
        int $size
    ): Collection
    {
        $displayOrder = null;
        if ($feedId) {
            $feed = $this->feedRepo->findOneById($feedId);
            $displayOrder = $feed ? (int) $feed->display_order : null;
        }

        $condition = [
            'userId'  => $userId,
            'type'    => [FeedConstant::TYPE_GIFT_SEND_FOR_WORK],
        ];

        return $this->feedRepo
            ->findAllForPagination($condition, $displayOrder, $isNext, $size)
            ->map(function ($feed, $_) {
                return (object) [
                    'feedId'            => $feed->id,
                    'userId'            => $feed->user_id,
                    'operatorUserId'    => $feed->operator_user_id,
                    'type'              => $feed->type,
                    'detail'            => (object) [
                        'workId'  => array_get($feed->detail, 'work_id'),
                        'giftHistoryId' => array_get($feed->detail, 'giftHistory_id'),
                    ],
                    'createdAt'         => $feed->created_at,
                    'isRead'            => (bool) $feed->is_read,
                ];
            });
    }

    public function getGiftFeedsDetailByIds(array $ids): Collection
    {
        $feeds = $this->feedRepo->findAllByIds($ids);
        return $feeds->map(function ($feed, $__){
            return (object) [
                'feedId'            => $feed->id,
                'userId'            => $feed->user_id,
                'operatorUserId'    => $feed->operator_user_id,
                'type'              => $feed->type,
                'detail'            => (object) [
                    'workId'  => array_get($feed->detail, 'work_id'),
                    'giftHistoryId' => array_get($feed->detail, 'giftHistory_id'),
                ],
                'createdAt'         => $feed->created_at,
                'isRead'            => (bool) $feed->is_read,
            ];
        });

    }
}
