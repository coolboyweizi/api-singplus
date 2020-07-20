<?php

namespace SingPlus\Exceptions\Musics;

use SingPlus\Exceptions\AppException;
use SingPlus\Exceptions\ExceptionCode;

class MusicOutOfStockException extends AppException
{
  public function __construct(string $message = 'music out of stock')
  {
    parent::__construct($message, ExceptionCode::MUSIC_OUT_OF_STOCK);
  }
}
