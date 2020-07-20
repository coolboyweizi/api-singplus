<?php

namespace SingPlus\Support\Notification;

use Symfony\Component\Process\Process;
use SingPlus\Exceptions\AppException;
use SingPlus\Exceptions\ExceptionCode;

class UserPushInboxFinder
{
  public function getCollection(string $userId)
  {
    $process = new Process(sprintf('NodeFinder %s', $userId));
    $process->run();
    if ( ! $process->isSuccessful()) {
      throw new AppException($process->getErrorOutput(), ExceptionCode::EXTERNAL_EXECUTION_ERROR);
    }

    return $process->getOutput();
  }
}
