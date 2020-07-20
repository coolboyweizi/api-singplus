<?php

namespace SingPlus\Domains\Works\Models;

use SingPlus\Support\Database\Eloquent\MongodbModel;

class H5WorkSelection extends MongodbModel
{
  const STATUS_NORMAL = 1;

  protected $collection = 'h5_work_selections';

  /**
   * The attributes that are mass assignable.
   *
   * @var array
   */
  protected $fillable = [
    'work_id', 'country_abbr', 'display_order', 'status'
  ];
}
