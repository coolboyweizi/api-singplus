<?php

namespace SingPlus\Domains\Friends\Models;

use SingPlus\Support\Database\Eloquent\MongodbModel;
use SingPlus\Domains\Friends\Models\Friend;

class UserFollowing extends MongodbModel
{
  protected $collection = 'user_followings';

  /**
   * The attributes that are mass assignable.
   *
   * @var array
   */
  protected $fillable = [
    'user_id',
    'followings',             // array, elements are followed user id
    'following_details',      // array, elements are \stdClass
                              //          - user_id string
                              //          - follow_at timestamp
    'display_order',
  ];

  static function buildFollowItem(string $userId) : \stdClass
  {
    return (object) [
      'user_id'   => $userId,
      'follow_at' => time(),
    ];
  }
}
