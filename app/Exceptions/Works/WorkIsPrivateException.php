<?php
/**
 * Created by PhpStorm.
 * User: karl
 * Date: 2018/4/4
 * Time: 下午4:27
 */

namespace SingPlus\Exceptions\Works;

use SingPlus\Exceptions\AppException;
use SingPlus\Exceptions\ExceptionCode;

class WorkIsPrivateException extends AppException
{
    public function __construct(string $message = 'The cover is set to be private')
    {
        parent::__construct($message, ExceptionCode::WORK_UNAVAILABLE_WHEN_PRIVATE);
    }
}
