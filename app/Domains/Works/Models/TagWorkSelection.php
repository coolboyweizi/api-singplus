<?php
/**
 * Created by PhpStorm.
 * User: karl
 * Date: 2018/3/26
 * Time: 下午4:42
 */

namespace SingPlus\Domains\Works\Models;


use SingPlus\Support\Database\Eloquent\MongodbModel;

class TagWorkSelection extends MongodbModel
{
    const STATUS_NORMAL = 1;

    protected $collection = 'tag_work_selections';

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'work_id', 'work_tag', 'display_order', 'status'
    ];
}