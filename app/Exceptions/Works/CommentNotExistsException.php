<?php

namespace SingPlus\Exceptions\Works;

use SingPlus\Exceptions\AppException;
use SingPlus\Exceptions\ExceptionCode;

class CommentNotExistsException extends AppException
{
  public function __construct(string $message = 'comment not exists')
  {
    parent::__construct($message, ExceptionCode::WORK_COMMENT_NOT_EXISTS);
  }
}
