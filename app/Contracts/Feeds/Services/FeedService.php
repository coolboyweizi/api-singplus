<?php

namespace SingPlus\Contracts\Feeds\Services;

use Illuminate\Support\Collection;
use Carbon\Carbon;

interface FeedService
{
  /**
   * @param string $userId          user id who's feed owner
   * @param ?string $feedId         used for pagination
   * @param bool $isNext            used for pagination
   * @param int $size               used for pagination
   *
   * @param Collection              properties as below
   *                                - feedId  string
   *                                - userId  string          feed owner id
   *                                - operatorUserId  string  user id who trigger this operation     
   *                                - createdAt Carbon        feed creation time
   *                                - musicId   string        work music id
   *                                - type      string        please see \SingPlus\Contracts\Feeds\Constants\Feed;
   *                                - detail    ?\stdClass    different by type
   *                                  - workId    string        work id
   *                                  - musicId string      
   *                                  - channel string        Facebook|WhatsApp   only available if 
   *                                                          type is transmit
   *                                - createdAt \Carbon\Carbon
   *                                - isRead bool
   */
  public function getUserNotificationFeeds(
    string $userId,
    ?string $feedId,
    bool $isNext,
    int $size,
    ?string $type
  ) : Collection;

  /**
   *
   * @param string $userId          user id who's feed owner
   * @param ?string $feedId         used for pagination
   * @param bool $isNext            used for pagination
   * @param int $size               used for pagination
   *
   * @param Collection              properties as below
   *                                - feedId  string
   *                                - userId  string          feed owner id
   *                                - operatorUserId  string  user id who trigger this operation     
   *                                - createdAt Carbon        feed creation time
   *                                - type      string        please see \SingPlus\Contracts\Feeds\Constants\Feed;
   *                                - detail    ?\stdClass    different by type
   *                                  - workId    string        work id
   *                                  - commentId string      comment id
   *                                - isRead bool
   */
  public function getUserCommentFeeds(
    string $userId,
    ?string $feedId,
    bool $isNext,
    int $size
  ) : Collection;

  /**
   * Get user mixed feeds, including: comments, chorus join
   *
   * @param string $userId          user id who's feed owner
   * @param ?string $feedId         used for pagination
   * @param bool $isNext            used for pagination
   * @param int $size               used for pagination
   *
   * @return Collection             properties as below
   *                                - feedId  string
   *                                - userId  string          feed owner id
   *                                - operatorUserId  string  user id who trigger this operation     
   *                                - createdAt Carbon        feed creation time
   *                                - type      string        please see \SingPlus\Contracts\Feeds\Constants\Feed;
   *                                - detail    ?\stdClass    different by type
   *                                  - workId string         work id
   *                                  // fields for comment
   *                                  - commentId string      comment id
   *                                  // fields for chorus join
   *                                  - workName string
   *                                  - workChorusJoinId string
   *                                  - workChorusJoinName string
   *                                  - workChorusJoinDescription
   *                                - isRead bool
   *                        
   */
  public function getUserMixedFeeds(
    string $userId,
    ?string $feedId,
    bool $isNext,
    int $size
  ) : Collection;

  /**
   * Create work transmit feed
   *
   * @param string $userId            feed owner user id
   * @param string $operatorUserId    feed transmit user id 
   * @param string $workId
   * @param string $musicId
   * @param string $channel
   */
  public function createWorkTransmitFeed(
    string $userId, string $operatorUserId, string $workId, string $musicId, string $channel
  ) : string;

  /**
   * Create work favourite feed
   *
   * @param string $userId
   * @param string $operatorUserId    feed transmit user id 
   * @param string $favouriteId       user favourite id
   * @param string $workId
   * @param bool $isAdd               user put favourite if true, or cancel
   *
   * @return string                   feed id
   */
  public function createWorkFavouriteFeed(
    string $userId, string $operatorUserId, string $favouriteId, string $workId, bool $isAdd
  ) : string;

  /**
   * Create work chorus join feed
   *
   * @param string $userId
   * @param string $operatorUserId        feed transmit user id 
   * @param string $workId                chrous start work id
   * @param string $workName
   * @param string $workChorusJoinId      chorus join work id
   * @param string $workChorusJoinName    chorus join work name
   * @param string $workChorusJoinDescription chorus join work description
   *
   * @return string                   feed id
   */
  public function createWorkChorusJoinFeed(
    string $userId,
    string $operatorUserId,
    string $workId,
    string $workName,
    string $workChorusJoinId,
    string $workChorusJoinName,
    string $workChorusJoinDescription
  ) : string;

  /**
   * Create work comment feed
   *
   * @param string $userId            feed owner user id
   * @param string $operatorUserId    comment owner user id
   * @param string $commentId         comment id
   * @param string $workId            work id which the comment belongs to
   * @param bool $isNew               true stands for new comment, false stands for deleting comment
   *
   * @return string                   feed id
   */
  public function createWorkCommentFeed(
    string $userId,
    string $operatorUserId,
    string $commentId,
    string $workId,
    bool $isNew
  ) : string;

  /**
   * Create user followed feed
   *
   * @param string $userId            use who are followed
   * @param string $operatorUserId    use who trigger follow action
   *
   * @return string                   feed id
   */
  public function createUserFollowedFeed(
    string $userId,
    string $operatorUserId
  ) : string;

  /**
   * Get feed counts by type
   *
   * @param string $userId
   *
   * @return \stdClass          properties as below
   *                            - workFavourite int
   *                            - workTransmit int
   *                            - workComment int
   *                            - followed int
   */
  public function getUserFeedCounts(string $userId) : \stdClass;

  /**
   * Set user Feeds readed by types
   *
   * @param string $userId
   * @param array $types
   */
  public function setUserFeedsReaded(string $userId, array $types);

  /**
   * Get feed
   *
   * @param string $feedId
   *
   * @return ?\stdClass       properties as below:
   *                          - operatorUserId string
   *                          - userId string
   *                          - type string
   *                          - detail \stdClass
   *                            if type is transmit
   *                              - workId string
   *                              - musicId string
   *                              - channel string
   *                            if type is comment
   *                              - workId string
   *                              - commentId string
   *                            if type is favourite
   *                              - workId string
   *                              - favouriteId string
   *                            if type is work chorus join
   *                              - workId string
   *                              - workName string
   *                              - work_chorus_join_id string
   *                              - work_chorus_join_name string
   *                              - work_chorus_join_description string
   */
  public function getFeedForNotification(string $feedId) : ?\stdClass;


    /**
     * Create work comment feed
     *
     * @param string $userId            feed owner user id
     * @param string $operatorUserId    gift sender user id
     * @param string $giftHistoryId         gift send history id
     * @param string $workId            work id which the gift belongs to
     *
     * @return string                   feed id
     */
    public function createGiftSendForWorkFeed(
        string $userId,
        string $operatorUserId,
        string $giftHistoryId,
        string $workId
    ) : string;



    /**
     *
     * @param string $userId          user id who's feed owner
     * @param ?string $feedId         used for pagination
     * @param bool $isNext            used for pagination
     * @param int $size               used for pagination
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
    ) : Collection;

    /**
     * @param array $ids
     * @return Collection       properties as below
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
    public function getGiftFeedsDetailByIds(array $ids) : Collection;
}
