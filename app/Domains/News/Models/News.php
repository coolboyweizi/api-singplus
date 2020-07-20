<?php

namespace SingPlus\Domains\News\Models;
use SingPlus\Support\Database\Eloquent\MongodbModel;
/**
 * Created by PhpStorm.
 * User: karl
 * Date: 2018/1/18
 * Time: 下午5:32
 */

class News extends MongodbModel
{
    const STATUS_NORMAL = 1;
    const STATUS_DELETED = 0;

    protected $collection = 'news';

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'user_id',
        'type',       // type please to see \SingPlus\Contracts\News\Constants\News::class
        'desc',       // string news desc
        'detail',     // object   {work_id: "dsadadsds"}
        'status',
        'display_order',
    ];

    public function isNormal() : bool
    {
        return in_array($this->status, [
            self::STATUS_NORMAL,
        ]);
    }
}