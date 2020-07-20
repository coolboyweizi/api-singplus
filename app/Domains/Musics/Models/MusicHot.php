<?php

namespace SingPlus\Domains\Musics\Models;

use SingPlus\Support\Database\Eloquent\MongodbModel;
use SingPlus\Domains\Musics\Models\Music;

class MusicHot extends MongodbModel
{
  protected $collection = 'music_hots';

  /**
   * The attributes that are mass assignable.
   *
   * @var array
   */
  protected $fillable = ['country_abbr', 'music_id', 'display_order'];
}
