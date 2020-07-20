<?php

namespace SingPlus\Exceptions\Users;

use SingPlus\Exceptions\AppException;
use SingPlus\Exceptions\ExceptionCode;

class UserImageUploadFailedException extends AppException
{
  public function __construct(string $message = 'upload failed')
  {
    parent::__construct($message, ExceptionCode::USER_IMAGE_UPLOAD_FAILED);
  }
}
