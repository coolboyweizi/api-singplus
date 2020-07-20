<?php

namespace SingPlus\Contracts\TUDC\Services;

interface Service
{
  /**
   * Verify ticket
   *
   * @param string $appChannel
   * @param string $ticket
   *
   * @return ?\stdClass       properties as below:
   *                          - openid string
   *                          - token string
   */
  public function verifyTicket(string $appChannel, string $ticket) : ?\stdClass;
}
