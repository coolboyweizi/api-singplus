<?php

namespace SingPlus\Domains\Works\Models;

use SingPlus\Support\Database\Eloquent\MongodbModel;

class RecommendWorkSheet extends MongodbModel
{
  const STATUS_DELETED = 0;
  const STATUS_NORMAL = 1;

  protected $collection = 'recommend_work_sheets';

  /**
   * The attributes that are mass assignable.
   *
   * @var array
   */
  protected $fillable = [
    'title',
    'cover',          // string
    'comments',       // string
    'works_ids',      // array
    'request_count',  // int
  ];

  public function isNormal() : bool
  {
    return $this->status == self::STATUS_NORMAL;
  }
}
