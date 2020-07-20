<?php

namespace SingPlus\Contracts\Orders\Services;

use Illuminate\Support\Collection;

interface ChargeOrderService
{
    /**
     * Create charge order
     *
     * @param string $userId        order ownner id
     * @param int $amount           order total amount, unit: micro USD
     * @param \stdClass $sku        elements are:
     *                              - skuId string
     *                              - price int     sku price, unit: micro USD
     *                              - coins int     coin number
     *                              - title string  sku title
     * @param int $skuNum           sku number
     * @param \stdClass $paymentInfo    payment request info
     */
    public function create(
        string $userId,
        int $amount,
        \stdClass $sku,
        int $skuNum,
        \stdClass $paymentInfo
    ) : string;


    /**
     * Get order by id
     * 
     * @param string $orderId
     *
     * @param ?\stdClass         elements as below:
     *                          - orderId string
     *                          - userId string         order woner
     *                          - payOrderId ?string    payment order id
     *                          - sku \stdClass
     *                              - skuId string
     *                              - coins int
     *                          - skuNum int
     *                          - status int            please to see \SingPlus\Contracts\Orders\Contracts\ChargeOrder::STATUS_XXXX
     *                          - isPending bool
     */
    public function getOrder(string $orderId) : ?\stdClass;

    /**
     * Charge a waiting order
     *
     * @param string $orderId
     * @param string $payOrderId
     * @param \stdClass $originalPayInfo
     */
    public function chargeOrder(string $orderId, string $payOrderId, \stdClass $originalPayInfo);

    /**
     * Close a waiting order
     *
     * @param string $orderId
     * @param ?string $payOrderId
     * @param \stdClass|string $originalPayInfo
     */
    public function closeOrder(string $orderId, ?string $payOrderId, $originalPayInfo);
}
