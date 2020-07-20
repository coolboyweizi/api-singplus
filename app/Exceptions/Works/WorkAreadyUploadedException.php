<?php

namespace SingPlus\Exceptions\Works;

use SingPlus\Exceptions\AppException;
use SingPlus\Exceptions\ExceptionCode;

class WorkAreadyUploadedException extends AppException
{
  public function __construct(string $message = 'work aready uploaded')
  {
    parent::__construct($message, ExceptionCode::WORK_AREADY_UPLOADED);
  }
}
