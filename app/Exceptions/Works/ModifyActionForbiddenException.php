<?php
/**
 * Created by PhpStorm.
 * User: karl
 * Date: 2018/3/16
 * Time: 下午12:07
 */

namespace SingPlus\Exceptions\Works;


use SingPlus\Exceptions\AppException;
use SingPlus\Exceptions\ExceptionCode;

class ModifyActionForbiddenException extends AppException
{
    public function __construct(string $message = 'you have no right to operate this work')
    {
        parent::__construct($message, ExceptionCode::WORK_MODIFY_ACTION_FORBIDDEN);
    }
}