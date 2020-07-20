<?php

namespace SingPlus\Exceptions\Users;

use SingPlus\Exceptions\AppException;
use SingPlus\Exceptions\ExceptionCode;

class UserPasswordIncorrectException extends AppException
{
  public function __construct(string $message = 'user password incorrect')
  {
    parent::__construct($message, ExceptionCode::USER_PASSWORD_INCORRECT);
  }
}
