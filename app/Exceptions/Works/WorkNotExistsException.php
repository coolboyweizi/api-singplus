<?php

namespace SingPlus\Exceptions\Works;

use SingPlus\Exceptions\AppException;
use SingPlus\Exceptions\ExceptionCode;

class WorkNotExistsException extends AppException
{
  public function __construct(string $message = 'work not exists')
  {
    parent::__construct($message, ExceptionCode::WORK_NOT_EXISTS);
  }
}
