<?php

namespace SingPlus\Exceptions\Users;

use SingPlus\Exceptions\AppException;
use SingPlus\Exceptions\ExceptionCode;

class UserMobileNotBoundException extends AppException
{
  public function __construct(string $message = 'user mobile not bound')
  {
    parent::__construct($message, ExceptionCode::USER_MOBILE_NOT_BOUND);
  }
}
