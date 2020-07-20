<?php

namespace SingPlus\Domains\Musics\Models;

use SingPlus\Support\Database\Eloquent\MongodbModel;

class Artist extends MongodbModel
{
  // todo
  const STATUS_NORMAL = 1;

  // constant for gender
  const GENDER_MALE = 'M';
  const GENDER_FEMALE = 'F';

  protected $collection = 'artists';

  /**
   * The attributes that are mass assignable.
   *
   * @var array
   */
  protected $fillable = [
    'name', 'is_band', 'gender', 'avatar', 'nationality', 'status', 'abbreviation',
  ];

  /**
   * The attributes that should be casted to native types.
   *
   * @var array
   */
  protected $casts = [
    'is_band'     => 'boolean',
  ];
}
