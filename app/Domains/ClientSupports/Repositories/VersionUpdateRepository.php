<?php

namespace SingPlus\Domains\ClientSupports\Repositories;

use SingPlus\Domains\ClientSupports\Models\VersionUpdate;

class VersionUpdateRepository
{
  public function findOne() : ?VersionUpdate
  {
    return VersionUpdate::first();
  }
}
