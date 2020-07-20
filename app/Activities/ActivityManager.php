<?php
/**
 * Created by PhpStorm.
 * User: karl
 * Date: 2018/5/9
 * Time: 下午11:12
 */

namespace SingPlus\Activities;


use Illuminate\Http\Request;

class ActivityManager
{

    private static $availableActivityMap = [
        'boomsingcoin20180531' => \SingPlus\Activities\BoomsingCoinActivity::class,
        'gaaopluscoin20180630' => \SingPlus\Activities\GaaoplusCoinActivity::class
    ];


    public static function parseActivity(String $activityName): ?BaseActivity{
        $activityClass = array_get(self::$availableActivityMap, $activityName);
        return $activityClass ? new $activityClass() : null;

    }

    public static function handleActivity(Request $request){
        $activitiesStr = config('activities.activity', "");
        $arr = explode("/", $activitiesStr);
        foreach ($arr as $str){
            $activity = self::parseActivity($str);
            if ($activity != null && !$activity->isExpired() && $activity->isAvaliable($request)){
                $activity->performActivity($request);
            }
        }
    }

}