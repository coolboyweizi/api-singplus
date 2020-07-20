<?php

namespace SingPlus\Domains\Works\Models;

use SingPlus\Support\Database\Eloquent\MongodbModel;

class WorkRank extends MongodbModel
{
  const STATUS_NORMAL = 1;

  const GLOBAL_NO = 0;
  const GLOBAL_YES = 1;

  const ROOKIE_NO = 0;
  const ROOKIE_YES = 1;

  protected $collection = 'work_chart';

  /**
   * The attributes that are mass assignable.
   *
   * @var array
   */
  protected $fillable = [
    'work_id',
    'country_abbr',
    'is_global',          // value please to see self::GLOBAL_XX
    'is_new_comer',       // value please to see self::ROOKIE_XX
    'rank',               // int
  ];
}
