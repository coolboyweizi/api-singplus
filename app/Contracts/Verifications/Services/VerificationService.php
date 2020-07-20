<?php

namespace SingPlus\Contracts\Verifications\Services;

interface VerificationService
{
  /**
   * Verify whether code correct or not
   *
   * @param string $mobile    mobile with country code
   * @param string $code
   *
   * @throw \SingPlus\Exceptions\Verifications\VerificationException
   */
  public function verify(string $mobile, string $code);

  /**
   * Send verification code
   *
   * @param string $mobile      mobile with country code part
   * @param string $sendMobile  E164 format mobile
   *
   * @return string           properties as below:
   *                          - code string     verification code
   *                          - interval  int   after this seconds, send allowd
   */
  public function sendCode(string $mobile, string $sendMobile) : \stdClass;
}
