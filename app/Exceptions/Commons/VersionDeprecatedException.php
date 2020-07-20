<?php

namespace SingPlus\Exceptions\Commons;

use SingPlus\Exceptions\AppException;
use SingPlus\Exceptions\ExceptionCode;

class VersionDeprecatedException extends AppException
{
  public function __construct(string $message = 'Your app version is deprecated, please download latest version')
  {
    parent::__construct($message, ExceptionCode::VERSION_DEPRECATED);
  }
}
