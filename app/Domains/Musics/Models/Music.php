<?php

namespace SingPlus\Domains\Musics\Models;

use SingPlus\Support\Database\Eloquent\MongodbModel;
use SingPlus\Domains\Musics\Models\Artist;

class Music extends MongodbModel
{
  // todo
  const STATUS_NORMAL = 1;

  protected $collection = 'music_libraries';

  /**
   * The attributes that are mass assignable.
   *
   * @var array
   */
  protected $fillable = [
    'name', 'cover_images', 'artists', 'album', 'languages', 'styles',
    'request_count', 'lyrics', 'resource', 'status',
    'work_rank_expired_at',   // 作品排行榜下次更新时间
    'regional_recommend_status',    // dict (记录对地区推荐状态，admin维护并使用)
  ];

  public function isNormal() : bool
  {
    return $this->status == self::STATUS_NORMAL;
  }
}
