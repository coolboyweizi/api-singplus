<?php

namespace SingPlus\Domains\Boomcoin\Services;
/**
 * Created by PhpStorm.
 * User: karl
 * Date: 2018/4/8
 * Time: 上午10:50
 */

use Illuminate\Support\Collection;
use SingPlus\Contracts\Boomcoin\Constants\Boomcoin;
use SingPlus\Contracts\Boomcoin\Services\BoomcoinService as BoomcoinServiceContract;
use SingPlus\Domains\Boomcoin\Models\Order;
use SingPlus\Domains\Boomcoin\Models\Product;
use SingPlus\Domains\Boomcoin\Repositories\OrderRepository;
use SingPlus\Domains\Boomcoin\Repositories\ProductRepository;
use SingPlus\Support\Database\SeqCounter;

class BoomcoinService implements BoomcoinServiceContract
{


    /**
     * @var ProductRepository
     */
    private $productRepository;

    /**
     * @var OrderRepository
     */
    private $orderRepository;

    public function __construct(OrderRepository $orderRepository, ProductRepository $productRepository)
    {
        $this->orderRepository = $orderRepository;
        $this->productRepository = $productRepository;
    }

    /**
     * @param string $abbr country abbr
     * @return Collection elements are object
     *          - productId  商品ID
     *          - coins      商品价值金币数
     *          - boomcoins  商品价值boomcoin数
     *          - dollars    商品价值美元数
     *
     */
    public function getProductList(string $abbr): Collection
    {
        $exchangeRate = Boomcoin::getExchangeRate(strtoupper($abbr));
        $products = $this->productRepository->findAll();
        if ($products->isEmpty()){
            // 由于后台功能还没有做，如果表里没有数据，就先从配置文件中将数据存入表里
            $this->insertProductsFromConfig();
            $products = $this->productRepository->findAll();
        }
        return $products->map(function($product, $__) use ($exchangeRate){
            return (object)[
                'productId' => $product->id,
                'coins' => $product->coins,
                'boomcoins' => $product->dollars * $exchangeRate,
                'dollars' => $product->dollars,
            ];
        });

    }

    private  function insertProductsFromConfig(){
       $lists = config('boomcoin.exchangeList');
       $display_order = 100;
       foreach ($lists as $key => $item){
            $product = new Product();
            $product->display_order = $display_order;
            $product->dollars = (int)$key;
            $product->coins = $item;
            $product->status = Product::STATUS_NORMAL;
            $product->save();
            $display_order = $display_order + 100;
       }
    }

    /**
     * @param string $mobile  format country+number eg. 254712686240
     * @return \stdClass
     *          -countryCode  string
     *          -balance    int
     */
    public function getBoomcoinBalance(string $mobile): \stdClass
    {
        $url = rtrim(config('boomcoin.domain'), '/') . '/bcs/api/getboomcoins';
        $res = GatewayService::channel()
            ->requestJson('GET', $url, [
                'msisnd' => $mobile
            ]);
        $code = $res->responseCode;
        $message = $res->message;
        return $code == 0 ? (object)[
            'countryCode' => $message->countryCode,
            'balance'  => $message->boomcoins,
            'code'   => 0,
        ] : (object)[
            'code' => $code
        ];
    }

    /**
     * @param string $orderId
     * @param string $mobile
     * @param int $amount
     * @return \stdClass
     */
    public function consumeBoomcoin(string $orderId, string $mobile, int $amount): \stdClass
    {
        $url = rtrim(config('boomcoin.domain'), '/') . '/bcs/api/payment';
        $res = GatewayService::channel()
            ->requestJson('GET', $url, [
                'msisnd' => $mobile,
                'reference' => $orderId,
                'amount' => $amount
            ]);
        $code = $res->responseCode;
        $message = $res->message;
        return $code == 0 ? (object)[
            'countryCode' => $message->countryCode,
            'balance'  => $message->boomcoins,
            'orderId' => $message->reference,
            'transactionId' => $message->transaction_tracking_id,
            'code'   => 0,
        ] : (object)[
            'code' => $code
        ];
    }


    /**
     * @param string $userId
     * @param string $mobile
     * @param string $countryAbbr
     * @param string $productId
     * @return null|\stdClass
     *              - orderId   string the order id of boomcoin order
     *              - amount    int the amount of boomcoin amount for this order
     *              - msisnd    string the msisnd for this boomcoin order
     *              - coins     int     the singplus coins for this boomcoin order
     */
    public function createBoomcoinOrder(string $userId, string $mobile, string $countryAbbr, string $productId): ?\stdClass
    {
        $exchangeRate = Boomcoin::getExchangeRate(strtoupper($countryAbbr));
        $product = $this->productRepository->findOneById($productId);
        if (!$product){
            return null;
        }

        $order = new Order();
        $order->user_id = $userId;
        $order->product_id = $productId;
        $order->amount = $product->dollars * $exchangeRate;
        $order->msisnd =  $mobile;
        $order->country_code = $countryAbbr;
        $order->status = Order::STATUS_PENDING;
        $order->display_order = SeqCounter::getNext('boomcoin_order');
        $order->source = config('apiChannel.channel', 'singplus');
        $order->save();
        return (object)[
            'orderId' => $order->id,
            'amount'  => $order->amount,
            'msisnd'  => $order->msisnd,
            'coins'   => $product->coins
        ];
    }

    /**
     * @param string $orderId
     * @param int $status
     * @param int|null $balance
     * @param null|string $transactionId
     * @return mixed
     */
    public function updateBoomcoinOrder(string $orderId, int $status, ?int $balance, ?string $transactionId)
    {
        $order = $this->orderRepository->findOneById($orderId);
        $order->status = $status;
        if ($balance){
            $order->balance = $balance;
        }

        if ($transactionId){
            $order->transaction_id = $transactionId;
        }

        $order->save();
    }

    /**
     * @param string $orderId
     * @return \stdClass
     */
    public function checkBoomcoinOrder(string $orderId): \stdClass
    {
        $url = rtrim(config('boomcoin.domain'), '/') . '/bcs/api/paymentStatus';
        $res = GatewayService::channel()
            ->requestJson('GET', $url, [
                'reference' => $orderId,
            ]);
        $code = $res->responseCode;
        $message = $res->message;
        return $code == 0 ? (object)[
            'status' => $message->status,
            'transactionId' => object_get($message, 'transaction_tracking_id'),
            'countryCode' => object_get($message, 'countryCode'),
            'balance'  => object_get($message, 'boomcoins'),
            'code'   => 0,
        ] : (object)[
            'code' => $code
        ];
    }

    /**
     * @param string $userId
     * @return null|\stdClass
     */
    public function getUserLatestPendingOrder(string $userId): ?\stdClass
    {
        $order = $this->orderRepository->findLatestOneByUserId($userId);
        return (!is_null($order)) ? (object)[
            'userId' => $order->user_id,
            'orderId' => $order->id,
            'productId' => $order->product_id,
            'transactoinBoomcoin' => $order->amount,
            'msisnd' => $order->msisnd,
            'status' => $order->status,
        ] : null;
    }

    /**
     * @param string $orderId
     * @return null|\stdClass
     */
    public function getOrderDetail(string $orderId):?\stdClass
    {
        $order = $this->orderRepository->findOneById($orderId);
        return (is_null($order)) ? (object)[
            'userId' => $order->user_id,
            'orderId' => $order->id,
            'productId' => $order->product_id,
            'transactoinBoomcoin' => $order->amount,
            'msisnd' => $order->msisnd,
            'status' => $order->status,
        ] : null;
    }

    /**
     * @param string $productId
     * @return null|\stdClass
     */
    public function getProductDetail(string $productId):?\stdClass
    {
        $product = $this->productRepository->findOneById($productId);
        if (!$product){
            return null;
        }
        return (object)[
            'productId' => $product->id,
            'coins' => $product->coins,
            'dollars' => $product->dollar,
        ];
    }

    /**
     * @param string $productId
     * @return mixed
     */
    public function incrProductSoldAmount(string $productId)
    {
       $this->productRepository->incrementSoldAmount($productId);
    }
}