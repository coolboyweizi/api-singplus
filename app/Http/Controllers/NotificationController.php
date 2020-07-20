<?php

namespace SingPlus\Http\Controllers;

use Illuminate\Http\Request;
use SingPlus\Http\Controllers\Controller;
use SingPlus\Services\NotificationService;

class NotificationController extends Controller
{
  /**
   * Set user notification
   */
  public function bindUserPushAlias(
    Request $request,
    NotificationService $notificationService
  ) {
    $this->validate($request, [
      'alias'       => 'nullable|string|max:256',
    ]);

    $notificationService->bindUserPushAlias(
      $request->user()->id,
      $request->request->get('alias')
    );

    return $this->renderInfo('success');
  }

  /**
   * Get editor recommend messages, include: music sheet, work sheet ...
   */
  public function getEditorRecommendList(
    Request $request,
    NotificationService $notificationService
  ) {
    $this->validate($request, [
      'id'    => 'uuid',
      'size'  => 'integer|min:1|max:50',
    ]);

    $countryAbbr = $request->headers->get('X-CountryAbbr');
    $messages = $notificationService->getEditorRecommends(
      $request->user()->id,
      $countryAbbr,
      $request->query->get('id'),
      (int) $request->query->get('size', $this->defaultPageSize)
    );

    return $this->render('notificationLogic.edtorRecommend', [
      'messages'  => $messages,
    ]);
  }
}
