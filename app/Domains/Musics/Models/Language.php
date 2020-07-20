<?php

namespace SingPlus\Domains\Musics\Models;

use SingPlus\Support\Database\Eloquent\MongodbModel;

class Language extends MongodbModel
{
  // todo
  const STATUS_NORMAL = 1;

  protected $collection = 'music_languages';

  /**
   * The attributes that are mass assignable.
   *
   * @var array
   */
  protected $fillable = [
    'cover_image', 'name', 'total_number', 'total_song_number',
  ];

  /*********************************
   *        Accessor 
   ********************************/
  public function getNameAttribute($value)
  {
    return $this->translateField($value);
  }
}
