<?php

namespace SingPlus\Exceptions\Coins;

use SingPlus\Exceptions\AppException;
use SingPlus\Exceptions\ExceptionCode;

class AccountBalanceNotEnoughException extends AppException
{
  public function __construct(string $message = 'balance not enough')
  {
    parent::__construct($message, ExceptionCode::ACCOUNT_BALANCE_NOT_ENOUGH);
  }
}
