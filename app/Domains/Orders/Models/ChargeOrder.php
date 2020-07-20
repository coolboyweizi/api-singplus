<?php

namespace SingPlus\Domains\Orders\Models;

use SingPlus\Support\Database\Eloquent\MongodbModel;
use SingPlus\Contracts\Orders\Constants\ChargeOrder as ChargeOrderConst;

class ChargeOrder extends MongodbModel
{
  protected $collection = 'charge_orders';

  /**
   * The attributes that are mass assignable.
   *
   * @var array
   */
  protected $fillable = [
    'user_id',
    'pay_order_id',     // payment channel order id
    'amount',
    'sku_count',
    'pay_order_details',
    'status',               // please to see ChargeOrderConst
    'status_histories',     // array, record status info after each status changing
    'sku',                  // sku info, dict
                                // - sku_id
                                // - price
                                // - coins
                                // - title
  ];

  /*********************************
   *        Accessor 
   ********************************/
  public function getSkuAttribute($value)
  {
    if ( ! is_array($value)) {
        return $value;
    }

    $value['title'] = $this->translateField($value['title']);
    return $value;
  }

  public function isPending() : bool
  {
    return $this->status == ChargeOrderConst::STATUS_WAITING;
  }
}
