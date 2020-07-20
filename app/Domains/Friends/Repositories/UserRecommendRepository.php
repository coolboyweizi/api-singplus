<?php

namespace SingPlus\Domains\Friends\Repositories;

use Carbon\Carbon;
use Illuminate\Support\Collection;
use SingPlus\Domains\Friends\Models\UserRecommend;
use SingPlus\Support\Database\Eloquent\Pagination;

class UserRecommendRepository
{
  /**
   * @param string $id
   * @param array $projection       specify which projection should be return
   *
   * @return ?\UserFollowing
   */
  public function findOneById(string $id, array $projection = []) : ?UserRecommend
  {
    $query = UserRecommend::where('_id', $id);
    if ( ! empty($projection)) {
      $query->project($projection);
    }

    return $query->first();
  }

  /**
   * @param string $countryAbbr
   * @param ?int $displayOrder    for pagination
   * @param bool $isNext          for pagination 
   * @param int $size             for pagination
   *
   * @return Collection           elements are UserFollowing
   */
  public function findAllForPagination(
    string $countryAbbr,
    ?int $displayOrder,
    bool $isNext,
    int $size
  ) : Collection {
    // Get timezone
    $tz = null;
    $countrycode = collect(config('countrycode'))->filter(function ($item, $_) use ($countryAbbr) {
      return $item[0] == $countryAbbr;
    })->first();
    if ($countrycode) {
      // UTC+02:00 ==> +02:00
      $tz = substr($countrycode[3], 3);
    }

    $term = Carbon::now($tz)->format('Ymd');
    $query = UserRecommend::where('country_abbr', $countryAbbr)
                                   ->where('term', $term)
                                   ->where('status', UserRecommend::STATUS_NORMAL);
    $query = Pagination::paginate($query, ['base' => $displayOrder], $isNext, $size);

    return $query ? $query->get() : collect();
  }
}
