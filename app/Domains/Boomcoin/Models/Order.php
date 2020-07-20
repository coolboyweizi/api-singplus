<?php

namespace SingPlus\Domains\Boomcoin\Models;
/**
 * Created by PhpStorm.
 * User: karl
 * Date: 2018/4/4
 * Time: 下午1:56
 */
use SingPlus\Support\Database\Eloquent\MongodbModel;

class Order extends MongodbModel
{

    const STATUS_PENDING = 1;
    const STATUS_SUCCESS = 2;
    const STATUS_FAILURE = 3;

    protected $collection = 'boomcoin_order';

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'user_id',      // string   user's id
        'product_id',   // string   the product id
        'amount',       // int      the transaction boomcoin amount
        'msisnd',       // string   the account number for boomcoin ,using the mobile phone 254712686240
        'country_code', // string   the account country code for boomcoin msisnd account
        'balance',      // int      the boomcoin balance after exchange successfully
        'transaction_id',   //string    the transaction id of boomcoin
        'status',       // int      the order status  see STATUS_PENDING STATUS_SUCCESS STATUS_FAILURE,
        'display_order', // int
        'source', //    string the api source for this order , singplus or boomsing
    ];

}