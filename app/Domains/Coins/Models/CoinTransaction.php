<?php

namespace SingPlus\Domains\Coins\Models;

use SingPlus\Support\Database\Eloquent\MongodbModel;
use SingPlus\Contracts\Orders\Constants\ChargeOrder as ChargeOrderConst;

class CoinTransaction extends MongodbModel
{
  protected $collection = 'coin_transactions';

  /**
   * The attributes that are mass assignable.
   *
   * @var array
   */
  protected $fillable = [
    'user_id',
    'operator',     // operator user id or admin user id
    'amount',       // coin amount.   positive number stands for deposit
                    //                negative number stands for withdraw
    'source',       // please to see SingPlus/Contracts/Coins/Constants/Trans::SOURCE_xxxxx
    'details',      // array. trans details info
    'display_order',
  ];
}
