<?php

namespace SingPlus\Exceptions\Users;

use SingPlus\Exceptions\AppException;
use SingPlus\Exceptions\ExceptionCode;

class UserNicknameBeUsedByOtherException extends AppException
{
  public function __construct(string $message = 'nickname aready be used by other one')
  {
    parent::__construct($message, ExceptionCode::USER_NICKNAME_BE_USED_BY_OTHER);
  }
}
