<?php

namespace SingPlus\Domains\Users\Models;

use SingPlus\Support\Database\Eloquent\MongodbModel;

class UserImage extends MongodbModel
{
  const AVATAR_NO = 0;
  const AVATAR_YES = 1;

  protected $collection = 'user_images';

  /**
   * The attributes that are mass assignable.
   *
   * @var array
   */
  protected $fillable = [
    'user_id', 'uri', 'is_avatar', 'display_order'
  ];
}
