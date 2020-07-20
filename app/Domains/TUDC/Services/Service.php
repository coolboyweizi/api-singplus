<?php

namespace SingPlus\Domains\TUDC\Services;

use SingPlus\Contracts\TUDC\Services\Service as ServiceContract;
use SingPlus\Domains\TUDC\Services\GatewayService;

class Service implements ServiceContract
{
  /**
   * @var GatewayServiceContract
   */
  private $gateway;

  /**
   * {@inheritdoc}
   */
  public function verifyTicket(string $appChannel, string $ticket) : ?\stdClass
  {
    $url = rtrim(config('tudc.domain.service'), '/') . '/service/ticket/v1/verify/st';
    $res = GatewayService::channel($appChannel)
                         ->requestJson('POST', $url, [], ['st' => $ticket]);

    return $res->code == 0 ? (object) [
                            'openid'  => $res->openid,
                            'token'   => $res->token,
                          ] : null;
  }
}
