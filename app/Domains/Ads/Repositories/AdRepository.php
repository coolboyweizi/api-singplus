<?php

namespace SingPlus\Domains\Ads\Repositories;

use Carbon\Carbon;
use Illuminate\Support\Collection;
use SingPlus\Domains\Ads\Models\Advertisement;

class AdRepository
{
  /**
   * Get all valid ads
   *
   * @param ?string $countryAbbr
   *
   * @return Collection     elements are Advertisement
   */
  public function getAll(?string $countryAbbr) : Collection
  {
    $now = Carbon::now()->format('Y-m-d H:i:s');
    $query = Advertisement::where('stop_time', '>=', $now)
                        ->where('status', Advertisement::STATUS_NORMAL);
    if ($countryAbbr) {
      $query = $query->where('country_abbr', $countryAbbr);
    }

    return $query->get();
  }
}
