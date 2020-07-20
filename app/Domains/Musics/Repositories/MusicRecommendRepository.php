<?php

namespace SingPlus\Domains\Musics\Repositories;

use Illuminate\Support\Collection;
use SingPlus\Domains\Musics\Models\MusicRecommend;
use SingPlus\Domains\Musics\Models\Music;

class MusicRecommendRepository
{
  /**
   * Get Recommends
   */
  public function findAll(?string $countryAbbr) : Collection
  {
    $query = MusicRecommend::query();
    if ($countryAbbr) {
      $query = $query->where('country_abbr', $countryAbbr);
    }
    return $query->orderBy('display_order', 'desc')->get();
  }
}
