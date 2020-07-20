<?php

namespace SingPlus\Services;

use SingPlus\Contracts\Admins\Services\AdminGatewayService as AdminGatewayServiceContract;
use SingPlus\Contracts\Works\Services\WorkService as WorkServiceContract;

class AdminGatewayService
{
  /**
   * @var AdminGatewayServiceContract
   */
  private $adminGatewayService;

  /**
   * @var WorkServiceContract
   */
  private $workService;

  public function __construct(
    AdminGatewayServiceContract $adminGatewayService,
    WorkServiceContract $workService
  ) {
    $this->adminGatewayService = $adminGatewayService;
    $this->workService = $workService;
  }

  /**
   * Send work published notification to admin
   */
  public function notifyWorkPublished(string $workId) : bool
  {
    $work = $this->workService->getDetail($workId, true);

    if ( ! $work) {
      return false;
    }

    return $this->adminGatewayService->notifyWorkPublished($workId, $work->userId);
  }

  /**
   * Send notification for update music work ranking list
   */
  public function notifyUpdateWorkRanking(string $musicId) : bool
  {
    return $this->adminGatewayService->notifyUpdateWorkRanking($musicId);
  }

  /**
   * Send notification for generating recommend user following
   *
   * @param string $userId
   */
  public function notifyGenRecommendUserFollowing(string $userId) : bool
  {
    return $this->adminGatewayService->notifyGenRecommendUserFollowing($userId);
  }

  /**
   * Send notification that user followed others
   *
   * @param string $userId
   * @param string $followedUserId
   */
  public function notifyUserFollowAction(string $userId, string $followedUserId) : bool
  {
    return $this->adminGatewayService->notifyUserFollowAction($userId, $followedUserId);
  }
}
