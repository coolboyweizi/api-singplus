<?php

namespace SingPlus\Exceptions\Verifications;

use SingPlus\Exceptions\AppException;
use SingPlus\Exceptions\ExceptionCode;

class VerificationFrequenceException extends AppException
{
  public function __construct(string $message = 'send frequence too many')
  {
    parent::__construct($message, ExceptionCode::VERIFICATION_FREQUENCE);
  }
}
