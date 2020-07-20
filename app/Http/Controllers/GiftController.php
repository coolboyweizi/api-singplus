<?php
/**
 * Created by PhpStorm.
 * User: karl
 * Date: 2018/1/30
 * Time: 上午10:38
 */

namespace SingPlus\Http\Controllers;


use Illuminate\Http\Request;
use SingPlus\Services\GiftService;

class GiftController extends Controller
{


    /**
     * get the gift list
     *
     * @param Request $request
     * @param GiftService $giftService
     * @return \Illuminate\Http\Response
     */
    public function getGiftList(Request $request,
                                GiftService $giftService){


        $result = $giftService->getGiftLists();
        return $this->render('gifts.lists', [
                'gifts' =>$result
            ]);
    }

    /**
     * get the gift rank of a work
     *
     * @param Request $request
     * @param GiftService $giftService
     * @return \Illuminate\Http\Response
     */
    public function getWorkGiftRank(Request $request, GiftService $giftService){
        $this->validate($request, [
            'coinAmount'  => 'integer|required_with:isNext',
            'isNext'  => 'boolean',
            'size'    => 'integer|min:1|max:100',
            'workId' => 'uuid|required',
        ]);

        $result = $giftService->getWorkGiftRank(
            $request->request->get('workId'),
            $request->request->get('coinAmount'),
            (bool)$request->request->get('isNext', true),
            (int)$request->request->get('size', 100)
        );

        return $this->render('gifts.ranks',[
           'ranks' => $result->rankInfo,
           'work' => $result->workInfo,
        ]);
    }

    /**
     * get the rank info of a user for a work
     *
     * @param Request $request
     * @param GiftService $giftService
     * @return \Illuminate\Http\Response
     */
    public function getWorkGiftRankForUser(Request $request, GiftService $giftService){
        $this->validate($request, [
            'workId' => 'uuid|required',
        ]);


        $result = $giftService->getWorkGiftRankForUser($request->user()->id, $request->query->get('workId'));

        return $this->render('gifts.userRank', [
            'rank' => $result
        ]);

    }

    /**
     * send a gift for a work
     *
     * @param Request $request
     * @param GiftService $giftService
     * @return \Illuminate\Http\Response
     */
    public function sendGiftForWork(Request $request, GiftService $giftService){

        $this->validate($request, [
            'workId' => 'uuid|required',
            'giftId' => 'uuid|required',
            'number' => 'int|required'
        ]);
        $realCountryAbbr = $request->headers->get('X-RealCountryAbbr');
        $result = $giftService->sendGiftForWork(
            $request->request->get('workId'),
            $request->user()->id,
            $request->request->get('giftId'),
            $request->request->get('number'),
            $realCountryAbbr
        );

        return $this->render('gifts.sendGift', [
           'coins' => $result
        ]);
    }

}