<?php

namespace SingPlus\Contracts\TUDC\Services;

interface GatewayService
{
  /**
   * Send a json request to TUDC
   *
   * @param string $method
   * @param string $url
   * @param array $querys
   * @param array $postData
   * @param ?string $userToken
   *
   * @return \stdClass
   */
  public function requestJson(
    string $method,
    string $url,
    array $querys = [],
    array $postData = [],
    ?string $userToken = null
  ) : \stdClass;
}
