<?php

namespace SingPlus\Domains\Admins\Services;

use SingPlus\Contracts\Admins\Services\AdminTaskService as AdminTaskServiceContract;
use SingPlus\Domains\Admins\Repositories\AdminTaskRepository;
use SingPlus\Domains\Admins\Models\AdminTask;

class AdminTaskService implements AdminTaskServiceContract
{
  /**
   * @var AdminTaskRepository
   */
  private $adminTaskRepo;

  public function __construct(
    AdminTaskRepository $adminTaskRepo
  ) {
    $this->adminTaskRepo = $adminTaskRepo;
  }

  /**
   * {@inheritdoc}
   */
  public function isTaskIdExists(string $taskId) : bool
  {
    $task = $this->adminTaskRepo->findOneByTaskId($taskId);
    return $task ? true : false;
  }

  /**
   * {@inheritdoc}
   */
  public function saveTaskId(string $taskId, $data)
  {
    $task = new AdminTask([
      'task_id' => $taskId,
      'data'    => (array) $data,
    ]);
    return $task->save();
  }
}
