<?php

namespace SingPlus\Domains\Nationalities\Models;

use SingPlus\Support\Database\Eloquent\MongodbModel;

class Nationality extends MongodbModel
{
  protected $collection = 'nationalities';

  /**
   * The attributes that are mass assignable.
   *
   * @var array
   */
  protected $fillable = [
    'code',
    'name',
    'flag',
    'flag_uri',     // flat storage key
    'status',
  ];
}
