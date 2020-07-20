<?php

namespace SingPlus\Exceptions\Works;

use SingPlus\Exceptions\AppException;
use SingPlus\Exceptions\ExceptionCode;

class WorkUploadTaskNotExistsException extends AppException
{
  public function __construct(string $message = 'work upload task not exists')
  {
    parent::__construct($message, ExceptionCode::WORK_UPLOAD_TASK_NOT_EXISTS);
  }
}
