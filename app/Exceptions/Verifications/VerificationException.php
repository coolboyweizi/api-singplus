<?php

namespace SingPlus\Exceptions\Verifications;

use SingPlus\Exceptions\AppException;
use SingPlus\Exceptions\ExceptionCode;

class VerificationException extends AppException
{
  public function __construct(string $message = 'verification failed')
  {
    parent::__construct($message, ExceptionCode::VERIFICATION_INCORRECT);
  }
}
