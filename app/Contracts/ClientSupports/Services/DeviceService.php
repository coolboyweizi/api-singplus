<?php

namespace SingPlus\Contracts\ClientSupports\Services;

interface DeviceService
{
  /**
   * Save new device info
   *
   * @param string $alias           device push alias
   * @param ?string $mobile
   * @param ?string $abbreviation   country short name
   * @param ?string $countryCode
   * @param ?string $latitude
   * @param ?string $longitude
   * @param ?string $clientVerstion
   */
  public function saveNewActiveDeviceInfos(
    string $alias,
    ?string $mobile,
    ?string $abbreviation,
    ?string $countryCode,
    ?string $latitude,
    ?string $longitude,
    ?string $clientVerstion
  );

  /**
   * Remove by alias
   *
   * @param string $appChannel
   * @param string $alias
   *
   * @return ?bool          null stand alias not exists 
   */
  public function removeAlias(string $appChannel, string $alias) : ?bool;
}
