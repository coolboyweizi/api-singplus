<?php

namespace SingPlus\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use SingPlus\Http\Controllers\Controller;
use SingPlus\Contracts\Feeds\Constants\Feed as FeedConstant;
use SingPlus\Services\FeedService;

class FeedController extends Controller
{
  /**
   * List feeds
   */
  public function getNotificationList(
    Request $request,
    FeedService $feedService
  ) {
    $this->validate($request, [
      'feedId'  => 'uuid|required_with:isNext',
      'isNext'  => 'boolean',
      'size'    => 'integer|min:1|max:50',
    ]);

    $feeds = $feedService->getUserNotificationFeeds(
      $request->user()->id,
      $request->query->get('feedId'),
      (bool) $request->query->get('isNext', true),
      (int) $request->query->get('size', $this->defaultPageSize),
      $request->query->get('type')
    );

    return $this->render('feeds.list', [
      'feeds' => $feeds,
    ]);
  }

  /**
   * Create external transmit
   */
  public function createWorkTransmitFeed(
    Request $request,
    FeedService $feedService
  ) {
    $this->validate($request, [
      'workId'  => 'required|uuid',
      'channel' => 'required|string|max:32',
    ]);

    $countryAbbr = $request->headers->get('X-RealCountryAbbr');
    $actingUser = $request->user();
    $actingUserId = $actingUser ? $actingUser->id : "";
    $actingUserId = Auth::check() ? $actingUserId : "";
    $feedService->createWorkTransmitFeed(
      $actingUserId,
      $request->request->get('workId'),
      $request->request->get('channel'),
      $countryAbbr ? $countryAbbr : ""
    );

    return $this->renderInfo('success');
  }

  /**
   * Get comment list
   */
  public function getUserCommentList(
    Request $request,
    FeedService $feedService
  ) {
    $this->validate($request, [
      'feedId'  => 'uuid|required_with:isNext',
      'isNext'  => 'boolean',
      'size'    => 'integer|min:1|max:50',
    ]);

    $comments = $feedService->getUserCommentFeeds(
      $request->user()->id,
      $request->query->get('feedId'),
      (bool) $request->query->get('isNext', true),
      (int) $request->query->get('size', $this->defaultPageSize)
    );

    return $this->render('feeds.comments', [
      'comments' => $comments,
      'clientVersion' => $request->headers->get('X-Version')
    ]);
  }

  /**
   * Get mixed feeds
   */
  public function getUserMixed(
    Request $request,
    FeedService $feedService
  ) {
    $this->validate($request, [
      'feedId'  => 'uuid|required_with:isNext',
      'isNext'  => 'boolean',
      'size'    => 'integer|min:1|max:50',
    ]);

    $feeds = $feedService->getUserMixedFeeds(
      $request->user()->id,
      $request->query->get('feedId'),
      (bool) $request->query->get('isNext', true),
      (int) $request->query->get('size', $this->defaultPageSize)
    );

    return $this->render('feeds.mixed', [
      'feeds' => $feeds,
    ]);
  }

  /**
   * User read his/her followed feeds
   */
  public function readUserFollowedFeed(
    Request $request,
    FeedService $feedService
  ) {
    $readCount = $feedService->setUserFeedsReaded(
      $request->user()->id,
      [FeedConstant::TYPE_USER_FOLLOWED]
    );

    return $this->renderInfo('success', [
      'readNum' => $readCount,
    ]);
  }

    /**
     * Get comment list
     */
    public function getUserGiftForWorkList(
        Request $request,
        FeedService $feedService
    ) {
        $this->validate($request, [
            'feedId'  => 'uuid|required_with:isNext',
            'isNext'  => 'boolean',
            'size'    => 'integer|min:1|max:50',
        ]);

        $giftsFeeds = $feedService->getUserGiftFeeds(
            $request->user()->id,
            $request->query->get('feedId'),
            (bool) $request->query->get('isNext', true),
            (int) $request->query->get('size', $this->defaultPageSize)
        );

        return $this->render('feeds.gifts', [
            'feeds' => $giftsFeeds
        ]);
    }
}
