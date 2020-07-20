<?php

namespace SingPlus\Domains\Users\Models;

use SingPlus\Support\Database\Eloquent\MongodbModel;

class TUDCUser extends MongodbModel
{
  const STATUS_NORMAL = 1;

  protected $collection = 'tudc_users';

  /**
   * The attributes that are mass assignable.
   *
   * @var array
   */
  protected $fillable = [
    'user_id',
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
