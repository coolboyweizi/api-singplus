<?php
/**
 * Created by PhpStorm.
 * User: karl
 * Date: 2018/3/1
 * Time: 上午9:45
 */

namespace SingPlus\Domains\Hierarchy\Models;

use SingPlus\Domains\Users\Models\UserProfile;
use SingPlus\Support\Database\Eloquent\MongodbModel;

class WealthRank extends MongodbModel
{

    const TYPE_DAILY = 'daily';
    const TYPE_TOTAL = 'total';

    protected $collection = 'coin_consume_rank';

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'user_id',  // string   uuid of user
        'type',  // string   type of rank
        'display_order' , // int    display order
    ];

}