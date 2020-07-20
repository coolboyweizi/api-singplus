<?php

namespace SingPlus\Http\Controllers;

use Illuminate\Support\Facades\Auth;
use Illuminate\Http\Request;
use SingPlus\Http\Controllers\Controller;
use SingPlus\Services\FriendService;

class FriendController extends Controller
{
  /**
   * follow some one
   */
  public function follow(
    Request $request,
    FriendService $friendService
  ) {
    $this->validate($request, [
      'user_id'   => 'required|uuid',
    ]);

    $friendService->follow(
      $request->user()->id,
      $request->request->get('user_id')
    );

    return $this->renderInfo('success');
  }

  /**
   * follow some one
   */
  public function unfollow(
    Request $request,
    FriendService $friendService
  ) {
    $this->validate($request, [
      'user_id'   => 'required|uuid',
    ]);

    $friendService->unfollow(
      $request->user()->id,
      $request->request->get('user_id')
    );

    return $this->renderInfo('success');
  }

  /**
   * Get user followers (fans)
   */
  public function getFollowers(
    Request $request,
    FriendService $friendService
  ) {
    $this->validate($request, [
      'userId'          => 'uuid',
      'id'              => 'uuid',
      'isNext'          => 'boolean',
      'size'            => 'integer|min:1|max:50',
    ]);

    $actingUser = $request->user();
    $actingUserId = $actingUser ? $actingUser->id : "";
    $actingUserId = Auth::check() ? $actingUserId : "";
    $followers = $friendService->getFollowers(
      $actingUserId,
      $request->query->get('userId') ?: $actingUserId,
      $request->query->get('id'),
      (bool) $request->query->get('isNext', true),
      (int) $request->query->get('size', $this->defaultPageSize)
    );

    return $this->render('friends.followers', [
      'followers' => $followers,
    ]);
  }

  /**
   * Get user followers (fans)
   */
  public function getFollowers_v4(
    Request $request,
    FriendService $friendService
  ) {
    $this->validate($request, [
      'userId'          => 'uuid',
      'page'            => 'integer|min:1',
      'size'            => 'integer|min:1|max:50',
    ]);

    $actingUser = $request->user();
    $actingUserId = $actingUser ? $actingUser->id : "";
    $actingUserId = Auth::check() ? $actingUserId : "";
    $followers = $friendService->getFollowers(
      $actingUserId,
      $request->query->get('userId') ?: $actingUserId,
      null,
      true,
      (int) $request->query->get('size', $this->defaultPageSize),
      (int) $request->query->get('page', 1)
    );

    return $this->render('friends.followers', [
      'followers' => $followers,
    ]);
  }

  /**
   * Get user followings
   */
  public function getFollowings(
    Request $request,
    FriendService $friendService
  ) {
    $this->validate($request, [
      'userId'  => 'uuid',
    ]);
    $actingUser = $request->user();
    $actingUserId = $actingUser ? $actingUser->id : "";
    $actingUserId = Auth::check() ? $actingUserId : "";
    $followings = $friendService->getFollowings(
      $actingUserId,
      $request->query->get('userId') ?: $actingUserId
    );

    return $this->render('friends.followings', [
      'followings' => $followings,
    ]);
  }

  /**
   * Get user followings
   */
  public function getFollowings_v4(
    Request $request,
    FriendService $friendService
  ) {
    $this->validate($request, [
      'userId'  => 'uuid',
      'page'    => 'integer|min:1',
      'size'    => 'integer|min:1',
    ]);
    $actingUser = $request->user();
    $actingUserId = $actingUser ? $actingUser->id : "";
    $actingUserId = Auth::check() ? $actingUserId : "";
    $followings = $friendService->getFollowings(
      $actingUserId,
      $request->query->get('userId') ?: $actingUserId,
      (int) $request->query->get('page', 1),
      (int) $request->query->get('size', $this->defaultPageSize)
    );

    return $this->render('friends.followings', [
      'followings' => $followings,
    ]);
  }

  /**
   * Search user by nickname
   */
  public function searchUsers(
    Request $request,
    FriendService $friendService
  ) {
    $this->validate($request, [
      'nickname'  => 'nullable|string|max:32',
    ]);

    $users = $friendService->searchUsers(
      $request->user()->id,
      (string) $request->query->get('nickname')
    );

    return $this->render('friends.searchUsers', [
      'users' => $users,
    ]);
  }

  /**
   * Get user followings latest works
   */
  public function getFollowingLatestWorks(
    Request $request,
    FriendService $friendService
  ) {
    $this->validate($request, [
      'id'      => 'uuid',
      'isNext'  => 'boolean',
      'size'    => 'integer|min:1|max:50',
    ]);

    $works = $friendService->getUserFollowingLatestWorks(
      $request->user()->id,
      $request->query->get('id'),
      (bool) $request->query->get('isNext', true),
      (int) $request->query->get('size', $this->defaultPageSize)
    );

    return $this->render('friends.followingLatestWorks', [
      'latests' => $works,
    ]);
  }

  /**
   * Get user followings latest works
   */
  public function getFollowingLatestWorks_v4(
    Request $request,
    FriendService $friendService
  ) {
    $this->validate($request, [
      'page'    => 'integer|min:1',
      'size'    => 'integer|min:1|max:50',
    ]);

    $works = $friendService->getUserFollowingLatestWorks_graph(
      $request->user()->id,
      (int) $request->query->get('page', 1),
      (int) $request->query->get('size', $this->defaultPageSize)
    );

    return $this->render('friends.followingLatestWorks', [
      'latests' => $works,
    ]);
  }

  /**
   * Get all socialite user's relationship
   */
  public function getSocialiteUsersFriends(
    Request $request,
    FriendService $friendService,
    string $provider
  ) {
    $this->validate($request, [
      'socialiteUserIds'    => 'required|array|max:50',
      'socialiteUserIds.*'  => 'string|max:128',
    ]);

    $users = $friendService->getSocialiteUsersFriends(
      $request->user()->id,
      $request->query->get('socialiteUserIds'),
      $provider
    );

    return $this->render('friends.socialiteUsers', [
      'users' => $users,
    ]);
  }

  /**
   * Get recommend following's works of current user
   */
  public function getRecommendUserFollowings(
    Request $request,
    FriendService $friendService
  ) {
    $this->validate($request, [
      'id'        => 'uuid',
      'isNext'    => 'boolean',
      'size'      => 'integer|min:1|max:50',
    ]);

    $users = $friendService->getRecommendUserFollowings(
      $request->user()->id,
      $request->query->get('id'),
      (bool) $request->query->get('isNext', true),
      (int) $request->query->get('size', $this->defaultPageSize)
    );

    return $this->render('friends.recommendUserFollowings', [
      'users' => $users,
    ]);
  }

  /**
   * Get recommend works by country
   */
  public function getRecommendWorksByCountry(
    Request $request,
    FriendService $friendService
  ) {
    $this->validate($request, [
      'id'        => 'uuid|required_with:isNext',
      'isNext'    => 'boolean',
      'size'      => 'integer|min:1|max:50',
    ]);

    $countryAbbr = $request->headers->get('X-CountryAbbr');
    $actingUser = $request->user();
    $actingUserId = $actingUser ? $actingUser->id : "";
    $actingUserId = Auth::check() ? $actingUserId : "";

    $userWorks = $friendService->getRecommendWorksByCountry(
      $actingUserId,
      $countryAbbr,
      $request->query->get('id'),
      (bool) $request->query->get('isNext', true),
      (int) $request->query->get('size', $this->defaultPageSize)
    );

    return $this->render('friends.recommendWorksByCountry', [
      'userWorks' => $userWorks,
    ]);
  }
}
