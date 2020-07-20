<?php

namespace SingPlus\Domains\Friends\Services;

use Carbon\Carbon;
use Illuminate\Support\Collection;
use SingPlus\Domains\Friends\Models\UserUnfollowed;
use SingPlus\Support\Database\SeqCounter;
use SingPlus\Contracts\Friends\Services\FriendService as FriendServiceContract;
use SingPlus\Domains\Friends\Repositories\UserFollowingRepository;
use SingPlus\Domains\Friends\Repositories\UserFollowingRecommendRepository;
use SingPlus\Domains\Friends\Repositories\UserRecommendRepository;
use SingPlus\Domains\Friends\Repositories\UserUnfollowedRepository;
use SingPlus\Domains\Friends\Models\UserFollowing;

class FriendService implements FriendServiceContract
{
  /**
   * @var UserFollowingRepository
   */
  private $userFollowingRepo;

  /**
   * @var UserFollowingRecommendRepository
   */
  private $userFollowingRecommendRepo;

  /**
   * @var UserRecommendRepository
   */
  private $userRecommendRepository;

  /**
   * @var $userUnfollowedRepository
   */
  private $userUnfollowedRepository;

  /**
   * @var GraphFriendService
   */
  private $graphFriendService;

  public function __construct(
    UserFollowingRepository $userFollowingRepo,
    UserFollowingRecommendRepository $userFollowingRecommendRepo,
    UserRecommendRepository $userRecommendRepository,
    UserUnfollowedRepository $userUnfollowedRepository,
    GraphFriendService $graphFriendService
  ) {
    $this->userFollowingRepo = $userFollowingRepo;
    $this->userFollowingRecommendRepo = $userFollowingRecommendRepo;
    $this->userRecommendRepository = $userRecommendRepository;
    $this->userUnfollowedRepository = $userUnfollowedRepository;
    $this->graphFriendService = $graphFriendService;
  }

  /**
   * {@inheritdoc}
   */
  public function follow(string $userId, string $followUserId) : bool
  {
    // if follow user self, do nothing
    if ($userId == $followUserId) {
      return false;
    }

    $userFollow = $this->userFollowingRepo->findOneByUserId($userId);
    if ( ! $userFollow) {
      $userFollow = new UserFollowing([
                      'user_id'       => $userId,
                      'display_order' => SeqCounter::getNext('user_followings'),
                    ]);
      $userFollow->save();
    }
    return $this->userFollowingRepo
                ->addFollowingForUser($userId, $followUserId) > 0;
  }

  /**
   * {@inheritdoc}
   */
  public function unfollow(string $userId, string $followUserId) : bool
  {
    // if unfollow user self, do nothing
    if ($userId == $followUserId) {
      return false;
    }

    return $this->userFollowingRepo
                ->deleteFollowingForUser($userId, $followUserId) > 0;
  }

  /**
   * {@inheritdoc}
   */
  public function getFollowings(string $userId, ?int $page = null, ?int $size = null) : Collection 
  {
    if ($page) {
        return $this->graphFriendService
                    ->getFollowings($userId, $page, $size);
    }

    $followings = $this->userFollowingRepo
                       ->findOneByUserId($userId, ['following_details' => 1]);
    $followings = $followings ? collect($followings->following_details)->reverse() : collect();
    $followingIds = $followings->map(function ($following, $_) {
                        return $following['user_id'];
                      })->toArray();
    $followers = $this->userFollowingRepo
                      ->findAllByUserIdsAndFollowUserId($followingIds, $userId);
    
    return $followings->map(function ($following, $_) use ($followers) {
              $follower = $followers->where('user_id', $following['user_id'])->first();

              return (object) [
                'userId'          => $following['user_id'],
                'isFollowing'     => true,
                'followAt'        => Carbon::createFromTimestamp($following['follow_at']),
                'isFollower'      => $follower ? true : false,
                'followedAt'      => $follower ? Carbon::createFromTimestamp($follower->following_details[0]['follow_at']) : null,
              ];
            });
  }

  /**
   * {@inheritdoc}
   */
  public function getFollowers(
    string $userId,
    ?string $id,
    bool $isNext,
    int $size,
    ?int $page = null
  ) : Collection {
    if ($page) {
        return $this->graphFriendService
                    ->getFollowers($userId, $page, $size);
    }

    $displayOrder = null;
    if ($id) {
      $follower = $this->userFollowingRepo->findOneById($id, ['display_order' => 1]);
      $displayOrder = $follower ? $follower->display_order : null;
    }

    $followings = $this->userFollowingRepo
                       ->findOneByUserId($userId, ['following_details' => 1]);
    $followings = $followings ? collect($followings->following_details) : collect();

    $followers = $this->userFollowingRepo
                ->findAllFollowersForPagination($userId, $displayOrder, $isNext, $size)
                ->map(function ($follower, $_) use ($followings) {
                  $following = $followings->where('user_id', $follower->user_id)->first();
                  return (object) [
                    'id'            => $follower->id,
                    'userId'        => $follower->user_id,
                    'isFollower'    => true,
                    'followedAt'    => Carbon::createFromTimestamp($follower->following_details[0]['follow_at']),
                    'isFollowing'   => $following ? true : false,
                    'followAt'      => $following ? Carbon::createFromTimestamp($following['follow_at']) : null,
                  ];
                });
    
    return $followers;
  }

  /**
   * {@inheritdoc}
   */
  public function getUserRelationship(string $userId, array $targetUserIds) : Collection
  {
    return $this->graphFriendService->getUserRelationship($userId, $targetUserIds);
    /*
    $followings = $this->userFollowingRepo
                       ->findOneByUserId($userId, ['following_details' => 1]);
    $followings = $followings ? collect($followings->following_details) : collect();
    $followers = $this->userFollowingRepo
                      ->findAllByUserIdsAndFollowUserId($targetUserIds, $userId);
    $relationships = collect();
    foreach ($targetUserIds as $targetUserId) {
      $follower = $followers->where('user_id', $targetUserId)->first();
      $following = $followings->where('user_id', $targetUserId)->first();

      $relationships->push((object) [
        'userId'        => $targetUserId,
        'isFollowing'   => $following ? true : false,
        'followAt'      => $following ? Carbon::createFromTimestamp($following['follow_at']) : null,
        'isFollower'    => $follower ? true : false,
        'followedAt'    => $follower ? Carbon::createFromTimestamp($follower->following_details[0]['follow_at']) : null,
      ]);
    }

    return $relationships;
    */
  }

  /**
   * {@inheritdoc}
   */
  public function countUserFollowers(string $userId) : int
  {
    return $this->userFollowingRepo->countByFollowingUserId($userId);
  }

  /**
   * {@inheritdoc}
   */
  public function getRecommendUserFollowings(
    string $userId, ?string $id, bool $isNext, int $size
  ) : Collection {
    $displayOrder = null;
    if ($id) {
      $recommend = $this->userFollowingRecommendRepo
                        ->findOneById($id, ['display_order' => 1]);
      $displayOrder = $recommend ? $recommend->display_order : null;
    }

    return $this->userFollowingRecommendRepo
                ->findAllFollowingsForPagination($userId, $displayOrder, $isNext, $size)
                ->map(function ($following, $_) {
                  return (object) [
                    'id'                => $following->id, 
                    'userId'            => $following->user_id,
                    'followingUserId'   => $following->following_user_id,
                    'isAutoRecommend'   => $following->isAutoRecommend(),
                  ];
                });
  }

  /**
   * {@inheritdoc}
   */
  public function getRecommendWorksByCountry(
    string $countryAbbr, ?string $id, bool $isNext, int $size
  ) : Collection {
    $displayOrder = null;
    if ($id) {
      $recommend = $this->userRecommendRepository
                        ->findOneById($id, ['display_order' => 1]);
      $displayOrder = $recommend ? $recommend->display_order : null;
    }

    return $this->userRecommendRepository
                ->findAllForPagination($countryAbbr, $displayOrder, $isNext, $size)
                ->map(function ($recommend, $_) {
                  return (object) [
                    'id'              => $recommend->id,
                    'userId'          => $recommend->user_id,
                    'countryAbbr'     => strtoupper($recommend->country_abbr),
                    'isAutoRecommend' => $recommend->isAutoRecommend(),
                    'workIds'         => $recommend->works_ids ?: [],
                  ];
                });
  }

    /**
     * @param string $userId
     * @param string $followUserId
     * @return bool
     *
     */
    public function addUnfollowed(string $userId, string $unfollowUserId): bool
    {
        if ($userId == $unfollowUserId) {
            return false;
        }

        $userUnfollow = $this->userUnfollowedRepository->findOneByUserId($userId);
        if ( ! $userUnfollow) {
            $userFollow = new UserUnfollowed([
                'user_id'       => $userId,
                'display_order' => SeqCounter::getNext("user_unfollowed"),
            ]);
            $userFollow->save();
        }

        if ($this->isUnfollowedUser($userId, $unfollowUserId)){
            return $this->userUnfollowedRepository->updateUnfollowedUserDetail($userId, $unfollowUserId) > 0;
        }else {
            return $this->userUnfollowedRepository
                    ->addUnFollowedForUser($userId, $unfollowUserId) > 0;
        }

    }

    /**
     * @param string $userId
     * @param string $followUserId
     * @return bool
     */
    public function isUnfollowedUser(string $userId, string $unfollowUserId): bool
    {
        if ($userId == $unfollowUserId) {
            return false;
        }

        return $this->userUnfollowedRepository->isUnfollowedUser($userId, $unfollowUserId);
    }

    /**
     * @param string $userId
     * @return array    elements are userIds
     */
    public function getFollowingUserIds(string $userId): array
    {
        $followings = $this->userFollowingRepo
            ->findOneByUserId($userId, ['followings' => 1]);
        return $followings ? $followings->followings : [];
    }
}
