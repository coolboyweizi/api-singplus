<?php

namespace SingPlus\Exceptions\Admins;

use SingPlus\Exceptions\AppException;
use SingPlus\Exceptions\ExceptionCode;

class AdminTaskIdExistsException extends AppException
{
  public function __construct(string $message = 'task id aready exists')
  {
    parent::__construct($message, ExceptionCode::ADMIN_TASKID_EXISTS);
  }
}
