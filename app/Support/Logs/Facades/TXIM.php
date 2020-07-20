<?php
/**
 * Created by PhpStorm.
 * User: karl
 * Date: 2018/1/10
 * Time: 下午2:12
 */

namespace SingPlus\Support\Logs\Facades;


use Illuminate\Support\Facades\Facade;

class TXIM extends Facade
{
    /**
     * Get the registered name of the component
     *
     * @return string
     */
    public static function getFacadeAccessor()
    {
        return 'log.txim';
    }
}