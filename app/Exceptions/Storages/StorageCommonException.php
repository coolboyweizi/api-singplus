<?php

namespace SingPlus\Exceptions\Storages;

use SingPlus\Exceptions\ExceptionCode;
use SingPlus\Exceptions\AppException;

class StorageCommonException extends \Exception
{
  public function __construct(string $message = 'storage service failed')
  {
    parent::__construct($message, ExceptionCode::STORAGE_COMMON);
  }
}
