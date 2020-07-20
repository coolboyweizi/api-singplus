<?php

namespace SingPlus\Domains\Musics\Models;

use SingPlus\Support\Database\Eloquent\MongodbModel;
use SingPlus\Domains\Musics\Models\Music;

class ArtistHot extends MongodbModel
{
  protected $collection = 'artist_hots';

  /**
   * The attributes that are mass assignable.
   *
   * @var array
   */
  protected $fillable = ['country_abbr', 'artist_id', 'display_order'];
}
