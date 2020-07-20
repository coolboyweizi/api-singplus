<?php
/**
 * Created by PhpStorm.
 * User: karl
 * Date: 2018/4/8
 * Time: 下午2:04
 */

namespace SingPlus\Contracts\Boomcoin\Services;


interface GatewayService
{

    /**
     * Send a json request to Boomcoin
     *
     * @param string $method
     * @param string $url
     * @param array $querys
     * @param array $postData
     * @return \stdClass
     */
    public function requestJson(
        string $method,
        string $url,
        array $querys = [],
        array $postData = []
    ) : \stdClass;

}