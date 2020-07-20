<?php

namespace SingPlus\Domains\Works\Models;

use SingPlus\Support\Database\Eloquent\MongodbModel;

class WorkSelection extends MongodbModel
{
  // todo
  const STATUS_NORMAL = 1;

  protected $collection = 'work_selections';

  /**
   * The attributes that are mass assignable.
   *
   * @var array
   */
  protected $fillable = [
    'work_id', 'display_order', 'country_abbr', 'status'
  ];
}
