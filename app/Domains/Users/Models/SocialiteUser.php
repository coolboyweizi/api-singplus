<?php

namespace SingPlus\Domains\Users\Models;

use SingPlus\Support\Database\Eloquent\MongodbModel;

class SocialiteUser extends MongodbModel
{
  const STATUS_NORMAL = 1;

  protected $collection = 'socialite_users';

  /**
   * The attributes that are mass assignable.
   *
   * @var array
   */
  protected $fillable = [
    'user_id',
    'socialite_user_id',
    'provider',
    'union_token',        // optional, 老数据没有
    'channels',           // format as: {
                          //              "singplus": {
                          //                  "openid": "xxxxxxx",
                          //                  "token": "xxxxxxxx"
                          //              },
                          //              "boomsing": {
                          //                  "openid": "xxxxxx",
                          //                  "token": "xxxxxxx"
                          //              }
                          //            }
  ];
}
