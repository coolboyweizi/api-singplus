<?php

/**
 * Created by PhpStorm.
 * User: karl
 * Date: 2018/1/29
 * Time: 下午4:41
 */
namespace SingPlus\Domains\Gifts\Models;

use SingPlus\Support\Database\Eloquent\MongodbModel;

class Gift extends MongodbModel
{

    const STATUS_NORMAL = 1;
    const STATUS_DELETE = 0;

    protected $collection = 'gifts';

    protected $fillable = [
        'type',        //string gift type
        'name',        // string gift name
        'icon',        //dict { "icon_small": "", "icon_big": "" }
        'coins',       //int gift value
        'sold_amount',  //int the amount of being sold
        'sold_coin_amount',  //int the amount of coins
        'status',       // int , 0 delete 1 normal
        'popularity',   // int
        'animation', // dict { "url":"", "type": 1 渐现 2 由下往上滑动, "duration": 1}
        'display_order', // int
    ];

    /*********************************
     *        Accessor 
     ********************************/
    public function getNameAttribute($value)
    {
        return $this->translateField($value);
    }
}
