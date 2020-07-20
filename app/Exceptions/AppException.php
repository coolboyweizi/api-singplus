<?php

namespace SingPlus\Exceptions;

use SingPlus\Exceptions\ExceptionCode;

class AppException extends \Exception
{
  public function __construct(string $message, ?int $code = NULL)
  {
    parent::__construct($message, $code ?: ExceptionCode::GENERAL);
  }
}
