<?php
/**
 * Created by PhpStorm.
 * User: karl
 * Date: 2018/5/9
 * Time: 下午11:18
 */

namespace SingPlus\Activities;


use Carbon\Carbon;
use Illuminate\Http\Request;
use Auth;
use Cache;
use SingPlus\Jobs\Activities\BoomsingCoinActivityJob;

class BoomsingCoinActivity implements BaseActivity
{


    public static function getEndTime():Carbon{
        return Carbon::create(2018, 05, 31, 23, 59, 59);
    }


    /**
     * check if the activity is expired
     * @return bool
     */
    public function isExpired(): bool
    {
        return BoomsingCoinActivity::getEndTime()->lt(Carbon::now());
    }

    /**
     * check if the activity is available
     * @param Request $request
     * @return bool
     */
    public function isAvaliable(Request $request): bool
    {
        $apiChannel = config('apiChannel.channel', 'singplus');
        if ($apiChannel != "boomsing"){
            return false;
        }
        $user = $request->user();
        if ($user != null){
            $userId = $user->id;
            $cacheData = Cache::get($this->getCacheKey($userId));

            if($cacheData == null){
                return true;
            }
        }
        return false;
    }

    /**
     * do what the activity need to do
     * @param Request $request
     * @return mixed
     */
    public function performActivity(Request $request)
    {
        $userId = $request->user()->id;
        $job = new BoomsingCoinActivityJob($request->user()->id);
        dispatch($job);
    }

    public static function getCacheKey(String $userId){
        return sprintf("boomsingCoinActivity:%s:done", $userId);
    }

}