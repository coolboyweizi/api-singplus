<?php

namespace SingPlus\Domains\Users\Repositories;

use Illuminate\Support\Collection;
use SingPlus\Domains\Users\Models\UserVerification;


class UserVerificationRepository
{
    /**
     * @param string $userId        user id
     * @param bool $force           if true, fetch recorcd even if
     *                              status is not normal
     */
    public function findOneByUserId(
        String $userId,
        bool $force=false
    ) : ?UserVerification {
        $query = UserVerification::where('user_id', $userId);
        if ( ! $force) {
            $query->where('status', UserVerification::STATUS_ONLINE);
        }

        return $query->first();
    }


    /**
     * @param string $userId        user id
     * @param bool $force           if true, fetch recorcd even if
     */
    public function findAllByUserIds(
        array $userIds,
        bool $force=false
    ) : Collection {
        if (empty($userIds)) {
            return collect();
        }
        $query = UserVerification::whereIn('user_id', $userIds);
        if ( ! $force) {
            $query->where('status', UserVerification::STATUS_ONLINE);
        }

        return $query->get();
    }
}
