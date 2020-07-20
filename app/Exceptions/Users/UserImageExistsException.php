<?php

namespace SingPlus\Exceptions\Users;

use SingPlus\Exceptions\AppException;
use SingPlus\Exceptions\ExceptionCode;

class UserImageExistsException extends AppException
{
  public function __construct(string $message = 'image aready uploaded')
  {
    parent::__construct($message, ExceptionCode::USER_IMAGE_EXISTS);
  }
}
