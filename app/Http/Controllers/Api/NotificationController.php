<?php

namespace SingPlus\Http\Controllers\Api;

use Illuminate\Http\Request;
use SingPlus\Http\Controllers\Controller;
use SingPlus\Services\NotificationService;

class NotificationController extends Controller
{
  /**
   * send announcement notification
   */
  public function notifyAnnouncementCreated(
    Request $request,
    NotificationService $notificationService
  ) {
    $notificationService->notifyAnnouncementToTopic();

    return $this->renderInfo('success');
  }

  /**
   * Send message to user's devices
   */
  public function pushMessages(
    Request $request,
    NotificationService $notificationService
  ) {
    $this->validate($request, [
      'tos'     => 'required|array',
      'type'    => 'required|string',
      'data'    => 'array',
      'toUserIds' => 'array'
    ]);

    $notificationService->notifyOperationMessages(
      $request->json('tos'),
      $request->json('type'),
      $request->json('data', []),
      $request->json('taskId'),
      $request->json('toUserIds', [])
    );

    return $this->renderInfo('success');
  }

    /**
     * @param Request $request
     * @param NotificationService $notificationService
     */
  public function pushPrivateMsgNotify(
      Request $request,
      NotificationService $notificationService
  ){
      $this->validate($request, [
          'userId'     => 'required|uuid',
          'receiveId'    => 'required|uuid',
      ]);

      $notificationService->notifyPrivateMsgToUser(
          $request->json('userId'),
          $request->json('receiveId'),
          $request->json('redirectTo')
      );

      return $this->renderInfo('success');
  }
}
