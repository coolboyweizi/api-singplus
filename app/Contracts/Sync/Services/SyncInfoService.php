<?php

/**
 * Created by PhpStorm.
 * User: karl
 * Date: 2018/3/19
 * Time: 上午10:43
 */
namespace SingPlus\Contracts\Sync\Services;

interface SyncInfoService
{

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
    public function updateSyncInfo(string $userId, string $type, string $data) :\stdClass;

    /**
     * remove sync info item where info has a item with key and value
     *
     * @param string $userId
     * @param string $type
     * @param string $key
     * @param $value
     * @return mixed
     */
    public function removeSyncInfoItem(string $userId, string $type, string $key, $value);

}