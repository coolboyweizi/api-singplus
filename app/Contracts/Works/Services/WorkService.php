<?php

namespace SingPlus\Contracts\Works\Services;

use Carbon\Carbon;
use Illuminate\Support\Collection;

interface WorkService
{

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
   * 2-steps upload -> first step
   *
   * @param string $userId    user id
   * @param string $musicId   music id which work belongs to
   * @param ?string $workName
   * @param string $cover     work cover uri
   * @param bool $isDefaultCover
   * @param array $slides     work slide uris
   * @param int $duration     how much seconds the work will play
   * @param string $description   work description, for sharing
   * @param bool $noAccompaniment
   * @param ?string $resource     work resource, only exists if client upload work
   *                              to s3 server directly
   * @param bool $isPrivate
   * @param ?int $chorusType
   * @param ?string $originWorkId
   *
   * @return \stdClass        new added work, properties as below
   *                          - taskId string   work upload task id
   */
  public function createTwoStepUploadTask(
    string $userId,
    string $musicId,
    ?string $workName,
    string $cover,
    bool $isDefaultCover,
    array $slides,
    int $duration,
    string $description,
    bool $noAccompaniment,
    ?string $resource = null,
    bool $isPrivate = false,
    ?int $chorusType,
    ?string $originWorkId
  ) : \stdClass;

  /**
   * Get user work upload task
   *
   * @param string $userId
   * @param string $taskId
   *
   * @return ?\stdClass       properties as below:
   *                          - musicId string
   *                          - workName ?string
   *                          - duration integer      work playing time length (seconds)
   *                          - cover string          cover image url
   *                          - slides array          elements are slide image url
   *                          - description string    work description, for share
   *                          - noAccompaniment bool 
   *                          - isDefaultCover bool
   *                          - isPrivate bool
   *                          - chorusType ?int
   *                          - originWorkId ?string
   */
  public function getUserUploadTask(string $userId, string $taskId) : ?\stdClass;

  /**
   * Delete work upload task by id
   *
   * @param string $taskId
   */
  public function deleteTaskAfterUploaded(string $taskId);

  /**
   * Remove all tasks which created_at less than expiredTime
   *
   * @param Carbon $expiredTime
   */
  public function clearExpiredUploadTask(Carbon $expiredTime);

  /**
   * Add new user work
   *
   * @param string $userId    user id
   * @param string $musicId   music id which work belongs to
   * @param ?string $workName
   * @param string $uri       work resource uri
   * @param string $cover     work cover uri
   * @param bool $isDefaultCover
   * @param array $slides     work slide uris
   * @param int $duration     how much seconds the work will play
   * @param string $description   work description, for sharing
   * @param bool $noAccompaniment
   * @param bool $isPrivate
   * @param ?int $chorusType  see \SingPlus\Contracts\Works\Constants\WorkConstant
   * @param ?string $originWorkId
   * @param ?string $countryAbbr
   *
   * @return \stdClass        new added work, properties as below
   *                          - workId string   work id
   */
  public function addUserWork(
    string $userId,
    string $musicId,
    ?string $workName,
    string $uri,
    string $cover,
    bool $isDefaultCover,
    array $slides,
    int $duration,
    string $description,
    bool $noAccompaniment,
    bool $isPrivate = false,
    ?int $chorusType = null,
    ?string $originWorkId = null,
    ?string $countryAbbr = null
  ) : \stdClass;

  /**
   * Get select works
   *
   * @param ?string $selectionId  used for pagination
   * @param bool $isNext          used for pagination
   * @param int $size             used for pagination
   *
   * @return Collection       elements are \stdClass, properties as below:
   *                          - selectionId string    work selection id
   *                          - workId string         work id
   *                          - workName ?string
   *                          - musicId string        music id which work belongs to
   *                          - userId string         work user
   *                          - cover string          work cover image uri
   *                          - isDefaultCover bool
   *                          - slides array          work slides image ids
   *                          - listenCount int       work listened count
   *                          - commentCount int
   *                          - favouriteCount int
   *                          - transmitCount int
   *                          - description string
   *                          - resource string       work resource uri
   *                          - chorusType ?int       work chorus type
   *                          - chorusCount int
   *                          - originWorkId ?string  work chorus start id
   *                          - createdAt Carbon
   *                          - giftAmount      int gifts received
   *                          - giftCoinAmount   int coins amount received from gifts
   *                          - giftPopularity   popularity amount from gift
   *                          - workPopularity    total popularity
   */
  public function getSelections(
    ?string $selectionId,
    bool $isNext,
    int $size,
    ?string $countryAbbr
  ) : Collection;

  /**
   * Get selected h5 works
   *
   * @param string $countryAbbr
   *
   * @return Collection     elements are \stdClass, properties as below:
   *                          - selectionId string    work selection id
   *                          - workId string         work id
   *                          - workName ?string
   *                          - musicId string        music id which work belongs to
   *                          - userId string         work user
   *                          - cover string          work cover image uri
   *                          - isDefaultCover bool
   *                          - slides array          work slides image ids
   *                          - listenCount int       work listened count
   *                          - commentCount int
   *                          - favouriteCount int
   *                          - transmitCount int
   *                          - description string
   *                          - createdAt Carbon
   */
  public function getH5Selections(string $countryAbbr) : Collection;

  /**
   * Get works by created time desc
   *
   * @param ?string $workdId      used for pagination
   * @param bool $isNext          used for pagination
   * @param int $size             used for pagination
   *
   * @return Collection       elements are \stdClass, properties as below:
   *                          - workId string         work id
   *                          - userId string         user id
   *                          - musicId string        music id
   *                          - workName ?string
   *                          - cover string          user image uri
   *                          - description string    work description, default for share text
   *                          - listenCount int       work listen count by others
   *                          - commentCount int      work comment count by others (not include replies)
   *                          - favouriteCount int    work favourite count by others
   *                          - transmitCount int     work be transmit count by others from sing+
   *                          - resource string       work resource uri
   *                          - createdAt string      datetime, format: Y-m-d H:i:s
   *                          - chorusType ?int       work chorus type
   *                          - chorusCount int
   *                          - originWorkId ?string  work chorus start id
   *                          - giftAmount      int gifts received
   *                          - giftCoinAmount   int coins amount received from gifts
   *                          - giftPopularity   popularity amount from gift
   *                          - workPopularity    total popularity
   */
  public function getWorks(?string $workId, bool $isNext, int $size) : Collection;

  /**
   * Get user works by created time desc
   *
   * @param string $invokeUserId  user who invoke this method
   * @param string $userId
   * @param ?string $workdId  used for pagination
   * @param bool $isNext      used for pagination
   * @param int $size         used for pagination
   *
   * @return Collection       elements are \stdClass, properties as below:
   *                          - workId string         work id
   *                          - userId string         user id
   *                          - musicId string        music id
   *                          - workName ?string
   *                          - cover string          user image uri
   *                          - description string    work description, default for share text
   *                          - listenCount int       work listen count by others
   *                          - commentCount int      work comment count by others (not include replies)
   *                          - favouriteCount int    work favourite count by others
   *                          - transmitCount int     work be transmit count by others from sing+
   *                          - isPrivate bool
   *                          - createdAt string      datetime, format: Y-m-d H:i:s
   *                          - chorusType ?int       work chorus type
   *                          - chorusCount int
   *                          - originWorkId ?string  work chorus start id
   *                          - giftAmount      int gifts received
   *                          - giftCoinAmount   int coins amount received from gifts
   *                          - giftPopularity   popularity amount from gift
   *                          - workPopularity    total popularity
   */
  public function getUserWorks(string $invokeUserId, string $userId, ?string $workId, bool $isNext, int $size) : Collection;

  /**
   * Get user chorus start works
   *
   * @param string $invokeUserId      the person who triger view action
   * @param string $userId            the person who's works ware fetched
   * @param ?string $workdId          used for pagination
   * @param bool $isNext              used for pagination
   * @param int $size                 used for pagination
   *
   * @return Collection               elements are \stdClass, properties as below:
   *                                  - workId string
   *                                  - userId string
   *                                  - musicId string
   *                                  - workName string
   *                                  - cover string      work cover url
   *                                  - isPrivate bool
   *                                  - createdAt string      datetime, format: Y-m-d H:i:s
   *                                  - chorusType ?int       work chorus type
   *                                  - chorusCount int
   */
  public function getUserChorusStartWorks(
    string $invokeUserId,
    string $userId,
    ?string $workId,
    bool $isNext,
    int $size
  ) : Collection;

  /**
   * Get chorus start work's chorus join works
   *
   * @param string $originWorkId      origin chorus start work id
   * @param ?string $workdId          used for pagination
   * @param bool $isNext              used for pagination
   * @param int $size                 used for pagination
   *
   * @return Collection               elements are \stdClass, properties as below:
   *                                  - workId string
   *                                  - userId string
   *                                  - musicId string
   *                                  - workName string
   *                                  - createdAt string      datetime, format: Y-m-d H:i:s
   */
  public function getChorusJoinsOfChorusStart(
    string $originWorkId,
    ?string $workId,
    bool $isNext,
    int $size
  ) : Collection;

  /**
   * Get user works by created time desc
   *
   * @param array $userIds    elements are user id
   * @param ?string $workdId  used for pagination
   * @param bool $isNext      used for pagination
   * @param int $size         used for pagination
   *
   * @return Collection       elements are \stdClass, properties as below:
   *                          - id string             for pagination
   *                          - workId string         work id
   *                          - userId string         user id
   *                          - musicId string        music id
   *                          - workName ?string
   *                          - cover string          user image uri
   *                          - description string    work description, default for share text
   *                          - listenCount int       work listen count by others
   *                          - commentCount int      work comment count by others (not include replies)
   *                          - favouriteCount int    work favourite count by others
   *                          - transmitCount int     work be transmit count by others from sing+
   *                          - resource string       work resource uri
   *                          - createdAt string      datetime, format: Y-m-d H:i:s
   *                          - chorusType ?int       work chorus type
   *                          - chorusCount int
   */
  public function getUsersWorks(
    array $userIds,
    ?string $id,
    bool $isNext,
    int $size
  ) : Collection;
  
  /**
   * Increment work listen count
   *
   * @param string $workId
   *
   * @return void
   */
  public function incrWorkListenCount(string $workId);

  /**
   * Increment work transmit count
   *
   * @param string $workId
   *
   * @return void
   */
  public function incrWorkTransmitCount(string $workId);

  /**
   * Get works by created time desc
   *
   * @param string $workdId   work id
   * @param bool $disableCounter  diable work viewd counter or not
   * @param bool $force           deleted work will be return if true
   *
   * @return \stdClass        properties as below:
   *                          - workId string         work id
   *                          - userId string         user id
   *                          - musicId string        music id
   *                          - workName ?string
   *                          - resource string       work resource uri
   *                          - cover string          cover image uri
   *                          - slides array          elements are image uri
   *                          - description string    work description, default for share text
   *                          - listenCount int       work listen count by others
   *                          - commentCount int      work comment count by others (not include replies)
   *                          - favouriteCount int    work favourite count by others
   *                          - transmitCount int     work be transmit count by others from sing+
   *                          - createdAt string      datetime, format: Y-m-d H:i:s
   *                          - isNormal bool         specify whether work is normal or not
   *                          - isPrivate bool        specify whether work is private or not
   *                          - noAccompaniment bool  indicate whether work has not accompaniment or not
   *                          - chorusType ?int       null stands for solo
   *                          - chorusStartInfo ?\stdClass  exists only if chorus start
   *                            - chorusCount int
   *                          - chorusJoinInfo ?\stdClass   exists only if chorus join
   *                            - originWorkId string
   *                          - giftAmount      int gifts received
   *                          - giftCoinAmount   int coins amount received from gifts
   *                          - giftPopularity   popularity amount from gift
   *                          - workPopularity    total popularity
   */
  public function getDetail(string $workId, bool $disableCounter = false, bool $force = false) : ?\stdClass;

  /**
   * Delete work
   * 
   * @param string $workId
   */
  public function deleteWork(string $workId);

  /**
   * User comment work
   *
   * @param string $authorId
   * @param string $content
   * @param string $workId      work which comment belongs to
   * @param string $commentId   comment which new comment belongs to
   *                            if this values is null, comment for
   *                            work
   * @param int $commentType    comment type
   * @param string $repliedId   string the replied user id for sendGift comment
   * @param string $giftFeedId   string the giftFeedId when comment type == TYPE_SEND_GIFT
   *
   * @return \stdClass          properties as below:
   *                            - commentId string    new comment id
   */
  public function comment(
    string $authorId,
    string $content,
    string $workId,
    ?string $commentId,
    ?int $commentType,
    ?string $repliedId,
    string $giftFeedId = null
  ) : \stdClass;

  /**
   * Fetch a comment by id
   *
   * @param string $commentId
   * @param bool $force       deleted comment will be return if $force is true
   *
   * @return ?\stdClass       properties as below:
   *                          - commentId string
   *                          - repliedCommentId ?string  this value is not empty if
   *                                                      user reply to other comment
   *                          - workId string             specify the work which own this comment
   *                          - authorId string
   *                          - repliedUserId string
   *                          - isNormal bool
   *                          - commentType   int
   *                          - content
   */
  public function getComment(string $commentId, bool $force = false) : ?\stdClass;

  /**
   * Delete comment
   * 
   * @param string $commentId
   */
  public function deleteComment(string $commentId);

  /**
   * Fetch comments by id
   *
   * @param array $commentIds
   * @param bool $force         deleted comment will be return if $force is true
   *
   * @return Collection         properties as below:
   *                              - commentId string
   *                              - repliedCommentId ?string  null stands for comment work, not other's comment
   *                              - authorId string       comment author user id
   *                              - musicId string        work music id
   *                              - work \stdClass
   *                                - workId string
   *                                - workName ?string
   *                              - repliedComment ?\stdClass  如果是某个评论的回复，该字段非空
   *                                - commentId string
   *                                - content string
   *                              - content
   *                              - createdAt \Carbon\Carbon
   *                              - isNormal bool
   *                              - giftFeedId  ?string the giftFeedId
   */
  public function getComments(array $commentIds, bool $force = false) : Collection;

  /**
   * Get work comments
   *
   * @param string $workId      all comments belong to this work
   * @param ?string $commentId  for pagination
   * @param bool $isNext        for pagination
   * @param int $size           for pagination
   *
   * @return Collection         elements are \stdClass, properties as below:
   *                            - workId string
   *                            - commentId string
   *                            - repliedCommentId ?string  null stands for comment work, not other's comment
   *                            - content string      comment content
   *                            - authorId string     author id
   *                            - repliedUserId       replied user id
   *                            - createdAt string    datetime, format: Y-m-d H:i:s
   */
  public function getWorkComments(
    string $workId,
    ?string $commentId,
    bool $isNext,
    int $size
  ) : Collection;

  /**
   * @param string $clientId      generated by client, prevent client from uploading more than once
   *
   * @return string               work id
   */
  public function getUploadedWork(string $clientId) : ?string;

  /**
   * Get user related comments
   *
   * @param string $userId
   * @param ?string $commentId          used for pagination
   * @param bool $isNext                used for pagination
   * @param int $size                   used for pagination
   *
   * @return Collection                 elements are \stdClass, properties as below:
   *                                    - commentId string
   *                                    - repliedCommentId ?string  null stands for comment work, not other's comment
   *                                    - authorId string       comment author user id
   *                                    - musicId string        work music id
   *                                    - work \stdClass
   *                                      - workId string
   *                                    - repliedComment ?\stdClass  如果是某个评论的回复，该字段非空
   *                                      - commentId string
   *                                      - content string
   *                                    - content
   *                                    - createdAt \Carbon\Carbon
   *                                    
   */
  public function getUserRelatedComments(
    string $userId,
    ?string $commentId,
    bool $isNext,
    int $size
  ) : Collection;

  /**
   * User favourit work
   *
   * @param string $userId
   * @param string $workId
   *
   * @return \stdClass          properties as below:
   *                            - favouriteId string
   *                            - increments int  favourite number in this action
   *                                              postive integer stand for add favourite number. eg: 1
   *                                              negative integer stand for cancel favourite number. eg: -1
   *                                              zero stand for nothing happend
   */
  public function favouriteWork(string $userId, string $workId) : \stdClass;

  /**
   * Indicate this work be favourited by specified user
   *
   * @param string $userId
   * @param string $workId
   *
   * @return bool
   */
  public function isFavourite(string $userId, string $workId) : bool;

  /**
   * Check user favourite status in multi works
   *
   * @param string $userId
   * @param array $workIds
   *
   * @return array          key is work id, value is favourite status (bool)
   */
  public function getUserFavouriteStatusOfMultiWorks(string $userId, array $workIds) : array;

  /**
   * Get favourite
   *
   * @param string $favouriteId
   * @param bool $force           fetch trashed record if true
   *
   * @return ?\stdClass           properties as below:
   *                              - favouriteId string
   *                              - userId string       favourite operator user id
   *                              - workId string
   *                              - isNormal bool       indicate whether favourite is trashed or not 
   *                                                    is normal if true
   */
  public function getFavourite(string $favouriteId, bool $force = false) : ?\stdClass;

  /**
   * Get specified work favourite, sort by favourite time desc
   *
   * @param string $workId
   * @param ?string $id         for pagination
   * @param bool $isNext        for pagination
   * @param int $size
   *
   * @return Collection         elements as below:
   *                            - favouriteId string
   *                            - userId string
   */
  public function getWorkFavourite(string $workId, ?string $id, bool $isNext, int $size) : Collection;

  /**
   * Get user's total work listen number
   *
   * @param string $userId
   *
   * @return int
   */
  public function getUserWorkListenNum(string $userId) : int;

  /**
   * @param array $workIds        elements are work id
   *
   * @return Collection           properties as below:
   *                              - workId string
   *                              - userId string
   *                              - musicId string
   *                              - workName ?string
   *                              - noAccompaniment bool    true if work has no accompaniment or else
   *                              - isNormal bool
   *                              - listenCount   int
   *                              - favouriteCount int
   *                              - commentCount   int
   *                              - description    string
   *                              - isPrivate  bool
   *                              - resource   string
   *                              - chorusType int
   *                              - chorusCount int
   *                              - workPopularity  int
   *                              - giftAmount      int
   *                              - giftCoinAmount  int
   *                              - giftPopularity  int
   *
   *
   */
  public function getWorksByIds(array $workIds, bool $withPrivate = false) : Collection;

  /**
   * Get music solo work ranking list
   *
   * @param string $musicId
   *
   * @return Collection       elements as below
   *                          - workId string 
   *                          - listenCount int
   *                          - userId string     work user id
   */
  public function getMusicSoloRankingList(string $musicId) : Collection;

  /**
   * Get music chorus work ranking list
   *
   * @param string $musicId
   *
   * @return Collection       elements as below:
   *                          - workId string
   *                          - chorusCount int
   *                          - userId string     work user id
   */
  public function getMusicChorusRankingList(string $musicId) : Collection;

  /**
   * Get chorus start work accompaniment
   *
   * @param string $workId
   *
   * @return \stdClass        elements as below:
   *                          - userId string
   *                          - resource string     accompaniment uri
   */
  public function getChorusStartAccompaniment(string $musicId) : \stdClass;

  /**
   * @param string $workId
   */
  public function incrWorkChorusCount(string $workId) : bool;

    /**
     * @var string $workId
     */
    public function decrWorkChorusCount(string $workId) : bool;

  /**
   * @param string $musicId
   *
   * @return bool
   */
  public function hasMusicOwnChorusStartWork(string $musicId) : bool;

  /**
   * Get specified recommend work sheet
   * 
   * @param string $sheetId
   *
   * @return ?\stdClass         properties as below:
   *                            - title string      sheet title
   *                            - cover string      sheet cover uri
   *                            - recommendText string
   *                            - works array       elements as below
   *                              - workId string
   *                              - originWorkId string
   *                              - musicId string
   *                              - userId string   work author user id
   *                              - workName ?string
   *                              - cover string
   *                              - listenCount int
   *                              - favouriteCount int
   *                              - transmitCount int     work be transmit count by others from sing+
   *                              - commentCount int
   *                              - description string
   *                              - chorusType ?integer
   *                              - isPrivate bool
   *                              - chorusCount int
   *                              - originWorkId ?string      work chorus start id
   *                              - resource ?string          work resource
   *                              - createdAt \Carbon\Carbon  datetime, format: Y-m-d H:i:s
   *                           - giftAmount      int gifts received
   *                           - giftCoinAmount   int coins amount received from gifts
   *                           - giftPopularity   popularity amount from gift
   *                           - workPopularity    total popularity
   */
  public function getRecommendWorkSheet(string $sheetId) : ?\stdClass;

    /**
     * @param string $version
     * @param int $type
     * @return int
     */
  public function compatCommenType(?string $version, int $type) : int;


    /**
     * @param string $workId
     * @return null|\stdClass
     *              - workId
     *              - userId
     *              - giftAmount
     *              - giftCoinAmount
     *              - giftPopularity
     *              - workPopulariy
     */
  public function getWorkById(string $workId) : ?\stdClass;


    /**
     * @param string $userId
     * @param string $workId
     * @param bool|null|null $isPrivate
     * @param null|string|null $cover
     * @param null|string|null $desc
     * @return mixed
     */
  public function updateWorkInfo(string $userId, string $workId, ?bool $isPrivate = null, ?string $cover = null, ?string $desc = null);

    /**
     * @param string $workTag
     * @param null|string $id
     * @param bool $isNext
     * @param int $size
     * @return Collection       elements are \stdClass, properties as below:
     *                          - id string             for pagination
     *                          - workId string         work id
     *                          - userId string         user id
     *                          - musicId string        music id
     *                          - workName ?string
     *                          - cover string          user image uri
     *                          - description string    work description, default for share text
     *                          - listenCount int       work listen count by others
     *                          - commentCount int      work comment count by others (not include replies)
     *                          - favouriteCount int    work favourite count by others
     *                          - transmitCount int     work be transmit count by others from sing+
     *                          - resource string       work resource uri
     *                          - createdAt string      datetime, format: Y-m-d H:i:s
     *                          - chorusType ?int       work chorus type
     *                          - chorusCount int
     *                          - originWorkId ?string  origin work id
     */
  public function getTagWorksList(string $workTag,
                                  ?string $id,
                                  bool $isNext,
                                  int $size): Collection;

    /**
     * @param string $workTag
     * @param null|string $id
     * @param bool $isNext
     * @param int $size
     * @return Collection       elements are TagWorkSelection
     */
  public function getTagWorkSelection(string $workTag,
                                      ?string $id,
                                      bool $isNext,
                                      int $size): Collection;
}
