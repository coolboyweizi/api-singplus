<?php

namespace SingPlus\Domains\Works\Repositories;

use Illuminate\Support\Collection;
use SingPlus\Domains\Works\Models\H5WorkSelection;
use SingPlus\Support\Database\Eloquent\Pagination;

class H5WorkSelectionRepository
{
  const MAX_SELECTION_COUNTS = 20;

  /**
   * @return Collection       elements are H5WorkSelection
   */
  public function findAll(string $countryAbbr) : Collection
  {
    return H5WorkSelection::where('status', H5WorkSelection::STATUS_NORMAL)
                          ->where('country_abbr', $countryAbbr)
                          ->orderBy('display_order', 'desc')
                          ->take(self::MAX_SELECTION_COUNTS)
                          ->get();
  }
}
