<?php

namespace SingPlus\Domains\Helps\Models;

use SingPlus\Support\Database\Eloquent\MongodbModel;

class Feedback extends MongodbModel
{
  const TYPE_GLOBAL             = 1;
  const TYPE_MUSIC_SEARCH       = 2;    // 用户提交的搜歌反馈
  const TYPE_MUSIC_SEARCH_AUTO  = 3;    // 系统自动提交的搜歌结果为空反馈
  const TYPE_ACCOMPANIMENT      = 4;    // 伴奏反馈

  const STATUS_WAIT     = 1;
  const STATUS_HANDLED  = 2;
  const STATUS_IGNORE   = 3;

  const ACCOMPANIMENT_OTHER = 0;
  const ACCOMPANIMENT_NOT_MATCH = 1;
  const ACCOMPANIMENT_LOW_QUALITY = 2;

  protected $collection = 'feedbacks';

  /**
   * The attributes that are mass assignable.
   *
   * @var array
   */
  protected $fillable = [
    'country_abbr', 'user_id', 'message', 'type', 'status'
  ];
}
