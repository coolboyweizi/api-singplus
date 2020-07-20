<?php

namespace SingPlus\Exceptions\Works;

use SingPlus\Exceptions\AppException;
use SingPlus\Exceptions\ExceptionCode;

class CommentActionForbiddenException extends AppException
{
  public function __construct(string $message = 'you have no right to operate this comment')
  {
    parent::__construct($message, ExceptionCode::WORK_COMMENT_ACTION_FORBIDDEN);
  }
}
