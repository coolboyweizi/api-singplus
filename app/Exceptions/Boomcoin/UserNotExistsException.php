<?php

/**
 * Created by PhpStorm.
 * User: karl
 * Date: 2018/4/8
 * Time: 下午3:58
 */
namespace SingPlus\Exceptions\Boomcoin;

use SingPlus\Exceptions\AppException;
use SingPlus\Exceptions\ExceptionCode;

class UserNotExistsException extends AppException
{
    public function __construct(string $message = 'user not exists int boomcoin account')
    {
        parent::__construct($message, ExceptionCode::BOOMCOIN_USER_NOT_EXSITS);
    }
}