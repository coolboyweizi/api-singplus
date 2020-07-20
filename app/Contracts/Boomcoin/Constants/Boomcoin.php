<?php

/**
 * Created by PhpStorm.
 * User: karl
 * Date: 2018/4/8
 * Time: 上午9:58
 */
namespace SingPlus\Contracts\Boomcoin\Constants;

use Cache;

class Boomcoin
{


    public static function getExchangeRate(string $abbr){
        $exchangeRateArr = config('boomcoin.exchange');
        $exchangeRate = array_get($exchangeRateArr, $abbr, config('boomcoin.defaultExchange'));
        return $exchangeRate;
    }

}