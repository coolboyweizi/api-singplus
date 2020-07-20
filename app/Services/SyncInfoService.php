<?php
/**
 * Created by PhpStorm.
 * User: karl
 * Date: 2018/3/19
 * Time: 下午2:39
 */

namespace SingPlus\Services;

use SingPlus\Contracts\Sync\Services\SyncInfoService as SyncInfoServiceContract;
use SingPlus\Domains\Sync\Models\SyncInfo;

class SyncInfoService
{
    /**
     * @var SyncInfoServiceContract
     */
    private $syncInfoService;

    public function __construct(SyncInfoServiceContract $syncInfoService)
    {
        $this->syncInfoService = $syncInfoService;
    }


    /**
     * @param string $userId
     * @param string $data
     * @return \stdClass
     */
    public function updateAccompanimentSyncInfo(string $userId,  string $data){
        return $this->syncInfoService->updateSyncInfo($userId, SyncInfo::TYPE_ACCOMPANIMENT, $data);

    }

    /**
     * @param string $userId
     * @param $value
     * @return mixed
     */
    public function removeAccompanimentSyncInfoItem(string $userId, $value){
        return $this->syncInfoService->removeSyncInfoItem($userId, SyncInfo::TYPE_ACCOMPANIMENT, 'id', $value);
    }

}