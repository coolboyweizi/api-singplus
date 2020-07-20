<?php

namespace SingPlus\Domains\Friends\Repositories;

use Illuminate\Support\Collection;
use SingPlus\Domains\Friends\Models\UserUnfollowed;
use SingPlus\Support\Database\Eloquent\Pagination;

/**
 * Technical Debt
 *
 * 1. 由于projection: $elemMatch只返回first matched element，不会返回所有匹配的elements
 *    因此只能取出全部following_details
 * 2. followings & followers 列表无法排序
 */
class UserUnfollowedRepository
{
    /**
     * @param string $id
     * @param array $projection       specify which projection should be return
     *
     * @return ?\UserUnfollowed
     */
    public function findOneById(string $id, array $projection = []) : ?UserUnfollowed
    {
        $query = UserUnfollowed::where('_id', $id);
        if ( ! empty($projection)) {
            $query->project($projection);
        }

        return $query->first();
    }

    /**
     * @param string $userId
     * @param array $projection       specify which projection should be return
     *
     * @return ?\UserUnfollowed
     */
    public function findOneByUserId(string $userId, array $projection = []) : ?UserUnfollowed
    {
        $query = UserUnfollowed::where('user_id', $userId);
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
     * @return Collection           elements are UserUnfollowed
     */
    public function findAllUnfollowedForPagination(
        string $userId,
        ?int $displayOrder,
        bool $isNext,
        int $size
    ) : Collection {
        $projection = [
            'user_id'           => 1,
            'unfollowed_details' => [
                '$elemMatch'  => [
                    'user_id' => $userId,
                ],
            ],
            'display_order'     => 1,
        ];
        $query = UserUnfollowed::where('unfollowed_details.user_id', 'all', [$userId])
            ->project($projection);
        $query = Pagination::paginate($query, ['base' => $displayOrder], $isNext, $size);

        return $query ? $query->get() : collect();
    }

    /**
     * @param string $userId
     * @param string $unfollowUserId
     *
     * @return int   affected docs
     */
    public function addUnFollowedForUser(
        string $userId,
        string $unfollowUserId
    ) : int {
        $unfollowed = UserUnfollowed::buildUnFollowedItem($unfollowUserId);
        $affectedRows = UserUnfollowed::where('user_id', $userId)
            ->push('unfollowed', $unfollowUserId, true);
        if ($affectedRows > 0) {
            UserUnfollowed::where('user_id', $userId)
                ->push('unfollowed_details', $unfollowed);
        }

        return $affectedRows;
    }

    public function isUnfollowedUser(
        string $userId,
        string $unfollowUserId
    ) :bool
    {
        $unfollow = $this->findOneByUserId($userId);
        if ($unfollow && $unfollow->unfollowed){
            if (in_array($unfollowUserId, $unfollow->unfollowed) ){
                return true;
            }
        }
        return false;
    }

    public function updateUnfollowedUserDetail(
        string $userId,
        string $unfollowUserId
    ) :int
    {
        $unfollowed = UserUnfollowed::buildUnFollowedItem($unfollowUserId);
        $affectedRows = UserUnfollowed::where('user_id', $userId)
            ->pull('unfollowed_details', [
                'user_id' => $unfollowUserId
            ]);
        if ($affectedRows > 0){
            UserUnfollowed::where('user_id', $userId)
                ->push('unfollowed_details', $unfollowed);
        }
        return $affectedRows;
    }
}
