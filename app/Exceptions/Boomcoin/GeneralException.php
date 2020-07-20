<?php
/**
 * Created by PhpStorm.
 * User: karl
 * Date: 2018/4/8
 * Time: 下午4:03
 */

namespace SingPlus\Exceptions\Boomcoin;
use SingPlus\Exceptions\AppException;
use SingPlus\Exceptions\ExceptionCode;

class GeneralException extends AppException
{
    public function __construct(string $message = 'boomcoin request general exception')
    {
        parent::__construct($message, ExceptionCode::BOOMCOIN_GENERAL);
    }
}