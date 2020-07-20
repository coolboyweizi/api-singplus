<?php
/**
 * Created by PhpStorm.
 * User: karl
 * Date: 2018/4/9
 * Time: 上午11:10
 */

namespace SingPlus\Exceptions\Boomcoin;

use SingPlus\Exceptions\AppException;
use SingPlus\Exceptions\ExceptionCode;

class ExchangeException extends AppException
{
    public function __construct(string $message = 'boomcoin request general exception')
    {
        parent::__construct($message, ExceptionCode::BOOMCOIN_EXCHANGE_EXCEPTION);
    }
}