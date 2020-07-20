<?php

namespace SingPlus\Domains\Works\Models;

use SingPlus\Support\Database\Eloquent\MongodbModel;
use Jenssegers\Mongodb\Eloquent\SoftDeletes;
use SingPlus\Domains\Works\Models\Work;

class WorkFavourite extends MongodbModel
{
  use SoftDeletes;

  protected $collection = 'work_favourites';

  /**
   * The attributes that are mass assignable.
   *
   * @var array
   */
  protected $fillable = [
    'user_id',
    'work_id',
  ];

  /**
   * This method must be added with SoftDeletes Trait
   */
  protected function runSoftDelete()
  {
    parent::runSoftDelete();
  }

  //======================================
  //        Relations
  //======================================
}
