<?php

namespace SingPlus\Exceptions\Storages;

use SingPlus\Exceptions\ExceptionCode;
use SingPlus\Exceptions\AppException;

class StorageFileExistsException extends \Exception
{
  /**
   * @var string
   */
  private $storageKey;

  public function __construct(string $key, string $message = 'file aready exists')
  {
    parent::__construct($message, ExceptionCode::STORAGE_FILE_EXISTS);
    $this->storageKey = $key;
  }

  public function getKey()
  {
    return $this->storageKey;
  }
}
