<?php

namespace SingPlus\Domains\ClientSupports\Models;

use SingPlus\Support\Database\Eloquent\MongodbModel;

class NewActiveDeviceInfo extends MongodbModel
{
  protected $collection = 'new_active_device_infos';

  /**
   * The attributes that are mass assignable.
   *
   * @var array
   */
  protected $fillable = [
    'alias',      // push alias, unique
    'mobile',
    'abbreviation',
    'country_code',
    'latitude',
    'longitude',
    'client_version',
  ];
}
