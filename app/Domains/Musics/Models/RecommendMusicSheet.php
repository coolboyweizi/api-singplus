<?php

namespace SingPlus\Domains\Musics\Models;

use SingPlus\Support\Database\Eloquent\MongodbModel;

class RecommendMusicSheet extends MongodbModel
{
  const STATUS_NORMAL = 1;

  protected $collection = 'recommend_music_sheets';

  /**
   * The attributes that are mass assignable.
   *
   * @var array
   */
  protected $fillable = [
    'cover', 'music_ids', 'title', 'request_count', 'status',
  ];

  public function isNormal()
  {
    return $this->status == self::STATUS_NORMAL;
  }
}
