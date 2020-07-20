<?php

namespace SingPlus\Exceptions\Admins;

use SingPlus\Exceptions\AppException;
use SingPlus\Exceptions\ExceptionCode;

class AdminTaskIdMissedException extends AppException
{
  public function __construct(string $message = 'task id missed')
  {
    parent::__construct($message, ExceptionCode::ADMIN_TASKID_MISSED);
  }
}
