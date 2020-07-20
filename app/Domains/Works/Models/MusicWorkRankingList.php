<?php

namespace SingPlus\Domains\Works\Models;

use SingPlus\Support\Database\Eloquent\MongodbModel;

class MusicWorkRankingList extends MongodbModel 
{
  // constant for status
  const STATUS_NORMAL = 1;

  // constant for field: type
  const TYPE_SOLO = 1;          // 独唱排行
  const TYPE_CHORUS = 2;        // 合唱排行

  protected $collection = 'music_work_ranking_lists';

  /**
   * The attributes that are mass assignable.
   *
   * @var array
   */
  protected $fillable = [
    'music_id',
    'work_id',
    'type',
    'status',
  ];
}
