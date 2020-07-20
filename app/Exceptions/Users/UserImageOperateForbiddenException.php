<?php

namespace SingPlus\Exceptions\Users;

use SingPlus\Exceptions\AppException;
use SingPlus\Exceptions\ExceptionCode;

class UserImageOperateForbiddenException extends AppException
{
  public function __construct(string $message = 'image can\'t be operated')
  {
    parent::__construct($message, ExceptionCode::USER_IMAGE_OPERATE_FORBIDDEN);
  }
}
