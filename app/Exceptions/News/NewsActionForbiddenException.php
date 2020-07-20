<?php
/**
 * Created by PhpStorm.
 * User: karl
 * Date: 2018/1/19
 * Time: 下午2:25
 */

namespace SingPlus\Exceptions\News;


use SingPlus\Exceptions\AppException;
use SingPlus\Exceptions\ExceptionCode;

class NewsActionForbiddenException extends AppException
{
    public function __construct(string $message = 'your have no right to operate this news')
    {
        parent::__construct($message, ExceptionCode::NEWS_ACTION_FORBIDDEN);
    }
}