<?php
/**
 * Created by PhpStorm.
 * User: karl
 * Date: 2018/3/23
 * Time: 下午5:20
 */

namespace SingPlus\Exceptions\Works;


use SingPlus\Exceptions\AppException;
use SingPlus\Exceptions\ExceptionCode;

class WorkTagNotExistsException extends AppException
{
    public function __construct(string $message = 'the work tag not exists')
    {
        parent::__construct($message, ExceptionCode::WORK_TAG_NOT_EXISTS);
    }
}