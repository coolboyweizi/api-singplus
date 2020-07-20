<?php

namespace SingPlus\Exceptions\Works;

use SingPlus\Exceptions\AppException;
use SingPlus\Exceptions\ExceptionCode;

class WorkUploadFailedException extends AppException
{
  public function __construct(string $message = 'upload failed')
  {
    parent::__construct($message, ExceptionCode::WORK_UPLOAD_FAILED);
  }
}
