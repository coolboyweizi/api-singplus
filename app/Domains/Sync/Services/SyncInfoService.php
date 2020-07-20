<?php

/**
 * Created by PhpStorm.
 * User: karl
 * Date: 2018/3/19
 * Time: 上午10:53
 */
namespace SingPlus\Domains\Sync\Services;

use SingPlus\Contracts\Sync\Services\SyncInfoService as SyncInfoServiceContract;
use SingPlus\Domains\Sync\Models\SyncInfo;
use SingPlus\Domains\Sync\Repositories\SyncInfoRepository;

class SyncInfoService implements SyncInfoServiceContract
{

    /**
     * @var SyncInfoRepository
     */
    private $syncInfoRepo;


    public function __construct(SyncInfoRepository $syncInfoRepository)
    {
        $this->syncInfoRepo = $syncInfoRepository;
    }

    /**
     * update sync info
     *
     * @param string $userId
     * @param string $type
     * @param string $data
     * @return \stdClass
     *              - userId    string
     *              - type  string
     *              - data  string json string of syncinfo
     */
    public function updateSyncInfo(string $userId, string $type, string $data): \stdClass
    {
        $syncData = $data;
        $syncInfo = $this->syncInfoRepo->findOneByUserIdAndType($userId, $type);
        if ($syncInfo){
            if ($syncInfo->type == SyncInfo::TYPE_ACCOMPANIMENT){
                //合并同步伴奏数据
                $syncData = $this->combineAccompanimentData($syncInfo->data, $data);
            }
        }

        SyncInfo::updateOrCreate(
            ['user_id' => $userId, 'type' => $type],
            ['data' => $syncData]
        );

        return (object)[
            'userId' => $userId,
            'type' => $type,
            'data' => $syncData
        ];
    }

    /**
     * remove sync info item where info has a item with key and value
     *
     * @param string $userId
     * @param string $type
     * @param string $key
     * @param $value
     * @return mixed
     */
    public function removeSyncInfoItem(string $userId, string $type, string $key, $value)
    {
        $syncInfo = $this->syncInfoRepo->findOneByUserIdAndType($userId, $type);
        if ($syncInfo){
            if ($syncInfo->type == SyncInfo::TYPE_ACCOMPANIMENT){
                // 删除匹配的伴奏信息
                $newData = $this->removeAccompanimentItem($syncInfo->data, $key, $value);
                if ($newData){
                    SyncInfo::where('user_id', $userId)
                        ->where('type', $type)
                        ->update(['data' => $newData]);
                }
            }
        }
    }



    /**
     * @param string $savedData
     * @param string $newData
     * @return string
     */
    private function combineAccompanimentData(?string $savedData, string $newData): string{
        if (!$savedData){
            return $newData;
        }

        $savedDataJson = json_decode($savedData);
        $newDataJson = json_decode($newData);
        $savedCollect = collect($savedDataJson);
        foreach ($newDataJson as $data){
            if (!$savedCollect->contains('id', $data->id)){
                array_push($savedDataJson, $data);
            }
        }
        return json_encode($savedDataJson);
    }

    /**
     * @param string $userId
     * @param string $type
     * @param null|string $savedData
     * @param string $key
     * @param $value
     */
    private function removeAccompanimentItem(?string $savedData, string $key, $value) :?string{

        if (!$savedData){
            return null;
        }
        $savedDataJson = json_decode($savedData);
        $newDataJson = [];
        foreach ($savedDataJson as $data){
            if ($data->$key != $value){
                array_push($newDataJson, $data);
            }
        }
        return json_encode($newDataJson);
    }

}