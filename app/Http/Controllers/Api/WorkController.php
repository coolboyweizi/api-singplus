<?php

namespace SingPlus\Http\Controllers\Api;

use Illuminate\Http\Request;
use SingPlus\Http\Controllers\Controller;
use SingPlus\Services\WorkService;

class WorkController extends Controller
{
  /**
   * synthetic user comment work
   */
  public function syntheticUserCommentWork(
    Request $request,
    WorkService $workService,
    string $workId
  ) {
    $request->request->set('workId', $workId);
    $this->validate($request, [
      'syntheticUserId' => 'required|uuid',
      'workId'          => 'required|uuid',
      'commentId'       => 'uuid',
      'comment'         => 'required|string|max:144',
    ], [
      'workId.uuid'   => 'work not exists', 
    ]);

    $comment = $workService->syntheticUserCommentWork(
      $request->request->get('syntheticUserId'), 
      $request->request->get('comment'),
      $request->request->get('workId'),
      $request->request->get('commentId')
    );

    return $this->renderInfo('success', [
      'commentId' => $comment->commentId,
    ]);
  }

  /**
   * @idempodent
   *
   * Synthetic user favourite work
   */
  public function syntheticUserFavouriteWork(
    Request $request,
    WorkService $workService,
    string $workId
  ) {
    $request->request->set('workId', $workId);
    $this->validate($request, [
      'syntheticUserId' => 'required|uuid',
      'workId'          => 'required|uuid',
    ]);

    $workService->syntheticUserFavouriteWork(
      $request->request->get('syntheticUserId'), 
      $request->request->get('workId')
    );

    return $this->renderInfo('success');
  }
}
