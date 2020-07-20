<?php

namespace SingPlus\Domains\Admins\Models;

use SingPlus\Support\Database\Eloquent\MongodbModel;

class AdminTask extends MongodbModel
{
  protected $collection = 'admin_tasks';

  /**
   * The attributes that are mass assignable.
   *
   * @var array
   */
  protected $fillable = [
    'task_id',
    'data',
  ];
}
