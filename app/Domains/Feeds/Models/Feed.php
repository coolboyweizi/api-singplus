<?php

namespace SingPlus\Domains\Feeds\Models;

use SingPlus\Support\Database\Eloquent\MongodbModel;

class Feed extends MongodbModel
{
  const STATUS_NORMAL = 1;

  const READ_NO = 0;
  const READ_YES = 1;

  protected $collection = 'feeds';

  /**
   * The attributes that are mass assignable.
   *
   * @var array
   */
  protected $fillable = [
    'user_id',
    'operator_user_id',
    'type',       // type please to see \SingPlus\Contracts\Feeds\Constants\Feed::class
    'detail',     // object
    'status',
    'is_read',
    'display_order',
  ];

  protected $casts = [
    'is_read' => 'boolean',
  ];
}
