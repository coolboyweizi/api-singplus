<?php

namespace SingPlus\Domains\Works\Repositories;

use Illuminate\Support\Collection;
use SingPlus\Domains\Works\Models\WorkSelection;
use SingPlus\Support\Database\Eloquent\Pagination;

class WorkSelectionRepository
{
  const SELECTION_COUNTS = 50;

  /**
   * @return ?WorkSelection
   */
  public function findOneById(string $id) : ?WorkSelection
  {
    return WorkSelection::find($id);
  }

  /**
   * @return Collection       elements are WorkSelection
   */
  public function findAllForPagination(
    ?int $displayOrder,
    bool $isNext,
    int $size,
    ?string $countryAbbr
  ) : Collection {
    $query = WorkSelection::query();
    if ($countryAbbr) {
      $query = $query->where('country_abbr', $countryAbbr);
    }
    $query = Pagination::paginate($query, ['base' => $displayOrder], $isNext, $size);
    if ( ! $query) {
      return collect();
    }
    return $query->get();
  }
}
