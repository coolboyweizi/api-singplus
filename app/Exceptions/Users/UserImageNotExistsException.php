<?php

namespace SingPlus\Exceptions\Users;

use SingPlus\Exceptions\AppException;
use SingPlus\Exceptions\ExceptionCode;

class UserImageNotExistsException extends AppException
{
  public function __construct(string $message = 'image not exists')
  {
    parent::__construct($message, ExceptionCode::USER_IMAGE_NOT_EXISTS);
  }
}
