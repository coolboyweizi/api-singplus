<?php

namespace SingPlus\Services;

use SingPlus\Contracts\Works\Services\WorkService as WorkServiceContract;

class WorkChorusService
{
  /**
   * @var WorkServiceContract
   */
  private $workService;

  public function __construct(
    WorkServiceContract $workService
  ) {
    $this->workService = $workService;
  }

  /**
   * @param string $musicId
   *
   * @return bool
   */
  public function hasMusicOwnChorusStartWork(string $musicId) : bool
  {
    return $this->workService->hasMusicOwnChorusStartWork($musicId);
  }
}
