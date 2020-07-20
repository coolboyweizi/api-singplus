<?php

namespace SingPlus\Exceptions\Users;

use SingPlus\Exceptions\AppException;
use SingPlus\Exceptions\ExceptionCode;

class SocialiteUserNotExistsException extends AppException
{
  public function __construct(string $message = 'user not exists')
  {
    parent::__construct($message, ExceptionCode::USER_SOCIALITE_NOT_EXISTS);
  }
}
