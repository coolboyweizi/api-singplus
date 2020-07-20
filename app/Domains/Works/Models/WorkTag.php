<?php
/**
 * Created by PhpStorm.
 * User: karl
 * Date: 2018/3/21
 * Time: 下午4:13
 */

namespace SingPlus\Domains\Works\Models;
use SingPlus\Support\Database\Eloquent\MongodbModel;

class WorkTag extends MongodbModel
{

    const STATUS_NORMAL = 1;
    const STATUS_DELETED = 0;
    const SOURCE_OFFICIAL = 'official';
    const SOURCE_USER = 'user';

    protected $collection = 'work_tags';

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'title',       //string name of work tag
        'cover',       //string  the url of work tag's cover
        'description',  //string the desc of work tag
        'join_count',   // int   the count of using the work tag
        'source',       //string the source of the work tag who created it
        'status',       //int   STATUS_NORMAL or STATUS_DELETED
    ];

}