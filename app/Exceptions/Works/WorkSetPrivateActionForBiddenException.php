<?php
/**
 * Created by PhpStorm.
 * User: karl
 * Date: 2018/4/4
 * Time: 下午3:23
 */

namespace SingPlus\Exceptions\Works;

use SingPlus\Exceptions\AppException;
use SingPlus\Exceptions\ExceptionCode;

class WorkSetPrivateActionForBiddenException extends AppException
{
    public function __construct(string $message = 'can not set collab work to private')
    {
        parent::__construct($message, ExceptionCode::WORK_SET_PRIVATE_FORBIDDEN);
    }
}