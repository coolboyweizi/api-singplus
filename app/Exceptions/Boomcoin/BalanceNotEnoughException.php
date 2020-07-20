<?php
/**
 * Created by PhpStorm.
 * User: karl
 * Date: 2018/4/8
 * Time: 下午4:01
 */

namespace SingPlus\Exceptions\Boomcoin;

use SingPlus\Exceptions\AppException;
use SingPlus\Exceptions\ExceptionCode;

class BalanceNotEnoughException extends AppException
{
    public function __construct(string $message = 'boomcoin balance not enough')
    {
        parent::__construct($message, ExceptionCode::BOOMCOIN_BALANCE_NOT_ENOUGH);
    }
}
