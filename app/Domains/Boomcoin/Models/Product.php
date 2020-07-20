<?php
/**
 * Created by PhpStorm.
 * User: karl
 * Date: 2018/4/8
 * Time: 上午10:28
 */

namespace SingPlus\Domains\Boomcoin\Models;

use SingPlus\Support\Database\Eloquent\MongodbModel;

class Product extends MongodbModel
{
    const STATUS_NORMAL = 1;

    protected $collection = 'boomcoin_product';

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'dollars', //int 商品美元价值
        'coins',   // int 商品金币价值
        'status',  // int 商品状态
        'sold_amount', // int 商品销售次数
        'display_order'  // int
    ];

}