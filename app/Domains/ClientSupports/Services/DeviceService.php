<?php

namespace SingPlus\Domains\ClientSupports\Services;

use SingPlus\Contracts\ClientSupports\Services\DeviceService as DeviceServiceContract;
use SingPlus\Domains\ClientSupports\Repositories\NewActiveDeviceInfoRepository;
use SingPlus\Domains\ClientSupports\Models\NewActiveDeviceInfo;

class DeviceService implements DeviceServiceContract
{
  /**
   * @var NewActiveDeviceInfoRepository
   */
  private $newActiveDeviceInfoRepo;

  public function __construct(
    NewActiveDeviceInfoRepository $newActiveDeviceInfoRepo
  ) {
    $this->newActiveDeviceInfoRepo = $newActiveDeviceInfoRepo;
  }

  /**
   * {@inheritdoc}
   */
  public function saveNewActiveDeviceInfos(
    string $alias,
    ?string $mobile,
    ?string $abbreviation,
    ?string $countryCode,
    ?string $latitude,
    ?string $longitude,
    ?string $clientVerstion
  ) {
    $info = $this->newActiveDeviceInfoRepo->findOneByAlias($alias);

    if ( ! $info) {
      $info = new NewActiveDeviceInfo([
        'alias' => $alias,
      ]);
    }

    $info->mobile = $mobile;
    $info->abbreviation = $abbreviation;
    $info->country_code = $countryCode;
    $info->latitude = $latitude;
    $info->longitude = $longitude;
    $info->client_version = $clientVerstion;

    return $info->save();
  }

  /**
   * {@inheritdoc}
   */
  public function removeAlias(string $appChannel, string $alias) : ?bool 
  {
    // todo
    // 临时方案，只处理singplus渠道
    if ($appChannel != config('tudc.defaultChannel')) {
      return true;
    }
    $info = $this->newActiveDeviceInfoRepo->findOneByAlias($alias);
    if ( ! $info) {
      return null;
    }

    return $info->delete();
  }
}
