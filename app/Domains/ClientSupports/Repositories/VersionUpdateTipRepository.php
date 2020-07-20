<?php

namespace SingPlus\Domains\ClientSupports\Repositories;

use SingPlus\Domains\ClientSupports\Models\VersionUpdateTip;

class VersionUpdateTipRepository
{
  public function findOneByVersion(string $version) : ?VersionUpdateTip
  {
    return VersionUpdateTip::where('version', $version)->first();
  }
}
