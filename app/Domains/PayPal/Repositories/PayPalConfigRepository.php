<?php
/**
 * Created by PhpStorm.
 * User: zhangyujie
 * Date: 2018/9/4
 * Time: 下午5:26
 */

namespace SingPlus\Domains\PayPal\Repositories;


use SingPlus\Domains\PayPal\Models\PayPalConfig;

class PayPalConfigRepository
{
    public function findOneAppName(string $appName) : ?PayPalConfig
    {
        return PayPalConfig::where('app_name', $appName)->first();
    }

}