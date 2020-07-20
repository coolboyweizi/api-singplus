<?php

namespace SingPlus\Contracts\Admins\Services;

interface AdminTaskService
{
  /**
   * @param string $taskId
   */
  public function isTaskIdExists(string $taskId) : bool;

  /**
   * @param string $taskId
   */
  public function saveTaskId(string $taskId, $data);
}
