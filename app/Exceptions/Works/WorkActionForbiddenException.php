<?php

namespace SingPlus\Exceptions\Works;

use SingPlus\Exceptions\AppException;
use SingPlus\Exceptions\ExceptionCode;

class WorkActionForbiddenException extends AppException
{
  public function __construct(string $message = 'your have no right to operate this work')
  {
    parent::__construct($message, ExceptionCode::WORK_ACTION_FORBIDDEN);
  }
}
