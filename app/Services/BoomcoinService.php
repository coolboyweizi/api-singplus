<?php
/**
 * Created by PhpStorm.
 * User: karl
 * Date: 2018/4/8
 * Time: 下午3:30
 */

namespace SingPlus\Services;

use SingPlus\Contracts\Boomcoin\Services\BoomcoinService as BoomcoinServiceContract;
use SingPlus\Contracts\Coins\Constants\Trans;
use SingPlus\Contracts\Users\Services\UserService as UserServiceContract;
use SingPlus\Contracts\Coins\Services\AccountService as AccountServiceContract;
use SingPlus\Domains\Boomcoin\Models\Order;
use SingPlus\Domains\Boomcoin\Models\Product;
use SingPlus\Exceptions\Boomcoin\BalanceNotEnoughException;
use SingPlus\Exceptions\Boomcoin\ExchangeException;
use SingPlus\Exceptions\Boomcoin\GeneralException;
use SingPlus\Exceptions\Users\UserMobileNotBoundException;
use SingPlus\Exceptions\Users\UserNotExistsException;
use SingPlus\Exceptions\Boomcoin\UserNotExistsException as BoomcoinUserNotExistsException;
use SingPlus\Jobs\CheckBoomcoinOrder as CheckBoomcoinOrderJob;

class BoomcoinService
{

    /**
     * @var BoomcoinServiceContract
     */
    private $boomcoinService;

    /**
     * @var UserServiceContract
     */
    private $userService;

    /**
     * @var AccountServiceContract
     */
    private $accountService;

    public function __construct(BoomcoinServiceContract $boomcoinService,
                                UserServiceContract $userService,
                                AccountServiceContract $accountService)
    {
        $this->boomcoinService = $boomcoinService;
        $this->userService = $userService;
        $this->accountService = $accountService;
    }


    /**
     * @param string $userId
     * @return \stdClass
     *          - balance  int
     *          - products Collection
     *              - productId  商品ID
     *              - coins      商品价值金币数
     *              - boomcoins  商品价值boomcoin数
     *              - dollars    商品价值美元数-
     *
     * @throws BoomcoinUserNotExistsException
     * @throws GeneralException
     * @throws UserMobileNotBoundException
     * @throws UserNotExistsException
     */
    public function getBoomcoinListWithBalance(string $userId):\stdClass{
        $user = $this->userService->fetchUser($userId);
        if (!$user){
            throw new UserNotExistsException();
        }

        $mobile = $user->getMobile();
        if (is_null($mobile)){
            throw new UserMobileNotBoundException();
        }

        $boomcoins = $this->boomcoinService->getBoomcoinBalance($mobile);
        if ($boomcoins->code != 0){
            if ($boomcoins->code == 2000){
                throw new BoomcoinUserNotExistsException();
            }else {
                throw new GeneralException(sprintf('general exception with code %d', $boomcoins->code));
            }
        }
        $user->boomcoin_country_code = $boomcoins->countryCode;
        $user->save();
        $products = $this->boomcoinService->getProductList($boomcoins->countryCode);

        return (object)[
            'balance' => $boomcoins->balance,
            'products' => $products
        ];


    }

    /**
     * @param string $userId
     * @param string $productId
     * @return \stdClass
     *              -orderId    string the order id
     *              -status     int Order::STATUS_XXXXXX
     *              -boomcoinBalance    int  boomcoin balance
     *              -incrCoins      int      the coins to be incremented
     *              -coinBalance    int      the coin balance
     *
     * @throws BalanceNotEnoughException
     * @throws BoomcoinUserNotExistsException
     * @throws GeneralException
     * @throws UserMobileNotBoundException
     * @throws UserNotExistsException
     */
    public function exchangeBoomcoinsToCoins(string $userId, string $productId):\stdClass{
        $user = $this->userService->fetchUser($userId);
        if (!$user){
            throw new UserNotExistsException();
        }

        $mobile = $user->getMobile();
        if (is_null($mobile)){
            throw new UserMobileNotBoundException();
        }

        $boomcoinCountryCode = $user->boomcoin_country_code;
        if (!$boomcoinCountryCode){
            throw new GeneralException('the country code not exist');
        }

        $order = $this->boomcoinService->createBoomcoinOrder($userId, $mobile, $boomcoinCountryCode, $productId);
        if (is_null($order)){
            throw new GeneralException('the product not exists');
        }

        try {
            $res = $this->boomcoinService->consumeBoomcoin($order->orderId, $order->msisnd, $order->amount);
        }catch (\Exception $e){
            //gatewayservice 请求出现异常的时候，最好去boomcoin那边检查一下这笔订单的交易情况
            $checkOrderJob = (new CheckBoomcoinOrderJob($userId, $order->orderId))->onQueue('sing_plus_hierarchy_update');
            dispatch($checkOrderJob);
            throw new ExchangeException(sprintf('boomcoin gateway service exception'));
        }
        if ($res->code != 0){
            $this->boomcoinService->updateBoomcoinOrder($order->orderId, Order::STATUS_FAILURE, null, null);
            if ($res->code == 2000){
                throw new BoomcoinUserNotExistsException();
            }else if ($res->code == 2001){
                throw new BalanceNotEnoughException();
            }else {
                throw new GeneralException(sprintf('general exception with code %d', $res->code));
            }
        }

        $this->boomcoinService->updateBoomcoinOrder($order->orderId, Order::STATUS_SUCCESS,
            $res->balance, $res->transactionId);
        $this->boomcoinService->incrProductSoldAmount($productId);
        // 向用户账户中增加金币
        $this->accountService->deposit($userId, $order->coins, Trans::SOURECE_DEPOSIT_BOOMCOIN, $userId, (object)[
            'order_id' => $order->orderId
        ]);

        return (object)[
            'orderId' => $order->orderId,
            'status' => Order::STATUS_SUCCESS,
            'boomcoinBalance' => $res->balance,
            'incrCoins' => $order->coins,
            'coinBalance' => $this->accountService->getUserBalance($userId)
        ];

    }

    /**
     * @param string $userId
     * @param string|null $orderId
     * @return \stdClass
     *          -orderWell  bool true: order is normal or not exist , false: order is pending and fixed the transactions
     *          -boomcoins int   the boomcoin balance and exits when orderWell be false
     *          -totalCoins int  the coins balance and exists when orderWell be false
     *          -orderId    string  the order id and exists when orderWell be false
     */
    public function checkBoomcoinOrder(string $userId, string $orderId = null):\stdClass{
        if (is_null($orderId)){
            $order = $this->boomcoinService->getUserLatestPendingOrder($userId);
        }else {
            $order = $this->boomcoinService->getOrderDetail($orderId);
        }

        if (is_null($order) || $order->status != Order::STATUS_PENDING){
            return (object)[
                'orderWell' => true,
            ];
        }
        try {
            $res = $this->boomcoinService->checkBoomcoinOrder($order->orderId);
        }catch (\Exception $e){
            throw new GeneralException(sprintf('boomcoin gateway service error occurs when check order status'));
        }
        if ($res->code == 0){
            $status = $res->status;
            $orderStatus = ($status == 'COMPLETED') ? Order::STATUS_SUCCESS : Order::STATUS_FAILURE;
            $boomcoinBalance = object_get($res, 'balance', null);
            $transactionId = object_get($res, 'transactionId', null);
            $this->boomcoinService->updateBoomcoinOrder($order->orderId, $orderStatus, $boomcoinBalance, $transactionId);
            if ($orderStatus == Order::STATUS_SUCCESS ){
                $this->boomcoinService->incrProductSoldAmount($order->productId);
                $product = $this->boomcoinService->getProductDetail($order->productId);
                // 向用户账户中增加金币
                $this->accountService->deposit($userId, $product->coins, Trans::SOURECE_DEPOSIT_BOOMCOIN, $userId, (object)[
                    'order_id' => $order->orderId
                ]);
                return (object)[
                    'orderWell' => false,
                    'boomcoins' => $boomcoinBalance ? $boomcoinBalance : 0,
                    'totalCoins' => $this->accountService->getUserBalance($userId),
                    'incrCoin' => $product->coins,
                    'orderId'    => $order->orderId
                ];
            }
        }

        return (object)[
            'orderWell' => true,
        ];

    }
}