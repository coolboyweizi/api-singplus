<?php

namespace SingPlus\Exceptions\Users;

use SingPlus\Exceptions\AppException;
use SingPlus\Exceptions\ExceptionCode;

class UserNotExistsException extends AppException
{
  public function __construct(string $message = 'user not exists')
  {
    parent::__construct($message, ExceptionCode::USER_NOT_EXISTS);
  }
}
