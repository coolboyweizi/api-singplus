<?php

namespace SingPlus\Domains\Admins\Repositories;

use SingPlus\Domains\Admins\Models\AdminTask;

class AdminTaskRepository
{
  /**
   * @param string $taskId
   */
  public function findOneByTaskId($taskId) : ?AdminTask
  {
    return AdminTask::where('task_id', $taskId)->first();
  }
}
