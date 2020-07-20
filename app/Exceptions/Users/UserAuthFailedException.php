<?php

namespace SingPlus\Exceptions\Users;

use SingPlus\Exceptions\AppException;
use SingPlus\Exceptions\ExceptionCode;

class UserAuthFailedException extends AppException
{
  public function __construct(string $message = 'user or password are not match')
  {
    parent::__construct($message, ExceptionCode::USER_AUTHENTICATE_FAILED);
  }
}
