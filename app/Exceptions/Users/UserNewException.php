<?php

namespace SingPlus\Exceptions\Users;

use SingPlus\Exceptions\AppException;
use SingPlus\Exceptions\ExceptionCode;

class UserNewException extends AppException
{
  public function __construct(string $message = 'new user, should complete profile')
  {
    parent::__construct($message, ExceptionCode::USER_NEW);
  }
}
