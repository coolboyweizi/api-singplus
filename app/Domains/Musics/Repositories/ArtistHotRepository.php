<?php

namespace SingPlus\Domains\Musics\Repositories;

use Illuminate\Support\Collection;
use SingPlus\Domains\Musics\Models\ArtistHot;

class ArtistHotRepository
{
  public function findAll(?string $countryAbbr) : Collection
  {
    $query = ArtistHot::query();
    if ($countryAbbr) {
      $query = $query->where('country_abbr', $countryAbbr);
    }
    return $query->orderBy('display_order', 'desc')->get();
  }


}
