<?php

namespace SingPlus\Domains\Banners\Repositories;

use Carbon\Carbon;
use Illuminate\Support\Collection;
use SingPlus\Domains\Banners\Models\Banner;

class BannerRepository
{
  /**
   * @param bool $onlyEffective
   *
   * @return Collection
   */
  public function findAll(?string $countryAbbr) : Collection
  {
    $now = Carbon::now()->format('Y-m-d H:i:s');
    $query = Banner::where('start_time', '<=', $now)
                   ->where('stop_time', '>=', $now);
    if ($countryAbbr) {
      $query = $query->where('country_abbr', $countryAbbr);
    }
    
    return $query->orderBy('display_order', 'desc')
                 ->get();
  }
}
