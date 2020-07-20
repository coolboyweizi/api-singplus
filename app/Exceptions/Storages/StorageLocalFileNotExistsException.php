<?php

namespace SingPlus\Exceptions\Storages;

use SingPlus\Exceptions\ExceptionCode;
use SingPlus\Exceptions\AppException;

class StorageLocalFileNotExistsException extends \Exception
{
  public function __construct(string $message = 'file not exists')
  {
    parent::__construct($message, ExceptionCode::STORAGE_LOCAL_FILE_NOT_EXISTS);
  }
}
