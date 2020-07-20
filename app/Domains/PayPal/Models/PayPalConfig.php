<?php
/**
 * Created by PhpStorm.
 * User: zhangyujie
 * Date: 2018/9/4
 * Time: 下午5:10
 */

namespace SingPlus\Domains\PayPal\Models;


use SingPlus\Support\Database\Eloquent\MongodbModel;

class PayPalConfig extends MongodbModel
{
    protected $collection = 'paypal_config';
    protected $fillable = [
        'app_name',
        'is_open',
        'url',
    ];
}