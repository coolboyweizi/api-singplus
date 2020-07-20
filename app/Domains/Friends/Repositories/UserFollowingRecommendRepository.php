<?php

namespace SingPlus\Domains\Friends\Repositories;

use Illuminate\Support\Collection;
use SingPlus\Domains\Friends\Models\UserFollowingRecommend;
use SingPlus\Support\Database\Eloquent\Pagination;

class UserFollowingRecommendRepository
{
  /**
   * @param string $id
   * @param array $projection       specify which projection should be return
   *
   * @return ?\UserFollowing
   */
  public function findOneById(string $id, array $projection = []) : ?UserFollowingRecommend
  {
    $query = UserFollowingRecommend::where('_id', $id);
    if ( ! empty($projection)) {
      $query->project($projection);
    }

    return $query->first();
  }

  /**
   * @param string $userId        followed user id
   * @param ?int $displayOrder    for pagination
   * @param bool $isNext          for pagination 
   * @param int $size             for pagination
   *
   * @return Collection           elements are UserFollowing
   */
  public function findAllFollowingsForPagination(
    string $userId,
    ?int $displayOrder,
    bool $isNext,
    int $size
  ) : Collection {
    $query = UserFollowingRecommend::where('user_id', $userId)
                                   ->where('status', UserFollowingRecommend::STATUS_NORMAL);
    $query = Pagination::paginate($query, ['base' => $displayOrder], $isNext, $size);

    return $query ? $query->get() : collect();
  }
}
