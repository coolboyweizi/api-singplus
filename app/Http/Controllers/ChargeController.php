<?php

namespace SingPlus\Http\Controllers;

use Auth;
use Validator;
use Illuminate\Http\Request;
use SingPlus\Http\Controllers\Controller;
use SingPlus\Contracts\Orders\Constants\ChargeOrder as ChargeOrderConst;
use SingPlus\Services\ChargeService;

class ChargeController extends Controller
{
    /**
     * Create charge order
     */
    public function createOrder(
        Request $request,
        ChargeService $chargeService
    ) {
        $this->validate($request, [
            'productId'         => 'required|string|max:512',
            'priceAmountMicros' => 'required|integer|min:0',
            'priceCurrencyCode' => 'required|string|size:3',
        ]);

        $orderId = $chargeService->createOrder(
            $request->user()->id,
            $request->request->get('productId'),
            (int) $request->request->get('priceAmountMicros'),
            $request->request->get('priceCurrencyCode')
        );

        return $this->renderInfo('success', [
            'developerPayload'  => $orderId,
        ]);
    }

    /**
     * Validate whether orders are valid
     */
    public function validateOrder(
        Request $request,
        ChargeService $chargeService
    ) {
        $request->request->set('orders', $request->json()->all());
        $this->validate($request, [
            'orders'                        => 'required|array|max:6',
            'orders.*.packageName'          => 'required|string|max:64',
            'orders.*.productId'            => 'required|string|max:512',
            'orders.*.purchaseToken'        => 'required|string|max:1000',
            'orders.*.developerPayload'     => 'required|uuid',
        ]);
        
        $orders = $request->request->get('orders');
        array_walk($orders, function (&$item) {
            $item = [
                'packageName'       => $item['packageName'],
                'skuId'             => $item['productId'],
                'purchaseToken'     => $item['purchaseToken'],
                'orderId'           => $item['developerPayload'],
                'payOrderId'        => array_get($item, 'orderId'),
                'gpPurchaseTime'    => array_get($item, 'purchaseTime'),
            ];
        });
        $res = $chargeService->validateOrders($request->user()->id, $orders);

        return $this->renderInfo('success', [
            'currentGold'   => $res->coinBalance,
            'products'      => $res->orders->map(function ($order, $_) {
                                    switch ($order->status) {
                                        case ChargeOrderConst::STATUS_WAITING :
                                            $payStatus = 1;
                                            break;
                                        case ChargeOrderConst::STATUS_PAID:
                                            $payStatus = 0;
                                            break;
                                        default :
                                            $payStatus = 2;
                                    }

                                    return [
                                        'productId' => $order->skuId,
                                        'status'    => $payStatus,
                                        'gainGold'  => object_get($order, 'convertToPaid') ?
                                                        $order->coins : null,
                                    ];
                                }),
        ]);
    }

    /**
     * Get charge skus
     */
    public function getSkus(
        Request $request,
        ChargeService $chargeService
    ) {
        $skus = $chargeService->getChargeSkus();

        return $this->renderInfo('success', [
            'products'  => $skus->map(function ($sku, $_) {
                                return (object) [
                                    'productId'     => $sku->skuId,
                                    'worth'         => $sku->coins,
                                    'title'         => $sku->title,
                                    'price'         => $sku->price,
                                    'description'   => '',
                                ];
                            }),
        ]);
    }
}
