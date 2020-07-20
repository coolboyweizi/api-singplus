<?php
/**
 * Created by PhpStorm.
 * User: karl
 * Date: 2018/1/19
 * Time: 下午2:22
 */

namespace SingPlus\Exceptions\News;
use SingPlus\Exceptions\AppException;
use SingPlus\Exceptions\ExceptionCode;

class NewsNotExistsException extends AppException
{
    public function __construct(string $message = 'news not exists')
    {
        parent::__construct($message, ExceptionCode::NEWS_NOT_EXISTS);
    }
}