<?php
/**
 * Created by PhpStorm.
 * User: karl
 * Date: 2018/2/3
 * Time: 下午1:48
 */

namespace SingPlus\Domains\DailyTask\Models;

use SingPlus\Support\Database\Eloquent\MongodbModel;

class Task extends MongodbModel
{
    const STATUS_DELETE = 0;
    const STATUS_NORMAL = 1;

    const CATEGORY_DAILY_TASK = 'daily_task';

    protected $collection = 'user_task';

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'category',   // string     category of task
        'type',       // string     type of task for one category
        'value',     // int the     base coins after finished the task
        'title',     //  string     the title of a task
        'desc',       // string     the desc of a task
        'value_step',   // int      the step coin add for base value
        'maximum_value',   // int   the maximum value for a task after finished
        'status',   // int  the status of a task  0 deleted 1 normal
    ];

    /*********************************
    *        Accessor 
    ********************************/
    public function getTitleAttribute($value)
    {
        return $this->translateField($value);
    }

    public function getDescAttribute($value)
    {
        return $this->translateField($value);
    }
}
