<?php
/**
 * Created by PhpStorm.
 * User: karl
 * Date: 2018/4/8
 * Time: 下午4:19
 */

namespace SingPlus\Http\Controllers;
use Illuminate\Http\Request;
use SingPlus\Services\BoomcoinService;

class BoomcoinController extends Controller
{

    /**
     * get user's boomcoins balance and product list
     *
     * @param Request $request
     * @param BoomcoinService $boomcoinService
     * @return \Illuminate\Http\Response
     */
    public function getProductList(
        Request $request,
        BoomcoinService $boomcoinService
    ){
        $res = $boomcoinService->getBoomcoinListWithBalance(
            $request->user()->id
        );

        return $this->render('boomcoin.list', [
            'balance' => $res->balance,
            'products' => $res->products,
        ]);
    }

    /**
     * exchange coins by boomcoin
     *
     * @param Request $request
     * @param BoomcoinService $boomcoinService
     * @return \Illuminate\Http\Response
     */
    public function exchangeCoins(
        Request $request,
        BoomcoinService $boomcoinService
    ){
         $this->validate($request, [
             'productId'    => 'required|string|max:512',
         ]);
        $res = $boomcoinService->exchangeBoomcoinsToCoins(
            $request->user()->id,
            $request->request->get('productId')
        );

        return $this->render('boomcoin.exchange',[
            'boomcoins' => $res->boomcoinBalance,
            'incrCoins' => $res->incrCoins,
            'coinBalance' => $res->coinBalance,
            'orderId'   => $res->orderId
        ]);
    }

    /**
     * @param Request $request
     * @param BoomcoinService $boomcoinService
     * @return \Illuminate\Http\Response
     */
    public function checkOrderStatus(
        Request $request,
        BoomcoinService $boomcoinService
    ){
        $res = $boomcoinService->checkBoomcoinOrder(
            $request->user()->id
        );

        return $this->render('boomcoin.check', [
            'result' => $res
        ]);
    }
}