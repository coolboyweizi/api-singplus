<?php

namespace SingPlus\Domains\Works\Repositories;

use Carbon\Carbon;
use SingPlus\Domains\Works\Models\WorkUploadTask;

class WorkUploadTaskRepository
{
  /**
   * @param string $taskId
   *
   * @return ?WorkUploadTask
   */
  public function findOneById(string $taskId) : ?WorkUploadTask
  {
    return WorkUploadTask::find($taskId);
  }

  /**
   * Delete task by id
   *
   * @param string $taskId
   */
  public function deleteById(string $taskId)
  {
    return WorkUploadTask::destroy($taskId);
  }

  /**
   * Delete all tasks by id
   */
  public function deleteAllByExpiredTime(Carbon $expiredTime)
  {
    return WorkUploadTask::where('created_at', '<=', $expiredTime->format('Y-m-d H:i:s'))
                         ->delete();
  }
}
