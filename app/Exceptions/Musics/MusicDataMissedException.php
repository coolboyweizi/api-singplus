<?php

namespace SingPlus\Exceptions\Musics;

use SingPlus\Exceptions\AppException;
use SingPlus\Exceptions\ExceptionCode;

class MusicDataMissedException extends AppException
{
  public function __construct(string $message = 'music data missed')
  {
    parent::__construct($message, ExceptionCode::MUSIC_DATA_MISSED);
  }
}
