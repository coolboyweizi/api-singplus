<?php

/**
 * Created by PhpStorm.
 * User: karl
 * Date: 2018/1/29
 * Time: 下午4:43
 */

namespace SingPlus\Domains\Gifts\Models;

use SingPlus\Support\Database\Eloquent\MongodbModel;

class GiftBag extends MongodbModel
{
    protected $collection = 'gift_bag';

    protected $fillable = [
        'sender_id', // uuid sender's user id
        'receiver_id',  //uuid receiver's user id
        'gift_info', //dict gift detail
        'expired_at', // string format Y-m-d H:i:s
        'amount',     //int the amount of gifts
    ];
}