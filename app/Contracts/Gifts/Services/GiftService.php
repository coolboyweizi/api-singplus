<?php

/**
 * Created by PhpStorm.
 * User: karl
 * Date: 2018/1/30
 * Time: 上午9:51
 */
namespace SingPlus\Contracts\Gifts\Services;
use Illuminate\Support\Collection;
interface GiftService
{
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
     *
     */
    public function giftLists() : Collection;


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
    public function giftRankByWorkId(string $workId, ?int $coinAmount, bool $isNext, int $size) : Collection;


    /**
     * @param string $workId
     * @param string $userId
     * @return \stdClass
     *              - rank  int the value of rank
     *              - giftAmount    int the amount of gifts the user sent
     *              - coinAmount    int the coin amount of gifts the user sent
     */
    public function getGiftRankByWorkIdForUser(string $workId, string $userId) : \stdClass;

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
     *
     */
    public function getDetail(string $giftId) : ?\stdClass;

    /**
     * @param string $historyId
     * @return null|\stdClass
     *                  - workId    string work id
     *                  - senderId  string user id of who sent the gift
     *                  - receiverId  string user id of who receive the gift
     */
    public function getGiftSendHistory(string $historyId) : ?\stdClass;

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
    public function getGiftSendHistoryByIds(array $ids) : Collection;


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
        int $size) : Collection;

    /**
     * @param string $workId
     * @param null|string $id
     * @param bool $isNext
     * @param int $size
     * @return Collection           element is
     *                              - contributionId   string
     *                              - userId           string
     */
    public function getWorkGiftContribution(string $workId, ?string $id, bool $isNext, int $size) : Collection;


    /**
     * @param string $workId
     * @param string $senderId
     * @param string $receiverId
     * @param string $giftId
     * @param int $coins
     * @param int $amount
     * @param \stdClass $giftDetail
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
     *
     * @return \stdClass
     *              - giftHistoryId     string
     */
    public function createWorkGiftHistory(string $workId, string $senderId,
                                          string $receiverId, string $giftId,
                                          int $coins, int $amount, \stdClass $giftDetail) :\stdClass;

    /**
     * @param $historyId
     * @return mixed
     */
    public function deleteWorkGiftHistory($historyId);

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
                                             int $coins, int $amount, int $giftPopularity);
}