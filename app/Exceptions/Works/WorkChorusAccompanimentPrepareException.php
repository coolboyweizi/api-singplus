<?php

namespace SingPlus\Exceptions\Works;

use SingPlus\Exceptions\AppException;
use SingPlus\Exceptions\ExceptionCode;

class WorkChorusAccompanimentPrepareException extends AppException
{
  public function __construct(string $message = 'Accompaniment preparing')
  {
    parent::__construct($message, ExceptionCode::WORK_CHORUS_ACCOMPANIMENT_PREPARE);
  }
}
