<?php

namespace SingPlus\Domains\Works\Repositories;

use Illuminate\Support\Collection;
use SingPlus\Domains\Works\Models\WorkRank;
use SingPlus\Contracts\Works\Constants\WorkRank as WorkRankConstant;

class WorkRankRepository
{
  /**
   * @param string $type      please to see WorkRankConstant
   */
  public function findAllByType(string $type, array $conditions) : Collection
  {
    $query = WorkRank::where('status', WorkRank::STATUS_NORMAL);
    switch ($type) {
      case WorkRankConstant::TYPE_GLOBAL :
        $query->where('is_global', WorkRank::GLOBAL_YES);
        break;

      case WorkRankConstant::TYPE_COUNTRY :
        if ( ! ($countryAbbr = array_get($conditions, 'countryAbbr'))) {
          return collect();
        }
        $query->where('country_abbr', $countryAbbr);
        break;

      case WorkRankConstant::TYPE_ROOKIE :
        $query->where('is_new_comer', WorkRank::ROOKIE_YES);
        break;

      default :
        return collect();
    }

    return $query->orderBy('rank')->get();
  }
}
