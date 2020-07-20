<?php

namespace SingPlus\Exceptions\Storages;

use SingPlus\Exceptions\ExceptionCode;
use SingPlus\Exceptions\AppException;

class StoragePathIllegalException extends \Exception
{
  /**
   * @var string
   */
  private $storageKey;

  public function __construct(string $key, string $message = 'storage uri illegal')
  {
    parent::__construct($message, ExceptionCode::STORAGE_PATH_ILLEGAL);
    $this->storageKey = $key;
  }

  public function getKey()
  {
    return $this->storageKey;
  }
}
