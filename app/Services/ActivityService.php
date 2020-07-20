<?php
/**
 * Created by PhpStorm.
 * User: karl
 * Date: 2018/5/9
 * Time: 下午11:46
 */

namespace SingPlus\Services;

use Cache;
use SingPlus\Activities\BoomsingCoinActivity;
use SingPlus\Activities\GaaoplusCoinActivity;
use SingPlus\Contracts\Coins\Constants\Trans;
use SingPlus\Contracts\Coins\Services\AccountService as AccountServiceContract;
use SingPlus\Contracts\TXIM\Services\ImService as ImServiceContract;
use SingPlus\Jobs\IMSendSimpleMsg;

class ActivityService
{

    /**
     * @var AccountServiceContract
     */
    private $accountService;

    /**
     * @var ImServiceContract
     */
    private $imService;

    public function __construct(AccountServiceContract $accountService, ImServiceContract $imService)
    {
        $this->accountService = $accountService;
        $this->imService = $imService;
    }

    public function sendCoinForBoomsingUser($userId){
        $cacheData = Cache::get(BoomsingCoinActivity::getCacheKey($userId));
        if ($cacheData != null){
            return;
        }
        // 加金币
        $this->accountService->deposit($userId, 100,
            Trans::SOURCE_DEPOSIT_ADMIN_GIVE,
            config('txim.senders.Annoucements'),
            null);
        Cache::put(BoomsingCoinActivity::getCacheKey($userId), "done", BoomsingCoinActivity::getEndTime());
        // 发送私信给用户
        $msg = "Welcome to Boomsing! 100 coins were sent to you for free, happy gifting! To celebrate the new release of Boomsing, we’re giving away everyone 100 coins till May 31, spread the words and have fun!";
        $job = (new IMSendSimpleMsg(config('txim.senders.Contests'), $userId, $msg, $msg))->delay(1);
        dispatch($job);
    }

    public function sendCoinForGaaoplusUser($userId){
        $cacheData = Cache::get(GaaoplusCoinActivity::getCacheKey($userId));
        if ($cacheData != null){
            return;
        }
        // 加金币
        $this->accountService->deposit($userId, 100,
            Trans::SOURCE_DEPOSIT_ADMIN_GIVE,
            config('txim.senders.Annoucements'),
            null);
        Cache::put(GaaoplusCoinActivity::getCacheKey($userId), "done", GaaoplusCoinActivity::getEndTime());
        // 发送私信给用户
        $msg = "Welcome to Gaao+! 100 coins were sent to you for free, happy gifting! To celebrate the new release of Gaao+, we’re giving away everyone 100 coins till June 30, spread the words and have fun!";
        $job = (new IMSendSimpleMsg(config('txim.senders.Contests'), $userId, $msg, $msg))->delay(1);
        dispatch($job);
    }

}