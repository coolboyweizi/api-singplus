<?php

namespace SingPlus\Exceptions\Musics;

use SingPlus\Exceptions\AppException;
use SingPlus\Exceptions\ExceptionCode;

class MusicNotExistsException extends AppException
{
  public function __construct(string $message = 'music not found')
  {
    parent::__construct($message, ExceptionCode::MUSIC_NOT_EXISTS);
  }
}
