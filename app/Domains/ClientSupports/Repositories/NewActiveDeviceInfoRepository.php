<?php

namespace SingPlus\Domains\ClientSupports\Repositories;

use SingPlus\Domains\ClientSupports\Models\NewActiveDeviceInfo;

class NewActiveDeviceInfoRepository
{
  /**
   * @param string $alias
   *
   * @return ?NewActiveDeviceInfo
   */
  public function findOneByAlias(string $alias) : ?NewActiveDeviceInfo
  {
    return NewActiveDeviceInfo::where('alias', $alias)->first();
  }
}
