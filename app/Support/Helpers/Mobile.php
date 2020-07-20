<?php

namespace SingPlus\Support\Helpers;

class Mobile
{
  /**
   * generate completed mobile with country code
   *
   * @param string $localMobile   local mobile which has not country code part
   * @param string $countryCode   country code
   */
  public static function genMobileWithCountryCode(string $localMobile, string $countryCode) : string
  {
    return "$countryCode$localMobile";
  }

  /**
   * generate E.164 format phone number: CC + NDC + SN
   */
  public static function genE164NumberForSending(string $localMobile, string $countryCode) : string
  {
    return $countryCode . ltrim($localMobile, "0");
  }

  /**
   * generate local mobile which has not country code part
   *
   * @param string $mobile        mobile which has country code part
   * @param string $countryCode   country code
   */
  public static function genLocalMobile(?string $mobile, ?string $countryCode) : ?string
  {
      if (empty($mobile) || empty($countryCode)) {
        return $mobile;
      }

      if (strpos($mobile, $countryCode) !== 0) {
        return $mobile;
      }

      return subStr($mobile, strlen($countryCode));
  }
}
