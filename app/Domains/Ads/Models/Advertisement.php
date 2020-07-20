<?php

namespace SingPlus\Domains\Ads\Models;

use SingPlus\Support\Database\Eloquent\MongodbModel;

class Advertisement extends MongodbModel
{
  const STATUS_NORMAL = 1;

  const NEED_LOGIN_NO = 0;
  const NEED_LOGIN_YES = 1;

  protected $collection = 'advertisements';

  /**
   * The attributes that are mass assignable.
   *
   * @var array
   */
  protected $fillable = [
    'country_abbr',
    'title',
    'image',
    'spec_images',  // dict: key is specification, value is storage_key
    'type',         // please to see \SingPlus\Contracts\Ads\Constants\Ad::TYPE_XXX
    'need_login',
    'start_time',   // string, Y-m-d H:i:s
    'stop_time',    // string, Y-m-d H:i:s
    'link',         // string: protocol://domain/path
    'status',
  ];

  /**
   * The attributes that should be mutated to dates.
   *
   * @var array
   */
  protected $dates = ['start_time', 'stop_time', 'created_at', 'updated_at'];

  public function needLogin() : bool
  {
    return $this->need_login == self::NEED_LOGIN_YES ? true : false;
  }
}
