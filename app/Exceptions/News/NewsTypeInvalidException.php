<?php
/**
 * Created by PhpStorm.
 * User: karl
 * Date: 2018/1/22
 * Time: 上午10:17
 */

namespace SingPlus\Exceptions\News;


use SingPlus\Exceptions\AppException;
use SingPlus\Exceptions\ExceptionCode;

class NewsTypeInvalidException extends AppException
{
    public function __construct(string $message = 'news type invalid')
    {
        parent::__construct($message, ExceptionCode::NEWS_TYPE_INVALID);
    }
}