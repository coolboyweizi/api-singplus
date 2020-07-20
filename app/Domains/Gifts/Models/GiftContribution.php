<?php

/**
 * Created by PhpStorm.
 * User: karl
 * Date: 2018/1/29
 * Time: 下午4:43
 */

namespace SingPlus\Domains\Gifts\Models;

use SingPlus\Support\Database\Eloquent\MongodbModel;

class GiftContribution extends MongodbModel
{
    protected $collection = 'gift_contribution';

    protected $fillable = [
        'sender_id',   // uuid gift's sender uuid
        'receiver_id', // uuid gift's receiver uuid
        'work_id',     // uuid work id
        'coin_amount', // int coin amount of all gifts
        'gift_amount', // int gift amount of all gifts
        'gift_ids',    //array
        'gift_detail', //array   elements are \stdClass
                        //          - gift_id string
                        //          - gift_coins int
                        //          - gift_amount int
    ];
}