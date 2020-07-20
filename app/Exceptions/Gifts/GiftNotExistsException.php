<?php

namespace SingPlus\Exceptions\Gifts;

use SingPlus\Exceptions\AppException;
use SingPlus\Exceptions\ExceptionCode;

class GiftNotExistsException extends AppException
{
  public function __construct(string $message = 'gift not exists')
  {
    parent::__construct($message, ExceptionCode::GIFT_NOT_EXIST);
  }
}
