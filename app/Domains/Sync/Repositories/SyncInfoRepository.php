<?php

/**
 * Created by PhpStorm.
 * User: karl
 * Date: 2018/3/19
 * Time: 上午10:25
 */
namespace SingPlus\Domains\Sync\Repositories;

use SingPlus\Domains\Sync\Models\SyncInfo;

class SyncInfoRepository
{

    /**
     * @param string $userId
     * @param string $type
     * @return null|SyncInfo
     */
    public function findOneByUserIdAndType(string $userId, string $type) :? SyncInfo
    {
        return SyncInfo::where('user_id', $userId)
            ->where('type', $type)
            ->first();
    }

}