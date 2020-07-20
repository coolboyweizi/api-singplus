<?php

namespace SingPlus\Exceptions\Musics;

use SingPlus\Exceptions\AppException;
use SingPlus\Exceptions\ExceptionCode;

class MusicRecommendSheetNotExistsException extends AppException
{
  public function __construct(string $message = 'recommend music sheet not found')
  {
    parent::__construct($message, ExceptionCode::MUSIC_RECOMMEND_SHEET_NOT_EXIST);
  }
}
