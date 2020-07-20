<?php
/**
 * Created by PhpStorm.
 * User: karl
 * Date: 2018/1/18
 * Time: 下午4:58
 */

namespace SingPlus\Services;
use Illuminate\Support\Collection;
use Log;
use SingPlus\Contracts\News\Services\NewsServices as NewsServiceContract;
use SingPlus\Contracts\News\Constants\News as NewsConstants;
use SingPlus\Contracts\Users\Services\UserProfileService as UserProfileServiceContract;
use SingPlus\Contracts\Works\Constants\WorkConstant;
use SingPlus\Contracts\Works\Services\WorkService as WorkServiceContract;
use SingPlus\Contracts\Friends\Services\FriendService as FriendServiceContract;
use SingPlus\Contracts\Musics\Services\MusicService as MusicServiceContract;
use SingPlus\Contracts\Storages\Services\StorageService as StorageServiceContract;
use SingPlus\Domains\Friends\Services\GraphFriendService;
use SingPlus\Exceptions\News\NewsActionForbiddenException;
use SingPlus\Exceptions\News\NewsNotExistsException;
use SingPlus\Exceptions\News\NewsTypeInvalidException;
use SingPlus\Support\Helpers\Arr;

class NewsService
{

    /**
     * @var NewsServiceContract
     */
    private $newService;

    /**
     * @var UserProfileServiceContract
     */
    private $userProfileService;

    /**
     * @var WorkServiceContract
     */
    private $workService;

    /**
     * @var FriendServiceContract
     */
    private $friendService;

    /**
     * @var MusicServiceContract
     */
    private $musicService;

    private $storageService;

    /**
     * @var GraphFriendService
     */
    private $graphFriendService;

    public function __construct(
        NewsServiceContract $newsService,
        UserProfileServiceContract $userProfileService,
        WorkServiceContract $workService,
        FriendServiceContract $friendService,
        MusicServiceContract $musicService,
        StorageServiceContract $storageService,
        GraphFriendService $graphFriendService
    )
    {
        $this->newService = $newsService;
        $this->userProfileService = $userProfileService;
        $this->workService = $workService;
        $this->friendService = $friendService;
        $this->musicService = $musicService;
        $this->storageService = $storageService;
        $this->graphFriendService = $graphFriendService;
    }

    /**
     * @param string $userId
     * @param string $type
     * @param string $desc
     * @param string $workId
     * @return \stdClass
     *              - news_id       string the id of the news been created
     * @throws NewsTypeInvalidException
     */
    public function createNews(string $userId, string $type, string $desc, string $workId){
        if ( ! in_array($type, NewsConstants::$validTypes)) {
            throw new NewsTypeInvalidException();
        }
        $result = $this->newService->createNews($userId, $type, $workId, $desc);
        return $result;
    }

    /**
     * @param string $userId
     * @param string $newsId
     * @return mixed
     * @throws NewsActionForbiddenException
     * @throws NewsNotExistsException
     */
    public function deleteNews(string $userId, string $newsId){
        $news = $this->newService->getDetail($newsId);
        if ( !$news) {
            throw new NewsNotExistsException();
        }

        if ($news->userId != $userId) {
            throw new NewsActionForbiddenException();
        }
        return $this->newService->deleteNews($newsId);
    }


    /**
     * @param string $userId
     * @param bool $isSelf
     * @param $newsId
     * @param $isNext
     * @param $size
     * @param null|string $otherId
     * @return Collection
     *                - id      string the id of news
     *                - newsId  string the id of news
     *                - type    string type of news
     *                - desc    string desc of news
     *                - createdAt   Carbon
     *                - author  \stdClass
     *                      - userId    string  the user id of news
     *                      - avatar    string
     *                      - nickname  string
     *                - work    \stdClass
     *                      - workId    string work id
     *                      - workName  string
     *                      - user \stdClass
     *                          - userId    string
     *                          - avatar    string
     *                          - nickname  string
     *                      - music \stdClass
     *                          - musicId   string
     *                          - name      string
     *                      - cover     string
     *                      - chorusType    int
     *                      - chorusCount   int
     *                      - originWorkUser    null\stdClass
     *                              - userId    string
     *                              - avatar
     *                              - nickname
     *                      - description   string
     *                      - resource  string
     *                      - listenCount   int
     *                      - favouriteCount    int
     *                      - commentCount  int
     *                      - transmitCount int
     *                      - shareLink string
     *                      - createdAt
     *                      - status    int
     *                      - giftAmount    int
     *                      - giftCoinAmount    int
     *                      - giftPopularity    int
     *                      - workPopularity    int
     */
    public function getNewsLists(string $userId, bool $isSelf, $newsId, $isNext, $size, ?string $otherId):Collection{
//        $getUserFollowingAt = $this->getMillisecond();
        $newsUserIds = array();
        $followingUserIds = $this->friendService->getFollowingUserIds($userId);
        if (!$isSelf){
            $newsUserIds = $followingUserIds;
        }
        if ($otherId){
            $newsUserIds[] = $otherId;
        }else {
            $newsUserIds[] = $userId;
        }
        $newsUserIds = Arr::quickUnique($newsUserIds);
        // 获取用户的动态
//        $getNewsAt = $this->getMillisecond();
        $news = $this->newService->getUsersNews($newsUserIds, $newsId, $isNext, $size);
        $worksId = [];
        $userIds = [];
        $news->each(function ($new, $_) use (&$worksId, &$userIds) {
            if ($new->workId) {
                $worksId[] = $new->workId;
                $userIds[] = $new->userId;
            }
        });
        // 获取用户动态相关的作品
//        $getWorksAt = $this->getMillisecond();
        $works = $this->workService->getWorksByIds(Arr::quickUnique($worksId));
        $musicIds = [];
        $originWorkIds = [];
        $originWorkUserMap = [];
//        $getOriginWorksAt = $this->getMillisecond();
        $works->each(function ($work, $_) use (&$userIds, &$musicIds, &$originWorkIds) {
            $userIds[] = $work->userId;
            $musicIds[] = $work->musicId;
            if ($work->originWorkId) {
                $originWorkIds[] = $work->originWorkId;
            }
        });
        $originWorks = $this->workService->getWorksByIds(Arr::quickUnique($originWorkIds));
        $originWorks->each(function ($work, $_) use (&$userIds, &$originWorkUserMap) {
            $userIds[] = $work->userId;
            $originWorkUserMap[$work->workId] = $work->userId;
        });
        // 获取用户信息
        $userIds = Arr::quickUnique($userIds);
//        $getUsersProfileAt = $this->getMillisecond();
        $users = $this->userProfileService->getUserProfiles($userIds);
//        $getMusicsAt = $this->getMillisecond();
        $musics = $this->musicService->getMusics(Arr::quickUnique($musicIds));
//        $getRelationsAt = $this->getMillisecond();
//        $userRelations = $this->friendService->getUserRelationship($userId, $userIds);
//        $mapResultAt = $this->getMillisecond();
        // 先flip下，然后在判断work->userId是否在数组里，直接用isset,这比array_in快得多
        $flipFollowingUsers = array_flip($followingUserIds);
        $obj = $news->map(function ($new, $_) use ($userId, $flipFollowingUsers, $works,$users, $musics, $originWorkUserMap){
            $newsUser = $users->where('userId', $new->userId)->first();
            if (!$newsUser){
                Log::alert('Data missed. news miss user profile', [
                    'news_id' => $new->newsId,
                    'user_id' => $new->userId,
                ]);
                return null;
            }
            $work = $works->where('workId', $new->workId)->first();
            if (!$work){
                Log::alert('Data missed. news miss work info', [
                    'news_id' => $new->newsId,
                    'work_id' => $new->workId,
                ]);
                return null;
            }
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
              'id'      => $new->id,
              'newsId'  => $new->newsId,
              'type'    => $new->type,
              'desc'    => $new->desc,
              'createdAt' => $new->createdAt,
              'author'  => (object)[
                          'userId'    => $newsUser->userId,
                          'avatar'    => $this->storageService->toHttpUrl($newsUser->avatar),
                          'nickname'  => $newsUser->nickname,
                          'verified'  => $newsUser->verified,
                        ],
              'work'   => (object)[
                  'workId'          => $work->workId,
                  'workName'        => $work->workName,
                  'user'            => (object) [
                      'userId'    => $user->userId,
                      'avatar'    => $this->storageService->toHttpUrl($user->avatar),
                      'nickname'  => $user->nickname,
                      'verified'  => $user->verified,
                      'popularity' => $user->popularity_herarchy->popularity,
                      'hierarchyIcon' => $this->storageService->toHttpUrl($user->popularity_herarchy->icon),
                      'hierarchyName' => $user->popularity_herarchy->name,
                      'hierarchyGap' => $user->popularity_herarchy->gapPopularity,
                      'hierarchyLogo' => $this->storageService->toHttpUrl($user->popularity_herarchy->iconSmall),
                      'hierarchyAlias' => $user->popularity_herarchy->alias
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
                  'status'          => $work->status,
                  'giftAmount'      => $work->giftAmount,
                  'giftCoinAmount'  => $work->giftCoinAmount,
                  'giftPopularity'  => $work->giftPopularity,
                  'workPopularity'  => $work->workPopularity,
              ],
              'relationships' => null,
            ];
            if ($originWorkUser) {
                $res->work->originWorkUser = (object) [
                    'userId'    => $originWorkUser->userId,
                    'avatar'    => $this->storageService->toHttpUrl($originWorkUser->avatar),
                    'nickname'  => $originWorkUser->nickname,
                ];
            }
            if ($userId != $work->userId ){
                $res->relationships = (object)[
                    // 客户端只用到当前登录到用户是否关注了这个作品到作者字段，故isFollower没用，这里写为false
                    'isFollowing' => isset($flipFollowingUsers[$work->userId]),
                    'isFollower'  => false,
                ];
            }
            return $res;

        })->filter(function ($new, $_) {
            return ! is_null($new);
        });
//        $mapEndAt = $this->getMillisecond();
//        print sprintf("\n------userIdsCount:--%d-------\n", count($userIds));
//        print sprintf("\n------getUsersFollowingsCost:--%.0f-------\n", $getNewsAt - $getUserFollowingAt);
//        print sprintf("\n------getNewsCost:--%.0f-------\n", $getWorksAt - $getNewsAt);
//        print sprintf("\n------getWorksCost:--%.0f-------\n", $getOriginWorksAt - $getWorksAt);
//        print sprintf("\n------getOriginWorksCost:--%.0f-------\n", $getUsersProfileAt - $getOriginWorksAt);
//        print sprintf("\n------getUsersProfilesCost:--%.0f-------\n", $getMusicsAt - $getUsersProfileAt);
//        print sprintf("\n------getMusicCost:--%.0f-------\n", $getRelationsAt - $getMusicsAt);
//        print sprintf("\n------getRelationsCosts:--%.0f-------\n", $mapResultAt - $getRelationsAt);
//        print sprintf("\n------mapResultCosts:--%.0f-------\n",  $mapEndAt- $mapResultAt);
//        print sprintf("\n------totalCosts:--%.0f-------\n",  $mapEndAt- $getUserFollowingAt);

        return $obj;
    }


    /**
     * @param ?string $loginUserId
     * @param bool $showSingle          是否查看特定用户的动态，否则查看登录用户关注者动态
     * @param ?string $targetUserId     如果$showSingle is true, 特定用户的id
     * @param int $page
     * @param $size
     *
     * @return Collection
     *                - id      string the id of news
     *                - newsId  string the id of news
     *                - type    string type of news
     *                - desc    string desc of news
     *                - createdAt   Carbon
     *                - author  \stdClass
     *                      - userId    string  the user id of news
     *                      - avatar    string
     *                      - nickname  string
     *                - work    \stdClass
     *                      - workId    string work id
     *                      - workName  string
     *                      - user \stdClass
     *                          - userId    string
     *                          - avatar    string
     *                          - nickname  string
     *                      - music \stdClass
     *                          - musicId   string
     *                          - name      string
     *                      - cover     string
     *                      - chorusType    int
     *                      - chorusCount   int
     *                      - originWorkUser    null\stdClass
     *                              - userId    string
     *                              - avatar
     *                              - nickname
     *                      - description   string
     *                      - resource  string
     *                      - listenCount   int
     *                      - favouriteCount    int
     *                      - commentCount  int
     *                      - transmitCount int
     *                      - shareLink string
     *                      - createdAt
     *                      - status    int
     *                      - giftAmount    int
     *                      - giftCoinAmount    int
     *                      - giftPopularity    int
     *                      - workPopularity    int
     */
    public function getNewsLists_v4(
        ?string $loginUserId,
        bool $showSingle,
        ?string $targetUserId,
        int $page,
        int $size
    ) : Collection {
        if ($showSingle) {
            $targetUserId = $targetUserId ?: $loginUserId;
            if ( ! $targetUserId) {
                return collect();
            }
            $newsIds = $this->graphFriendService
                            ->getUserLatestNews($targetUserId, $page, $size)
                            ->toArray();
            // todo
        } else {
            if ( ! $loginUserId) {
                return collect();
            }
            $newsIds = $this->graphFriendService
                            ->getFollowingLatestNews($loginUserId, $page, $size)
                            ->toArray();
        }
        $news = $this->newService->getNews($newsIds);

        $worksId = [];
        $userIds = [];
        $news->each(function ($new, $_) use (&$worksId, &$userIds) {
            if ($new->workId) {
                $worksId[] = $new->workId;
                $userIds[] = $new->userId;
            }
        });

        // 获取用户动态相关的作品
        $works = $this->workService->getWorksByIds(Arr::quickUnique($worksId));
        $musicIds = [];
        $originWorkIds = [];
        $originWorkUserMap = [];
        $workUserIds = [];
        $works->each(function ($work, $_) use (
            &$workUserIds,
            &$userIds,
            &$musicIds,
            &$originWorkIds
        ) {
            $workUserIds[] = $work->userId;
            $userIds[] = $work->userId;
            $musicIds[] = $work->musicId;
            if ($work->originWorkId) {
                $originWorkIds[] = $work->originWorkId;
            }
        });
        $originWorks = $this->workService->getWorksByIds(Arr::quickUnique($originWorkIds));
        $originWorks->each(function ($work, $_) use (&$userIds, &$originWorkUserMap) {
            $userIds[] = $work->userId;
            $originWorkUserMap[$work->workId] = $work->userId;
        });
        $workUserRelation = collect();
        if ($loginUserId) {
            $workUserRelation = $this->friendService
                                     ->getUserRelationship($loginUserId, Arr::quickUnique($workUserIds));
        }

        // 获取用户信息
        $userIds = Arr::quickUnique($userIds);
        $users = $this->userProfileService->getUserProfiles($userIds);
        $musics = $this->musicService->getMusics(Arr::quickUnique($musicIds));

        $obj = $news->map(function ($new, $_) use (
            $loginUserId,
            $workUserRelation,
            $works,
            $users,
            $musics,
            $originWorkUserMap
        ) {
            $newsUser = $users->where('userId', $new->userId)->first();
            if (!$newsUser){
                Log::alert('Data missed. news miss user profile', [
                    'news_id' => $new->newsId,
                    'user_id' => $new->userId,
                ]);
                return null;
            }
            $work = $works->where('workId', $new->workId)->first();
            if (!$work){
                Log::alert('Data missed. news miss work info', [
                    'news_id' => $new->newsId,
                    'work_id' => $new->workId,
                ]);
                return null;
            }
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
              'id'      => $new->id,
              'newsId'  => $new->newsId,
              'type'    => $new->type,
              'desc'    => $new->desc,
              'createdAt' => $new->createdAt,
              'author'  => (object)[
                          'userId'    => $newsUser->userId,
                          'avatar'    => $this->storageService->toHttpUrl($newsUser->avatar),
                          'nickname'  => $newsUser->nickname,
                          'verified'  => $newsUser->verified,
                        ],
              'work'   => (object)[
                  'workId'          => $work->workId,
                  'workName'        => $work->workName,
                  'user'            => (object) [
                      'userId'    => $user->userId,
                      'avatar'    => $this->storageService->toHttpUrl($user->avatar),
                      'nickname'  => $user->nickname,
                      'verified'  => $user->verified,
                      'popularity' => $user->popularity_herarchy->popularity,
                      'hierarchyIcon' => $this->storageService->toHttpUrl($user->popularity_herarchy->icon),
                      'hierarchyName' => $user->popularity_herarchy->name,
                      'hierarchyGap' => $user->popularity_herarchy->gapPopularity,
                      'hierarchyLogo' => $this->storageService->toHttpUrl($user->popularity_herarchy->iconSmall),
                      'hierarchyAlias' => $user->popularity_herarchy->alias
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
                  'status'          => $work->status,
                  'giftAmount'      => $work->giftAmount,
                  'giftCoinAmount'  => $work->giftCoinAmount,
                  'giftPopularity'  => $work->giftPopularity,
                  'workPopularity'  => $work->workPopularity,
              ],
              'relationships' => null,
            ];
            if ($originWorkUser) {
                $res->work->originWorkUser = (object) [
                    'userId'    => $originWorkUser->userId,
                    'avatar'    => $this->storageService->toHttpUrl($originWorkUser->avatar),
                    'nickname'  => $originWorkUser->nickname,
                ];
            }
            if ($loginUserId && $loginUserId != $work->userId) {
                $relation = $workUserRelation->where('userId', $work->userId)->first();
                $res->relationships = (object) [
                    'isFollowing'   => $relation->isFollowing,
                    'isFollower'    => $relation->isFollower,
                ];
            }
            return $res;

        })->filter(function ($new, $_) {
            return ! is_null($new);
        });

        return $obj;
    }

    function getMillisecond() {
        list($s1, $s2) = explode(' ', microtime());
        return (float)sprintf('%.0f', (floatval($s1) + floatval($s2)) * 1000);
    }

}
