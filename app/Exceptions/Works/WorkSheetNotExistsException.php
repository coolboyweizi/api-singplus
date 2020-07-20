<?php

namespace SingPlus\Exceptions\Works;

use SingPlus\Exceptions\AppException;
use SingPlus\Exceptions\ExceptionCode;

class WorkSheetNotExistsException extends AppException
{
  public function __construct(string $message = 'work sheet not exists')
  {
    parent::__construct($message, ExceptionCode::WORK_SHEET_NOT_EXISTS);
  }
}
