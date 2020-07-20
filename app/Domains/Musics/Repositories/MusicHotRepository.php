<?php

namespace SingPlus\Domains\Musics\Repositories;

use Illuminate\Support\Collection;
use SingPlus\Support\Database\Eloquent\Pagination;
use SingPlus\Domains\Musics\Models\MusicHot;

class MusicHotRepository
{
  /**
   * @param string $musicId
   *
   * @return ?MusicHot
   */
  public function findOneById(string $hotId) : ?MusicHot
  {
    return MusicHot::find($hotId);
  }

  /**
   * @param ?int $displayOrder      order base point
   * @param bool $isNext            fetch next page if true, or fetch prev page
   * @param int $size               fetched number per page
   * @param ?string $countryAbbr
   *
   * @return Collection             elements are MusicHot
   */
  public function findAllForPagination(
    ?int $displayOrder,
    bool $isNext,
    int $size,
    ?string $countryAbbr
  ) : Collection {
    $query = MusicHot::query();
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
