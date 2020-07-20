<?php

namespace SingPlus\Domains\Friends\Repositories;

use Illuminate\Support\Collection;
use SingPlus\Domains\Friends\Models\UserFollowing;
use SingPlus\Support\Database\Eloquent\Pagination;

/**
 * Technical Debt
 *
 * 1. 由于projection: $elemMatch只返回first matched element，不会返回所有匹配的elements
 *    因此只能取出全部following_details
 * 2. followings & followers 列表无法排序
 */
class UserFollowingRepository
{
  /**
   * @param string $id
   * @param array $projection       specify which projection should be return
   *
   * @return ?\UserFollowing
   */
  public function findOneById(string $id, array $projection = []) : ?UserFollowing
  {
    $query = UserFollowing::where('_id', $id);
    if ( ! empty($projection)) {
      $query->project($projection);
    }

    return $query->first();
  }

  /**
   * @param string $userId
   * @param array $projection       specify which projection should be return
   *
   * @return ?\UserFollowing
   */
  public function findOneByUserId(string $userId, array $projection = []) : ?UserFollowing
  {
    $query = UserFollowing::where('user_id', $userId);
    if ( ! empty($projection)) {
      $query->project($projection);
    }

    return $query->first();
  }

  /**
   * @param string $userId
   * @param string $followUserId
   *
   * @return int   affected docs
   */
  public function addFollowingForUser(
    string $userId,
    string $followUserId
  ) : int {
    $follow = UserFollowing::buildFollowItem($followUserId);
    $affectedRows = UserFollowing::where('user_id', $userId)
                                 ->push('followings', $followUserId, true);
    if ($affectedRows > 0) {
      UserFollowing::where('user_id', $userId)
                   ->push('following_details', $follow);
    }

    return $affectedRows;
  }

  /**
   * @param string $userId
   * @param string $followUserId
   *
   * @return int   affected docs
   */
  public function deleteFollowingForUser(
    string $userId,
    string $followUserId
  ) : int {
    $affectedRows = UserFollowing::where('user_id', $userId)
                                 ->pull('followings', $followUserId);
    if ($affectedRows > 0) {
      UserFollowing::where('user_id', $userId)
                   ->pull('following_details', [
                      'user_id' => $followUserId
                   ]);
    }
    return $affectedRows;
  }

  /**
   * @param string $userId        followed user id
   * @param ?int $displayOrder    for pagination
   * @param bool $isNext          for pagination 
   * @param int $size             for pagination
   *
   * @return Collection           elements are UserFollowing
   */
  public function findAllFollowersForPagination(
    string $userId,
    ?int $displayOrder,
    bool $isNext,
    int $size
  ) : Collection {
    $projection = [
      'user_id'           => 1,
      'following_details' => [
        '$elemMatch'  => [
          'user_id' => $userId,
        ],
      ],
      'display_order'     => 1,
    ];
    $query = UserFollowing::where('following_details.user_id', 'all', [$userId])
                          ->project($projection);
    $query = Pagination::paginate($query, ['base' => $displayOrder], $isNext, $size);

    return $query ? $query->get() : collect();
  }

  /**
   * @param array $userIds          elements are user id
   * @param string $followUserId    follow user id
   *
   * @return Collection             elements are UserFollowing
   */
  public function findAllByUserIdsAndFollowUserId(
    array $userIds,
    string $followUserId
  ) : Collection {
    if (empty($userIds)) {
      return collect();
    }

    $projection = [
      'user_id' => 1,
      'following_details' => [
        '$elemMatch'  => [
          'user_id' => $followUserId,
        ],
      ],
    ];

    return UserFollowing::whereIn('user_id', $userIds)
                        ->where('following_details.user_id', 'all', [$followUserId])
                        ->project($projection)
                        ->get();
  }

  /**
   * count by following user id
   *
   * @param string $userId
   */
  public function countByFollowingUserId(string $userId) : int
  {
    return UserFollowing::where('followings', 'all', [$userId])->count();
  }
}
