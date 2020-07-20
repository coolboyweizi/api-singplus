<?php

/**
 * Created by PhpStorm.
 * User: karl
 * Date: 2018/3/19
 * Time: 上午10:20
 */
namespace SingPlus\Domains\Sync\Models;

use SingPlus\Support\Database\Eloquent\MongodbModel;

class SyncInfo extends MongodbModel
{

    const TYPE_ACCOMPANIMENT = 'accompaniment';

    protected $collection = 'user_sync_info';

    protected $fillable = [
        'user_id' ,     // string the user's id
        'type',        //string sync info type, default is accompaniment
        'data',        // string json string of sync info.
    ];

}