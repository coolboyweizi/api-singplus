<?php

namespace SingPlus\Contracts\Boomcoin\Services;
use Illuminate\Support\Collection;

/**
 * Created by PhpStorm.
 * User: karl
 * Date: 2018/4/8
 * Time: 上午10:46
 */

interface BoomcoinService
{

    /**
     * @param string $abbr  country abbr
     * @return Collection elements are object
     *          - productId  商品ID
     *          - coins      商品价值金币数
     *          - boomcoins  商品价值boomcoin数
     *          - dollars    商品价值美元数
     *
     */
    public function getProductList(string $abbr):Collection;


    /**
     * @param string $mobile
     * @return \stdClass
     */
    public function getBoomcoinBalance(string $mobile):\stdClass;

    /**
     * @param string $orderId
     * @param string $mobile
     * @param int $amount
     * @return \stdClass
     */
    public function consumeBoomcoin(string $orderId, string $mobile, int $amount):\stdClass;

    /**
     * @param string $userId
     * @param string $mobile
     * @param string $countryAbbr
     * @param string $productId
     * @return  null|\stdClass
     *              - orderId   string the order id of boomcoin order
     *              - amount    int the amount of boomcoin amount for this order
     *              - msisnd    string the msisnd for this boomcoin order
     *              - coins     int     the singplus coins for this boomcoin order
     */
    public function createBoomcoinOrder(string $userId, string $mobile, string $countryAbbr, string $productId ):?\stdClass;


    /**
     * @param string $orderId
     * @param int $status
     * @param int|null $balance
     * @param null|string $transactionId
     * @return mixed
     */
    public function updateBoomcoinOrder(string $orderId, int $status, ?int $balance, ?string $transactionId);

    /**
     * @param string $orderId
     * @return \stdClass
     */
    public function checkBoomcoinOrder(string $orderId):\stdClass;

    /**
     * @param string $userId
     * @return null|\stdClass
     */
    public function getUserLatestPendingOrder(string $userId):?\stdClass;

    /**
     * @param string $orderId
     * @return null|\stdClass
     */
    public function getOrderDetail(string $orderId):?\stdClass;

    /**
     * @param string $productId
     * @return null|\stdClass
     */
    public function getProductDetail(string $productId):?\stdClass;

    /**
     * @param string $productId
     * @return mixed
     */
    public function incrProductSoldAmount(string $productId);

}