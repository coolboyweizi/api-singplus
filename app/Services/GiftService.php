<?php
/**
 * Created by PhpStorm.
 * User: karl
 * Date: 2018/1/30
 * Time: 上午10:05
 */

namespace SingPlus\Services;

use Illuminate\Support\Collection;
use SingPlus\Contracts\Gifts\Services\GiftService as GiftServiceContract;
use SingPlus\Contracts\Users\Services\UserProfileService as UserProfileServiceContract;
use SingPlus\Contracts\Coins\Services\AccountService as AccountServiceContract;
use SingPlus\Contracts\Works\Services\WorkService as WorkServiceContract;
use SingPlus\Contracts\DailyTask\Services\DailyTaskService as DailyTaskServiceContract;
use SingPlus\Contracts\DailyTask\Constants\DailyTask as DailyTaskConstant;
use SingPlus\Contracts\Storages\Services\StorageService as StorageServiceContract;
use SingPlus\Exceptions\Coins\AccountBalanceNotEnoughException;
use SingPlus\Exceptions\Gifts\GiftNotExistsException;
use SingPlus\Exceptions\Works\WorkNotExistsException;
use SingPlus\Contracts\Coins\Constants\Trans;
use SingPlus\Events\Gifts\UserSendGiftForWork as UserSendGiftForWorkEvent;
use SingPlus\Jobs\UpdateWorkPopularity as UpdateWorkPopularityJob;
use SingPlus\Jobs\UpdateWealthHierarchy as UpdateWealthHierarchyJob;
use SingPlus\Events\Works\WorkUpdateCommentGiftCacheData as WorkUpdateCommentGiftCacheDataEvent;

class GiftService
{

    /**
     * @var GiftServiceContract
     */
    private $giftService;

    /**
     * @var UserProfileServiceContract
     */
    private $userProfileService;

    /**
     * @var AccountServiceContract
     */
    private $accountService;

    /**
     * @var WorkServiceContract
     */
    private $workService;

    /**
     * @var DailyTaskServiceContract
     */
    private $dailyTaskService;

    /**
     * @var
     */
    private $storageService;

    public function __construct(GiftServiceContract $giftService,
                                UserProfileServiceContract $userProfileService,
                                AccountServiceContract $accountService,
                                WorkServiceContract $workService,
                                DailyTaskServiceContract $dailyTaskService,
                                StorageServiceContract $storageService)
    {
        $this->giftService = $giftService;
        $this->userProfileService = $userProfileService;
        $this->accountService = $accountService;
        $this->workService = $workService;
        $this->dailyTaskService = $dailyTaskService;
        $this->storageService = $storageService;
    }

    /**
     * @return Collection
     *          - giftId    string the gift id
     *          - giftName      string the name of gift
     *          - giftWorth     int the coins of gift
     *          - giftType      string the type of gift
     *          - giftIcon \stdClass
     *                  - small     string the small icon
     *                  - big       string the big icon
     *          - giftPopularity    int the popularity of gift
     *          - giftAnimation     \stdClass
     *                  - url      string the gif url of gift's animation
     *                  - type      int the animation type of gift
     *                  - duration  int the duration of animation
     */
    public function getGiftLists():Collection{
        $lists = $this->giftService->giftLists();

        return $lists->map(function ($gift, $__){
            $gift->giftIcon->small = $this->storageService->toHttpUrl($gift->giftIcon->small);
            $gift->giftIcon->big = $this->storageService->toHttpUrl($gift->giftIcon->big);
            $gift->giftAnimation->url = $this->storageService->toHttpUrl($gift->giftAnimation->url);
            return $gift;
        });
    }

    /**
     * @param string $workId
     * @param int|null $coinAmount
     * @param bool $isNext
     * @param int $size
     * @return \stdClass
     *                      -rankInfo Collect
     *                          -userId string
     *                          -nickname string
     *                          -avatar string
     *                          -coins  int
     *                          -gifts  \stdclass
     *                              - giftName      string the name of gift
     *                              - giftId        string the id of gift
     *                              - giftIcon  \stdClass
     *                                  - small        string  small icon
     *                                  - big          string big icon
     *                              - giftNum       int the amount of gifts been sent
     *                              - giftCoin      int the coins amount of gifts been sent
     *                          - consumeCoins  int     the amount of consume coins
     *                          - wealthHierarchyName   string  the wealth hierarchy name
     *                          - wealthHierarchyIcon   string  the icon url for wealth hierarchy
     *                      -workInfo
     *                          - workId
     *                          - userId
     *                          - giftAmount
     *                          - giftCoinAmount
     *                          - giftPopularity
     *                          - workPopulariy
     * @throws WorkNotExistsException
     */
    public function getWorkGiftRank(string $workId, ?int $coinAmount, bool $isNext, int $size) : \stdClass {

        $work  = $this->workService->getWorkById($workId);
        if (!$work){
            throw new WorkNotExistsException();
        }

        $ranks =  $this->giftService->giftRankByWorkId($workId, $coinAmount, $isNext, $size);
        $usersId = [];
        $ranks->each(function ($rank, $_) use (&$usersId) {
            if ($rank->senderId && ! in_array($rank->senderId, $usersId)) {
                $usersId[] = $rank->senderId;
            }
        });

        $userProfiles = $this->userProfileService->getUserProfiles($usersId);
        $rankInfo = $ranks->map(function ($rank, $__) use ( $userProfiles) {

            $rank->giftDetails = $rank->giftDetails->map(function ($detail, $__){
                $detail->giftIcon->small = $this->storageService->toHttpUrl($detail->giftIcon->small);
                $detail->giftIcon->big  = $this->storageService->toHttpUrl($detail->giftIcon->big);
                return $detail;
            });

            $userProfile = $userProfiles->where('userId', $rank->senderId)->first();
            return (object)[
                'userId' => $userProfile->userId,
                'nickname' => $userProfile->nickname,
                'avatar'   => $this->storageService->toHttpUrl($userProfile->avatar),
                'coins' => $rank->coinAmount,
                'gifts' => $rank->giftDetails,
                'consumeCoins' => $userProfile->wealth_herarchy->consumeCoins,
                'wealthHierarchyName'  => $userProfile->wealth_herarchy->name,
                'wealthHierarchyIcon'  => $this->storageService->toHttpUrl($userProfile->wealth_herarchy->icon),
                'wealthHierarchyLogo'   => $this->storageService->toHttpUrl($userProfile->wealth_herarchy->iconSmall),
                'wealthHierarchyAlias'  => $userProfile->wealth_herarchy->alias,
            ];

        });
        return (object)[
            'rankInfo' => $rankInfo,
            'workInfo' => $work,
        ];
    }

    /**
     * @param string $userId
     * @param string $workId
     * @return \stdClass
     *              - rank  int the value of rank
     *              - giftAmount    int the amount of gifts the user sent
     *              - coinAmount    int the coin amount of gifts the user sent
     *
     */
    public function getWorkGiftRankForUser(string $userId, string $workId) : \stdClass{
         return $this->giftService->getGiftRankByWorkIdForUser($workId, $userId);
    }

    /**
     * @param string $workId
     * @param string $userId
     * @param string $giftId
     * @param int $amount
     * @param null|string $realCountryAbbr
     * @return \stdClass
     *                  -balance int
     *                  -costCoins  int
     * @throws GiftNotExistsException
     * @throws WorkNotExistsException
     */
    public function sendGiftForWork(string $workId, string $userId, string $giftId,
                                    int $amount, ?string $realCountryAbbr) :\stdClass{

        $work = $this->workService->getWorkById($workId);
        if (!$work){
            throw new WorkNotExistsException();
        }
        $gift = $this->giftService->getDetail($giftId);
        if (!$gift){
            throw new GiftNotExistsException();
        }

        $totalCoins = $amount * $gift->coins;

        $result = $this->giftService->createWorkGiftHistory($workId, $userId, $work->userId,
            $giftId, $totalCoins, $amount, $gift);
        try {
            //减金币
            $balance = $this->accountService->withdraw($userId, $totalCoins, Trans::SOURCE_WITHDRAW_GIVE_GIFT,
                $userId, (object)[
                    'giftHistory_id' => $result->giftHistoryId
                ]);
        } catch (AccountBalanceNotEnoughException $e){
            $this->giftService->deleteWorkGiftHistory($result->giftHistoryId);
            throw $e;
        }
        $this->giftService->updateIncrCoinsAndAmount($workId, $userId, $work->userId,
            $giftId, $totalCoins, $amount, $gift->popularity);


        // 完成每日任务
        $this->dailyTaskService->resetDailyTaskLists($userId, $realCountryAbbr);
        $this->dailyTaskService->finisheDailyTask($userId, DailyTaskConstant::TYPE_GIFT);

        // 创建送礼物的feed
        if ($userId != $work->userId){
            event(new UserSendGiftForWorkEvent($result->giftHistoryId));
        }

        //  执行job 更新作品人气值 ,用户人气值, 用户等级等相关
        $popularityJobs = (new UpdateWorkPopularityJob($workId))->onQueue('sing_plus_hierarchy_update');
        dispatch($popularityJobs);

        //  更新财富等级
        $wealthJob = (new UpdateWealthHierarchyJob($userId))->onQueue('sing_plus_hierarchy_update');
        dispatch($wealthJob);

        //  更新cached data for workService getMultiWorksCommentsAndGifts
        event(new WorkUpdateCommentGiftCacheDataEvent($workId, $userId, null));

        return (object)[
            'balance' => $balance,
            'costCoins' => $totalCoins
        ];
    }
}