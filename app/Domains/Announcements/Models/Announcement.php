<?php

namespace SingPlus\Domains\Announcements\Models;

use SingPlus\Support\Database\Eloquent\MongodbModel;

class Announcement extends MongodbModel
{
  // constant for status
  const STATUS_NORMAL = 1;

  protected $collection = 'announcements';

  /**
   * The attributes that are mass assignable.
   *
   * type's value please to see \SingPlus\Contracts\Announcements\Constants\Announcement
   *
   * @var array
   */
  protected $fillable = [
    'country_abbr', 'title', 'cover', 'summary', 'type',
    'attributes', 'status', 'display_order',
  ];
}
