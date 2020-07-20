<?php

namespace SingPlus\Domains\Banners\Models;

use SingPlus\Support\Database\Eloquent\MongodbModel;

class Banner extends MongodbModel
{
  protected $collection = 'banners';

  /**
   * The attributes that are mass assignable.
   *
   * @var array
   */
  protected $fillable = [
    'country_abbr', 'image', 'type', 'attributes', 'start_time', 'stop_time',
    'display_order',
  ];

  /**
   * The attributes that should be mutated to dates.
   *
   * @var array
   */
  protected $dates = ['start_time', 'stop_time', 'created_at', 'updated_at'];
}
