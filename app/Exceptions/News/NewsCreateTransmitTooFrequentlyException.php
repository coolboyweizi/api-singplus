<?php
/**
 * Created by PhpStorm.
 * User: karl
 * Date: 2018/1/23
 * Time: 下午12:16
 */

namespace SingPlus\Exceptions\News;


use SingPlus\Exceptions\AppException;
use SingPlus\Exceptions\ExceptionCode;

class NewsCreateTransmitTooFrequentlyException extends AppException
{
    public function __construct(string $message = 'your operation is too frequently')
    {
        parent::__construct($message, ExceptionCode::NEWS_CREATE_FREQUENCY);
    }
}