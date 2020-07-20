<?php

/**
 * Created by PhpStorm.
 * User: karl
 * Date: 2018/1/24
 * Time: 下午4:12
 */
namespace SingPlus\Domains\DailyTask\Models;

use SingPlus\Support\Database\Eloquent\MongodbModel;
class DailyTask extends MongodbModel
{
    const STATUS_DELETE = 0;
    const STATUS_NORMAL = 1;

    const NEED_FINISH = 1;
    const NEED_AWARD = 2;
    const FINISHED = 3;

    protected $collection = 'daily_task';

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'user_id',
        'type',       // type please to see \SingPlus\Contracts\DailyTask\Constants\DailyTask::class
        'days',     // the days for continuous finishing
        'detail',   //object other info
        'status',
        'finished_status',
        'finished_at',   // format Y-m-d H:i:s,
        'history_id',   // uuid the dailytask history id
    ];
}