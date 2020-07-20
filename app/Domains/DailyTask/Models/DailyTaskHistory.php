<?php
/**
 * Created by PhpStorm.
 * User: karl
 * Date: 2018/2/3
 * Time: 上午10:00
 */

namespace SingPlus\Domains\DailyTask\Models;

use SingPlus\Support\Database\Eloquent\MongodbModel;
class DailyTaskHistory extends MongodbModel
{

    const NEED_FINISH = 1;
    const NEED_AWARD = 2;
    const FINISHED = 3;

    protected $collection = 'daily_task_history';

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'user_id',
        'task_id',
        'type',       // type please to see \SingPlus\Contracts\DailyTask\Constants\DailyTask::class
        'days',     // the days for continuous finishing
        'finished_status',       // status please to see \SingPlus\Domain\DailyTask\Models\DailyTask::class
        'finished_at',   // format Y-m-d H:i:s
        'display_order',
    ];
}