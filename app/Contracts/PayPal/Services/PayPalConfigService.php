<?php
/**
 * Created by PhpStorm.
 * User: zhangyujie
 * Date: 2018/9/4
 * Time: 下午5:42
 */

namespace SingPlus\Contracts\PayPal\Services;


interface PayPalConfigService
{
    public function getPayPalStatus(string $appName) : \stdClass;
}