<?php

/**
 * Created by PhpStorm.
 * User: karl
 * Date: 2018/1/29
 * Time: 下午4:42
 */

namespace SingPlus\Domains\Gifts\Models;

use SingPlus\Support\Database\Eloquent\MongodbModel;

class GiftHistory extends MongodbModel
{

    const STATUS_DELETED = 0;
    const STATUS_NORMAL = 1;

    protected $collection = 'gift_history';
    protected $fillable = [
        'gift_info',    //dict gift detail info
 /**                        -id string gift id
  *                         -type string gift type
  *                         -name string gift name
  *                         -icon dict
  *                             -icon_small small icon
  *                             -icon_big   big icon
  *                         -coins  int gift coins
  *                         -sold_amount  int  gift sold_amount
  *                         -sold_coin_amount  int gift coin amount
  *                         -status int
  *                         -popularity int
  */
        'sender_id',    //uuid sender's user id
        'receiver_id',  //uuid receiver's user id
        'work_id',      // uuid work's id
        'amount',       // the amount of gift
        'display_order', //
        'status'        // int  0 deleted 1 normal
    ];

    /*********************************
    *        Accessor 
    ********************************/
    public function getGiftInfoAttribute($value)
    {
        if ( ! is_array($value)) {
            return $value;
        }

        $value['name'] = $this->translateField($value['name']);
        return $value;
    }
}
