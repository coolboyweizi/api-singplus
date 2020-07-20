<?php

namespace SingPlus\Contracts\Users\Services\Auth;

interface RegisterService
{
  /**
   * user register from mobile
   *
   * @param string $countryCode   country code
   * @param string $mobile        mobile with country code, eg: 8613800138000
   * @param string $password
   *
   * @return string               user id
   */
  public function register(string $countryCode, string $mobile, string $password) : string;

  /**
   * register from socialite
   *
   * @param string $appChannel          such as singplus | boomsing
   * @param string $socialiteUserId     socialite user id
   * @param string $userToken           socialite user access token
   * @param string $provider            socialite provider, eg: facebook
   * @param ?string $unionToken         同一个provider下，同一个用户在多个app中的openid不同,
   *                                    通过unionToken将这些openid关联起来，例如在facebook下
   *                                    unionToken对应token_for_business
   *
   * @return \stdClass        properties as below:
   *                          - userId string
   */
  public function registerFromSocialite(
    string $appChannel,
    string $socialiteUserId,
    string $userToken,
    string $provider,
    ?string $unionToken = null
  ) : \stdClass;

  /**
   * register from tudc
   *
   * @param string $appChannel      such as singplus | boomsing
   * @param string $tudcOpenid
   * @param string $tudcToken
   * @param string $countryCode     country code
   * @param string $mobile          mobile with country code, eg: 8613800138000
   * @param string $password
   *
   * @return \stdClass              properties as below:
   *                                - userId string
   */
  public function registerFromTUDC(
    string $appChannel,
    string $tudcOpenid,
    string $tudcToken,
    string $countryCode,
    string $mobile,
    string $password
  ) : \stdClass;

  /**
   * register synthetic user
   *
   * @param string $countryCode   country code
   * @param string $mobile        mobile with country code, eg: 8613800138000
   * @param string $password
   *
   * @return string               user id
   */
  public function registerSyntheticUser(string $countryCode, string $mobile, string $password) : string;
}
