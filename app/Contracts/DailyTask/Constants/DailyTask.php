<?php
namespace SingPlus\Contracts\DailyTask\Constants;
use Carbon\Carbon;


/**
 * Created by PhpStorm.
 * User: karl
 * Date: 2018/1/24
 * Time: 下午3:08
 */
class DailyTask
{
    const TYPE_SIGN_UP         = 'daily_task_a_signup';
    const TYPE_PUBLISH         = 'daily_task_b_publish';
    const TYPE_SHARE           = 'daily_task_c_share';
    const TYPE_COMMENT         = 'daily_task_d_comment';
    const TYPE_INVITE          = 'daily_task_e_invite';
    const TYPE_GIFT            = 'daily_task_f_gift';

    public static function getDailyTaskValue(string $type, int $baseVal, int $stepValue, int $maximum, int $days){

        return min($baseVal + ($days - 1) * $stepValue, $maximum);

    }

    public static function getDailyTaskTitle(string $type, string $title,int $days, int $value):string{
        return sprintf($title, $days, $value);
    }

    public static function getDailyTaskDesc(string $type, string $desc, int $days, int $value) :string{
        return sprintf($desc, $value, $days);
    }

    public static $validType = [
      DailyTask::TYPE_SIGN_UP,
      DailyTask::TYPE_COMMENT,
      DailyTask::TYPE_INVITE,
      DailyTask::TYPE_PUBLISH,
      DailyTask::TYPE_SHARE,
      DailyTask::TYPE_GIFT
    ];

    public static function compareDays(?string $countryAbbr, string $format, string $finishedAt, Carbon $now):int {
        $tz = null;
        $countrycode = collect(config('countrycode'))->filter(function ($item, $_) use ($countryAbbr) {
            return $item[0] == $countryAbbr;
        })->first();
        if ($countrycode) {
            // UTC+02:00 ==> +02:00
            $tz = substr($countrycode[3], 3) ?: 'UTC';
        }

        $termLast = Carbon::createFromFormat($format,$finishedAt, null)->setTimezone($tz)->format('Ymd');
        $termNow = $now->copy()->setTimezone($tz)->format('Ymd');
        $days = (int)$termNow - (int)$termLast;
        //$now->setTimezone(null);
        return $days < 0 ? 0 : $days;

    }

}
