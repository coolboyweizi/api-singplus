<?php

namespace SingPlus\Domains\Orders\Models;

use SingPlus\Support\Database\Eloquent\MongodbModel;

class CoinSku extends MongodbModel
{
  // constant for status
  const STATUS_INIT = 0;
  const STATUS_SHELVE = 1;
  const STATUS_UNSHELVE = -1;

  protected $collection = 'coin_skus';

  /**
   * The attributes that are mass assignable.
   *
   * @var array
   */
  protected $fillable = [
    'sku_id',
    'price',
    'coins',
    'title',
    'status',
  ];

  /*********************************
   *        Accessor 
   ********************************/
  public function getTitleAttribute($value)
  {
    return $this->translateField($value);
  }
}
