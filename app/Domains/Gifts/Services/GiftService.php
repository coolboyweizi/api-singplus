<?php

/**
 * Created by PhpStorm.
 * User: karl
 * Date: 2018/1/30
 * Time: 上午9:58
 */
namespace SingPlus\Domains\Gifts\Services;

use Illuminate\Support\Collection;
use SingPlus\Contracts\Gifts\Services\GiftService as GiftServiceContract;
use SingPlus\Domains\Gifts\Models\GiftHistory;
use SingPlus\Domains\Gifts\Repositories\GiftContributionRepository;
use SingPlus\Domains\Gifts\Repositories\GiftHistoryRepository;
use SingPlus\Domains\Gifts\Repositories\GiftRepository;
use SingPlus\Domains\Works\Repositories\WorkRepository;
use SingPlus\Domains\Users\Repositories\UserProfileRepository;
use SingPlus\Exceptions\Works\WorkNotExistsException;

class GiftService implements GiftServiceContract
{

    /**
     * @var GiftRepository
     */
    private $giftRepo;
    /**
     * @var GiftHistoryRepository
     */
    private $giftHistoryRepo;
    /**
     * @var GiftContributionRepository
     */
    private $giftContributionRepo;
    /**
     * @var WorkRepository
     */
    private $workRepo;

    /**
     * @var UserProfileRepository
     */
    private $userProfileRepo;


    public function __construct(GiftRepository $giftRepo,
                                GiftHistoryRepository $giftHisRepo,
                                GiftContributionRepository $giftContributionRepo,
                                WorkRepository $workRepository,
                                UserProfileRepository $userProfileRepository)
    {
        $this->giftRepo = $giftRepo;
        $this->giftHistoryRepo = $giftHisRepo;
        $this->giftContributionRepo = $giftContributionRepo;
        $this->workRepo = $workRepository;
        $this->userProfileRepo = $userProfileRepository;
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
     *
     */
    public function giftLists(): Collection
    {
        $gifts = $this->giftRepo->findAll();

        return $gifts->map(function ($gift, $__){

            return (object)[
                'giftId' => $gift->id,
                'giftName' => $gift->name,
                'giftWorth' => $gift->coins,
                'giftType'  => $gift->type,
                'giftIcon'  => (object)[
                    "small" => array_get($gift->icon, 'icon_small'),
                    "big"   => array_get($gift->icon, 'icon_big'),
                ],
                'giftPopularity' => $gift->popularity,
                'giftAnimation'  => (object)[
                    'url' => array_get($gift->animation, 'url'),
                    'type' => array_get($gift->animation, 'type'),
                    'duration' => array_get($gift->animation, 'duration', 1)
                ],
            ];

        });
    }


    /**
     * @param string $workId
     * @param int|null $coinAmount
     * @param bool $isNext
     * @param int $size
     * @return Collection
     *              - senderId   string the user id who sent the gift
     *              - receiverId    string the user id who receive the gift
     *              - workId        string the workId
     *              - coinAmount    int the total coin amount of all gifts
     *              - giftAmount    int the total amount of all gifts
     *              - giftIds     array the ids of gifts
     *              - giftDetail \Collect
     *                  - giftName      string the name of gift
     *                  - giftId        string the id of gift
     *                  - giftIcon  \stdClass
     *                      - small        string  small icon
     *                      - big          string big icon
     *                  - giftNum       int the amount of gifts been sent
     *                  - giftCoin      int the coins amount of gifts been sent
     */
    public function giftRankByWorkId(string $workId, ?int $coinAmount, bool $isNext, int $size): Collection
    {
        $ranks = $this->giftContributionRepo->findAllByWorkIdForPagination($workId, $coinAmount, $isNext, $size);

        $gifts = $this->giftRepo->findAll(true);


        return $ranks->map(function ($rank, $__) use ($gifts) {
            $giftDetails = collect($rank->gift_detail);

            $giftDetails = $giftDetails->map(function ($giftDetail, $__) use($gifts) {
                $gift = $gifts->where('_id', array_get($giftDetail,'gift_id'))->first();

                return (object)[
                    'giftName' => $gift->name,
                    'giftId'   => $gift->id,
                    'giftIcon' => (object)[
                        'small' => array_get($gift->icon, 'icon_small'),
                        'big'   => array_get($gift->icon, 'icon_big')
                    ],
                    'giftNum'   => array_get($giftDetail,'gift_amount'),
                    'giftCoin'  => array_get($giftDetail,'gift_coins'),
                ];

            });
            return (object)[
                'senderId' => $rank->sender_id,
                'receiverId' => $rank->receiver_id,
                'workId'  => $rank->work_id,
                'coinAmount' => $rank->coin_amount,
                'giftAmount' => $rank->gift_amount,
                'giftIds' => $rank->gift_ids,
                'giftDetails' => $giftDetails,

            ];
        });
    }

    /**
     * @param string $workId
     * @param string $userId
     * @return \stdClass
     *              - rank  int the value of rank
     *              - giftAmount    int the amount of gifts the user sent
     *              - coinAmount    int the coin amount of gifts the user sent
     */
    public function getGiftRankByWorkIdForUser(string $workId, string $userId): \stdClass
    {
        $work  = $this->workRepo->findOneById($workId);
        if (!$work){
            throw new WorkNotExistsException();
        }
        $ranks = $this->giftContributionRepo->findAllByWorkIdForPagination($workId, null, true, 100);


        $contrib = $this->giftContributionRepo->findOneByWorkIdAndUserId($workId, $userId);
        if (!$contrib){
            return (object)[
                'rank' => 0,
                'giftAmount' => 0,
                'coinAmount' => 0,
            ];
        }else {
            $index = $ranks->search($contrib);
            return (object)[
                'rank' => $index === false ? 0 : $index + 1,
                'giftAmount' => $contrib->gift_amount,
                'coinAmount' => $contrib->coin_amount,
            ];
        }


    }

    /**
     * @param string $giftId
     * @return \stdClass
     *                  - type  string gift's type
     *                  - name  string gifts's name
     *                  - icon \stdClass
     *                      -small  string small icon
     *                      -big    string big icon
     *                  - coins     int the gift's coins
     *                  - soldAmount    int the amount of gift been sold
     *                  - soldCoinAmount    int , the coin amount of gift been sold
     *                  - status        int , the status of gift
     *                  - popularity    int , the popularity value of gift
     *                  - animation \stdClass
     *                      - type      string
     *                      - duration      int
     *                      - url       string
     *
     */
    public function getDetail(string $giftId): \stdClass
    {
        $gift = $this->giftRepo->findOneById($giftId);
        if ($gift){
            return (object)[
                'type' => $gift->type,
                'name' => $gift->name,
                'icon' => (object)[
                    'small' => array_get($gift->icon, 'icon_small'),
                    'big'   => array_get($gift->icon, 'icon_big')
                ],
                'coins' => $gift->coins,
                'soldAmount' => $gift->sold_amount,
                'soldCoinAmount' => $gift->sold_coin_amount,
                'status' => $gift->status,
                'popularity'  => $gift->popularity,
                'animation' => (object)[
                    'type' => array_get($gift->animation, 'type'),
                    'duration' => array_get($gift->animation, 'duration'),
                    'url'   => array_get($gift->animation, 'url')
                ]
            ];
        }else {
            return null;
        }
    }

    /**
     * @param string $historyId
     * @return null|\stdClass
     *                  - workId    string work id
     *                  - senderId  string user id of who sent the gift
     *                  - receiverId  string user id of who receive the gift
     */
    public function getGiftSendHistory(string $historyId): ?\stdClass
    {
        $history = $this->giftHistoryRepo->findOneById($historyId);
        if ($history){
            return (object)[
                'workId' => $history->work_id,
                'senderId'  => $history->sender_id,
                'receiverId' => $history->receiver_id,
            ];
        }else {
            return null;
        }
    }

    /**
     * @param array $ids
     * @return Collection           properties as below:
     *                              - historyId  string
     *                              - giftId string
     *                              - giftName string
     *                              - giftAmount string
     *                              - icon object
     *                                      -- small small icon
     *                                      -- big bigIcon
     */
    public function getGiftSendHistoryByIds(array $ids): Collection
    {
        return $this->giftHistoryRepo->findAllByIds($ids)
            ->map(function ($history, $__){
                $giftInfo = $history->gift_info;
                $icons = array_get($giftInfo, 'icon');
                return (object)[
                    'historyId' => $history->id,
                    'giftId' => array_get($giftInfo, 'id'),
                    'giftName' => array_get($giftInfo, 'name'),
                    'giftAmount' => $history->amount,
                    'icon' => (object)[
                        'small' =>  array_get($icons, 'icon_small'),
                        'big'   =>  array_get($icons, 'icon_big')
                    ],
                ];
        });
    }

    /**
     * @param string $workId                        all giftHistory belong to this work
     * @param null|string $giftHistroyId            for pagination
     * @param bool $isNext                          for pagination
     * @param int $size                             for pagination
     * @return Collection                   elements are \stdClass, properties as below:
     *                            - workId string
     *                            - amount   string the amount of gift for this history
     *                            - senderId  string
     *                            - receiverId string
     *                            - createdAt   Carbon
     *                            - giftInfo   \stdClass
     *                                  - id   string gift id
     *                                  - type  string gift type
     *                                  - name string gift name
     *                                  - popularity    int gift popularity
     *                                  - icon  array
     *                                          -icon_small string
     *                                          -icon_big   string
     */
    public function getWorkGiftHistory(
        string $workId,
        ?string $giftHistroyId,
        bool $isNext,
        int $size): Collection
    {
        $displayOrder = null;
        if ($giftHistroyId) {
            $giftHistory = $this->giftHistoryRepo->findOneById($giftHistroyId, ['display_order']);
            $displayOrder = $giftHistory ? $giftHistory->display_order : null;
        }

        return $this->giftHistoryRepo
            ->findWorkAllForPagination($workId, $displayOrder, $isNext, $size)
            ->map(function ($history, $_) {
                $giftInfo = $history->gift_info;
                return (object) [
                    'workId'        => $history->work_id,
                    'amount'       => $history->amount,
                    'senderId'      => $history->sender_id,
                    'receiverId' => $history->receiver_id,
                    'createdAt'     => $history->created_at,
                    'giftInfo'      => (object)[
                        'id'  => array_get($giftInfo, 'id'),
                        'type' => array_get($giftInfo, 'type'),
                        'name' => array_get($giftInfo, 'name'),
                        'popularity' => array_get($giftInfo, 'popularity'),
                        'icon' => array_get($giftInfo, 'icon')
                    ],
                ];
            });
    }

    /**
     * @param string $workId
     * @param null|string $id
     * @param bool $isNext
     * @param int $size
     * @return Collection           element is
     *                              - contributionId   string
     *                              - userId           string
     */
    public function getWorkGiftContribution(string $workId, ?string $id, bool $isNext, int $size): Collection
    {
        $displayOrder = null;
        if ($id) {
            $favourite = $this->giftContributionRepo->findOneById($id);
            $displayOrder = $favourite ? $favourite->updated_at->format('Y-m-d H:i:s') : null;
        }
        return $this->giftContributionRepo
            ->findAllByWorkIdForPaginationLatest($workId, $displayOrder, $isNext, $size)
            ->map(function ($contribution) {
                return (object) [
                    'contributionId'   => $contribution->id,
                    'userId'        => $contribution->sender_id,
                ];
            });
    }

    /**
     * @param string $workId
     * @param string $senderId
     * @param string $receiverId
     * @param string $giftId
     * @param int $coins
     * @param int $amount
     * @param \stdClass $giftDetail
     * @return \stdClass
     *              - giftHistoryId     string
     */
    public function createWorkGiftHistory(string $workId, string $senderId,
                                          string $receiverId, string $giftId,
                                          int $coins, int $amount, \stdClass $giftDetail): \stdClass
    {
        $giftHistory = $this->giftHistoryRepo->createOne($senderId, $receiverId, $workId, $amount, [
            'id' => $giftId,
            'type' => $giftDetail->type,
            'name' => $giftDetail->name,
            'icon' => [
                'icon_small' => $giftDetail->icon->small,
                'icon_big'   => $giftDetail->icon->big,
            ],
            'coins' => $giftDetail->coins,
            'sold_amount' => $giftDetail->soldAmount,
            'sold_coin_amount' => $giftDetail->soldCoinAmount,
            'status' => $giftDetail->status,
            'popularity' => $giftDetail->popularity,
            'animation' => [
                'type' => $giftDetail->animation->type,
                'url'  => $giftDetail->animation->url,
                'duration' => $giftDetail->animation->duration,
            ]
        ]);
        return (object)[
            'giftHistoryId' => $giftHistory->id,
        ];
    }

    /**
     * @param $historyId
     * @return mixed
     */
    public function deleteWorkGiftHistory($historyId)
    {
        $history = $this->giftHistoryRepo->findOneById($historyId, ['status']);
        if ($history) {
            $history->status = GiftHistory::STATUS_DELETED;
            $history->save();
        }
    }

    /**
     * @param string $workId
     * @param string $senderId
     * @param string $receiverId
     * @param string $giftId
     * @param int $coins
     * @param int $amount
     * @param \stdClass $giftDetail
     * @return mixed
     */
    public function updateIncrCoinsAndAmount(string $workId, string $senderId,
                                             string $receiverId, string $giftId,
                                             int $coins, int $amount, int $giftPopularity)
    {
        // contributioin
        $contribution = $this->giftContributionRepo->findOrCreateBySenderIdAndWorkId($senderId, $workId, $receiverId);
        $this->giftContributionRepo->incrementCoinsAndAmountById($contribution->id, $giftId, $coins, $amount);

        //gift increment sold info
        $this->giftRepo->incrSolCoin($giftId, $coins);
        $this->giftRepo->incrSoldAmount($giftId, $amount);
        //work increment gift into
        $this->workRepo->incrWorkGiftAmount($workId, $amount);
        $this->workRepo->incrWorkGiftCoinAmount($workId, $coins);
        $this->workRepo->incrWorkGiftPopularity($workId, $amount * $giftPopularity);
        //increment user consume coins and gift_consume_coins
        $this->userProfileRepo->incrGiftConsumeCoins($senderId, $coins);
        $this->userProfileRepo->incrTotalConsumeCoins($senderId, $coins);
    }
}