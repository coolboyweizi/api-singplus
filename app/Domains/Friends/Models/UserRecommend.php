<?php

namespace SingPlus\Domains\Friends\Models;

use SingPlus\Support\Database\Eloquent\MongodbModel;
use SingPlus\Domains\Friends\Models\Friend;

class UserRecommend extends MongodbModel
{
  const STATUS_NORMAL = 1;

  // constant for field: is_auto_recommend
  const RECOMMEND_EDITOR = 0;
  const RECOMMEND_AUTO = 1;

  protected $collection = 'user_recommends';

  /**
   * The attributes that are mass assignable.
   *
   * @var array
   */
  protected $fillable = [
    'term',
    'orig_uuid',    // only for admin
    'user_id',
    'works_ids',    // array, elements are work id
    'country_abbr',
    'is_auto_recommend',
  ];

  //==================================
  //      Business logic
  //==================================
  public function isAutoRecommend() : bool
  {
    return $this->is_auto_recommend == self::RECOMMEND_AUTO;
  }
}
