<?php

namespace SingPlus\Contracts\Admins\Services;

interface AdminGatewayService
{
  /**
   * Send work published notification
   *
   * @param string $workId      work id
   * @param string $authorId    work author user id
   */
  public function notifyWorkPublished(string $workId, string $authorId) : bool;

  /**
   * Send music's work ranking list
   *
   * @param string $musicId     music id
   */
  public function notifyUpdateWorkRanking(string $musicId) : bool;

  /**
   * Send notification for generating recommend user following
   *
   * @param string $userId
   *
   * @return bool
   */
  public function notifyGenRecommendUserFollowing(string $userId) : bool;

  /**
   * Send notification that user followed others
   *
   * @param string $userId
   * @param string $followedUserId
   *
   * @return bool
   */
  public function notifyUserFollowAction(string $userId, string $followedUserId) : bool;
}
