<?php

namespace SingPlus\Exceptions\Users;

use SingPlus\Exceptions\AppException;
use SingPlus\Exceptions\ExceptionCode;

class UserImageTooManyException extends AppException
{
  public function __construct(string $message = 'your gallery can not hold any more images, please delete some old images at first')
  {
    parent::__construct($message, ExceptionCode::USER_IMAGE_TOO_MANY);
  }
}
