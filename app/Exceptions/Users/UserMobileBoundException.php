<?php

namespace SingPlus\Exceptions\Users;

use SingPlus\Exceptions\AppException;
use SingPlus\Exceptions\ExceptionCode;

class UserMobileBoundException extends AppException
{
  public function __construct(string $message = 'user mobile aready bound')
  {
    parent::__construct($message, ExceptionCode::USER_MOBILE_BOUND);
  }
}
