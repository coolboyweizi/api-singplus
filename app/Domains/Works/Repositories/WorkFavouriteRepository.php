<?php

namespace SingPlus\Domains\Works\Repositories;

use Illuminate\Support\Collection;
use SingPlus\Support\Database\Eloquent\Pagination;
use SingPlus\Domains\Works\Models\WorkFavourite;

class WorkFavouriteRepository
{
  /**
   * Fetch one record by work id and user id
   *
   * @param string $workId
   * @param string $userId
   * @param bool $force       fetch trashed record if true
   *
   * @return ?WorkFavourite
   */
  public function findOneByWorkIdAndUserId(
    string $workId,
    string $userId,
    bool $force = false
  ) : ?WorkFavourite {
    $query =  WorkFavourite::where('work_id', $workId)
                           ->where('user_id', $userId);
    if ($force) {
      $query->withTrashed();
    }

    return $query->first();
  }

  /**
   * @param string $userId
   * @param array $workIds
   * 
   * @return Collection       elements are WorkFavourite
   */
  public function findAllByWorkIdsAndUserId(
    string $userId,
    array $workIds
  ) {
    if (empty($workIds)) {
      return collect();
    }

    return WorkFavourite::where('user_id', $userId)
                        ->whereIn('work_id', $workIds)
                        ->get();
  }

  /**
   * @param string $favouriteId
   * @param bool $force               fetch trashed record if true
   */
  public function findOneById(string $favouriteId, bool $force = false) : ?WorkFavourite
  {
    $query = WorkFavourite::query();
    if ($force) {
      $query->withTrashed();
    }

    return $query->find($favouriteId);
  }

  /**
   * @param string $workId
   * @param int $size
   *
   * @return Collection              elements are WorkFavourite
   */
  public function findAllByWorkIdForPagination(
    string $workId,
    $displayOrder,
    bool $isNext,
    int $size
  ) : Collection {
    $query = WorkFavourite::where('work_id', $workId);
    $query = Pagination::paginate($query,
                                  [
                                    'name'  => 'updated_at',
                                    'base'  => $displayOrder
                                  ],
                                  $isNext,
                                  $size);
    if ( ! $query) {
      return collect();
    }
    return $query->get();
  }
}
