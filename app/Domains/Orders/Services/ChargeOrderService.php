<?php

namespace SingPlus\Domains\Orders\Services;

use Carbon\Carbon;
use SingPlus\Contracts\Orders\Services\ChargeOrderService as ChargeOrderServiceContract;
use SingPlus\Contracts\Orders\Constants\ChargeOrder as ChargeOrderConst;
use SingPlus\Domains\Orders\Repositories\ChargeOrderRepository;
use SingPlus\Domains\Orders\Models\ChargeOrder;

class ChargeOrderService implements ChargeOrderServiceContract
{
    /**
     * @var ChargeOrderRepository
     */
    private $chargeOrderRepo;

    public function __construct(
        ChargeOrderRepository $chargeOrderRepo
    ) {
        $this->chargeOrderRepo = $chargeOrderRepo;
    }

    /**
     * {@inheritdoc}
     */
    public function create(
        string $userId,
        int $amount,
        \stdClass $sku,
        int $skuNum,
        \stdClass $paymentInfo
    ) : string {
        $order = ChargeOrder::create([
            'user_id'           => $userId,
            'pay_order_id'      => null,
            'amount'            => $amount,
            'sku_count'         => $skuNum,
            'pay_order_details' => [
                    'currency_amount'   => (int) object_get($paymentInfo, 'currencyPayAmount'),
                    'currency_code'     => object_get($paymentInfo, 'currencyCode'),
                ],
            'status'            => ChargeOrderConst::STATUS_WAITING,
            'status_histories'  => [
                    [
                        'status'    => ChargeOrderConst::STATUS_WAITING,
                        'time'      => Carbon::now()->timestamp,
                    ],
                ],
            'sku'               => [
                    'sku_id'    => $sku->skuId,
                    'price'     => $sku->price,
                    'coins'     => $sku->coins,
                    'title'     => $sku->title,
                ],
        ]);

        return $order->id;
    }


    /**
     * {@inheritdoc}
     */
    public function  getOrder(string $orderId) : ?\stdClass
    {
        $order = $this->chargeOrderRepo->findOneById($orderId);

        return $order ? (object) [
            'orderId'       => $order->id,
            'userId'        => $order->user_id,
            'payOrderId'    => $order->pay_order_id,
            'sku'           => (object) [
                    'skuId' => $order->sku['sku_id'],
                    'coins' => (int) $order->sku['coins'],
                ],
            'skuNum'        => (int) $order->sku_count,
            'status'        => $order->status,
            'isPending'     => $order->isPending(),
        ] : null;
    }

    /**
     * {@inheritdoc}
     */
    public function chargeOrder(string $orderId, string $payOrderId, \stdClass $originalPayInfo)
    {
        $order = $this->chargeOrderRepo->findOneById($orderId);
        if ( ! $order || $order->status != ChargeOrderConst::STATUS_WAITING) {
            return null;
        }
        $order->status = ChargeOrderConst::STATUS_PAID;
        $order->pay_order_id = $payOrderId;
        $statusHistories = $order->status_histories;
        $statusHistories[] = [
            'status'    => ChargeOrderConst::STATUS_PAID,
            'time'      => Carbon::now()->timestamp,
        ];
        $order->status_histories = $statusHistories;
        $payOrderDetails = $order->pay_order_details;
        $payOrderDetails['originalPayInfo'] = (array) $originalPayInfo;
        $order->pay_order_details = $payOrderDetails;
        return $order->save();
    }

    /**
     * {@inheritdoc}
     */
    public function closeOrder(string $orderId, ?string $payOrderId, $originalPayInfo) 
    {
        $originalPayInfo = ($originalPayInfo instanceof \stdClass) ?
            (array) $originalPayInfo : (string) $originalPayInfo;
        $order = $this->chargeOrderRepo->findOneById($orderId);
        if ( ! $order || $order->status != ChargeOrderConst::STATUS_WAITING) {
            return null;
        }
        $order->pay_order_id = $payOrderId;
        $order->status = ChargeOrderConst::STATUS_CLOSED;
        $statusHistories = $order->status_histories;
        $statusHistories[] = [
            'status'    => ChargeOrderConst::STATUS_CLOSED,
            'time'      => Carbon::now()->timestamp,
        ];
        $order->status_histories = $statusHistories;
        $payOrderDetails = $order->pay_order_details;
        $payOrderDetails['originalPayInfo'] = $originalPayInfo;
        $order->pay_order_details = $payOrderDetails;
        return $order->save();
    }
}
