<?php
namespace SingPlus\Contracts\News\Constants;

/**
 * Created by PhpStorm.
 * User: karl
 * Date: 2018/1/18
 * Time: 下午5:29
 */
class News
{
    const TYPE_TRANSMIT         = 'news_transmit';
    const TYPE_PUBLISH          = 'news_publish';

    static public $validTypes = [
        self::TYPE_PUBLISH,
        self::TYPE_TRANSMIT,
    ];
}