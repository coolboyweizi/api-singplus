<?php

namespace SingPlus\Domains\Verifications\Models;

use Jenssegers\Mongodb\Eloquent\SoftDeletes;
use SingPlus\Support\Database\Eloquent\MongodbModel;

class Verification extends MongodbModel
{
  use SoftDeletes;

  protected $collection = 'verifications';

  /**
   * The attributes that are mass assignable.
   *
   * @var array
   */
  protected $fillable = [
    'mobile', 'code', 'expired_at'
  ];

  /**
   * The attributes that should be mutated to dates.
   *
   * @var array
   */
  protected $dates = ['expired_at', 'created_at', 'updated_at', 'deleted_at'];
}
