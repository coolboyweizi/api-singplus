<?php

namespace SingPlus\Exceptions\Orders;

use SingPlus\Exceptions\AppException;
use SingPlus\Exceptions\ExceptionCode;

class SkuNotExistsException extends AppException
{
  public function __construct(string $message = 'product not exists')
  {
    parent::__construct($message, ExceptionCode::SKU_NOT_EXISTS);
  }
}
