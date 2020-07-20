<?php

namespace SingPlus\Domains\Musics\Models;

use SingPlus\Support\Database\Eloquent\MongodbModel;

class Style extends MongodbModel
{
  const STATUS_NORMAL = 1;

  // constant for field: show
  const NEED_SHOW_NO = 0;
  const NEED_SHOW_YES = 1;

  protected $collection = 'music_styles';

  /**
   * The attributes that are mass assignable.
   *
   * @var array
   */
  protected $fillable = [
    'cover_image', 'name', 'total_number', 'total_song_number', 'need_show'
  ];

  /*********************************
   *        Accessor 
   ********************************/
  public function getNameAttribute($value)
  {
    return $this->translateField($value);
  }

  public function needShow() : bool
  {
    return $this->need_show == self::NEED_SHOW_YES;
  }
}
