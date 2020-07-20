<?php

namespace SingPlus\Services;

use Log;
use Illuminate\Support\Collection;
use SingPlus\Contracts\Orders\Services\SkuService as SkuServiceContract;
use SingPlus\Contracts\Orders\Services\ChargeOrderService as ChargeOrderServiceContract;
use SingPlus\Contracts\Orders\Constants\ChargeOrder as ChargeOrderConst;
use SingPlus\Contracts\Coins\Services\AccountService as AccountServiceContract;
use SingPlus\Contracts\Coins\Constants\Trans as TransConst;
use SingPlus\Exceptions\Orders\SkuNotExistsException;

class ChargeService
{
    /**
     * @var ChargeOrderServiceContract
     */
    private $chargeOrderService;

    /**
     * @var SkuServiceContract
     */
    private $skuService;

    /**
     * @var AccountServiceContract
     */
    private $accountService;

    public function __construct(
        SkuServiceContract $skuService,
        ChargeOrderServiceContract $chargeOrderService,
        AccountServiceContract $accountService
    ) {
        $this->skuService = $skuService;
        $this->chargeOrderService = $chargeOrderService;
        $this->accountService = $accountService;
    }

    /**
     * Create payment order
     *
     * @param string $loginUserId 
     * @param string $skuId                 coin skuid
     * @param string $currencyPayAmount     local currency payment amount: micro yuan
     * @param string $currencyCode          ISO currency code
     *
     * @return string                       order id
     */
    public function createOrder(
        string $loginUserId,
        string $skuId,
        int $currencyPayAmount,
        string $currencyCode
    ) : string {
        // get sku
        $sku = $this->skuService->getSku($skuId);
        if ( ! $sku) {
            throw new SkuNotExistsException();
        }

        // create order
        $orderId = $this->chargeOrderService->create(
            $loginUserId, $sku->price, $sku, 1, (object) [
                'currencyPayAmount' => $currencyPayAmount,
                'currencyCode'      => $currencyCode,
            ]
        );

        return $orderId;
    }

    /**
     * Validate orders
     *
     * @param string $loginUserId
     * @param array $validateOrders     elements as \stcClass, properities below:
     *                                  - packageName string
     *                                  - skuId string
     *                                  - purchaseToken string
     *                                  - orderId string
     *                                  - payOrderId string
     *                                  - gpPurchaseTime int     micro timestamp
     *
     * @return \stdClass                properities as below:
     *                                  - coinBalance int       user account balance
     *                                  - orders array          properities as below:
     *                                      - skuId string
     *                                      - status string     charge order status
     *                                      - coins int         total charge coins
     *                                      - orderId string    charge order id
     *                                      - payOrderId string pay order id
     */
    public function validateOrders(string $loginUserId, array $validateOrders) : \stdClass 
    {
        $resOrders = collect();
        foreach($validateOrders as $order) {
            // check order
            $chargeOrder = $this->chargeOrderService
                                ->getOrder($order['orderId']);
            if ( ! $chargeOrder || $chargeOrder->userId != $loginUserId) {
                continue;
            }

            $chargeInfo = (object) [
                'skuId'         => $chargeOrder->sku->skuId,
                'coins'         => $chargeOrder->sku->coins * $chargeOrder->skuNum,
                'orderId'       => $chargeOrder->orderId,
                'payOrderId'    => null,
            ];
            if ( ! $chargeOrder->isPending) {
                $chargeInfo->payOrderId = $chargeOrder->payOrderId;
                $chargeInfo->status = $chargeOrder->status;
            } else {
                $chargeInfo = $this->validatePendingChargeOrder(
                    $loginUserId, $order, $chargeInfo
                );
            }

            $resOrders->push($chargeInfo);
        }

        // get account balance
        $balance = $this->accountService->getUserBalance($loginUserId);

        return (object) [
            'coinBalance'   => $balance,
            'orders'        => $resOrders,
        ];
    }

    /**
     * Get charge skus
     *
     * @return Collection       elements as below:
     *                          - skuId string
     *                          - coins int
     *                          - title string
     */
    public function getChargeSkus() : Collection
    {
        return $this->skuService
                    ->getAllSkus()
                    ->map(function ($sku, $_) {
                        return (object) [
                            'skuId' => $sku->skuId,
                            'coins' => $sku->coins,
                            'title' => $sku->title,
                            'price' => $sku->price,
                        ];
                    });
    }
    


    /**
     * Validate pending charge order
     *
     * @param string $userId
     * @param array $order
     * @param \stdClass $chargeInfo
     *
     * @return \stdClass                completed chargeInfo
     */
    private function validatePendingChargeOrder(
        string $userId,
        array $order,
        \stdClass $chargeInfo
    ) : \stdClass {
        // check google
        try {
            $payRes = $this->checkOrderChargeInfo($order);
            $chargeInfo->payOrderId = $payRes->payOrderId;
            if ($payRes->paid) {
                $this->chargeOrderService->chargeOrder(
                    $order['orderId'], $payRes->payOrderId, $payRes->origin
                );
                $chargeInfo->status = ChargeOrderConst::STATUS_PAID;
                $chargeInfo->convertToPaid = true;

                // coins trans
                $balance = $this->accountService->deposit(
                    $userId,
                    $chargeInfo->coins,
                    TransConst::SOURCE_DEPOSIT_CHARGE,
                    $userId,
                    (object) [
                        'order_id'  => $order['orderId'],
                    ]
                );

            } else {
                $this->chargeOrderService->closeOrder(
                    $order['orderId'], $payRes->payOrderId, $payRes->origin
                );
                $chargeInfo->status = ChargeOrderConst::STATUS_CLOSED;
            }
        } catch (\Google_Service_Exception $ex) {
            // this exception stands for google order status invalid,
            // so charge order should be closed
            $this->chargeOrderService->closeOrder(
                $order['orderId'], null, $ex->getMessage()
            );
            $chargeInfo->status = ChargeOrderConst::STATUS_CLOSED;
        } catch (\Exception $ex) {
            // other exception is not about google order, so
            // we should do nothing and notice user to retry
            $chargeInfo->status = ChargeOrderConst::STATUS_WAITING;
            Log::error('Google pay request error', [
                'order' => $order,
                'error' => $ex->getMessage(),
            ]);
        }

        return $chargeInfo;
    }

    /**
     * @param string $order
     *
     * @return \stdClass        elements as below:
     *                          - orderId string        sing+ order id
     *                          - payOrderId string
     *                          - paid bool
     *                          - origin    \stdClass   original pay info
     */
    private function checkOrderChargeInfo(array $order)
    {
        $googleApi = app()->make('google.service');
        $payRes = $googleApi->service('AndroidPublisher')
                            ->purchases_products
                            ->get(
                                $order['packageName'],
                                $order['skuId'],
                                $order['purchaseToken']
                            )
                            ->toSimpleObject();
        return (object) [
            'orderId'       => $payRes->developerPayload,
            'payOrderId'    => $payRes->orderId,
            'paid'          => $payRes->purchaseState == 0,
            'origin'        => $payRes,
        ];
    }
}
