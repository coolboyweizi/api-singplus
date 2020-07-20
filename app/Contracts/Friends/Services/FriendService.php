<?php

namespace SingPlus\Contracts\Friends\Services;

use Illuminate\Support\Collection;

interface FriendService
{
  /**
   * user follow target user
   *
   * @param string $userId
   * @param string $followUserId
   *
   * @return bool
   */
  public function follow(string $userId, string $followUserId) : bool;

  /**
   * user unfollow target user
   *
   * @param string $userId
   * @param string $followUserId
   *
   * @return bool
   */
  public function unfollow(string $userId, string $followUserId) : bool;

  /**
   * Get user followings
   *
   * @param string $userId      给定用户user id，以其为中心查找关系
   * @param ?int $page          如果page有值表示使用page翻页
   *
   * @return Collection         elements are \stdClass
   *                            - userId string            目标用户userId
   *                            - isFollowing bool    是否是给定用户的following
   *                            - followAt \Carbon\Carbon  给定用户关注目标用户的时间
   *                            - isFollower bool     是否是给定用户的follower
   *                            - followedAt ?\Carbon\Carbon  给定用户被目标用户关注的时间
   */
  public function getFollowings(string $userId, ?int $page = null, ?int $size = null) : Collection;

  /**
   * Get user followers (fans)
   *
   * @param string $userId
   * @param ?string $id               for pagination
   * @param bool $isNext              for pagination
   * @param int $size                 for pagination
   * @param ?int $page                如果page有值，表示使用page翻页
   *
   * @return Collection         elements are \stdClass
   *                            - id string                 for pagination
   *                            - userId string             目标用户userId
   *                            - isFollowing bool          是否是给定用户的following
   *                            - followAt \Carbon\Carbon   给定用户关注目标用户的时间
   *                            - isFollower bool           是否是给定用户的follower
   *                            - followedAt ?\Carbon\Carbon  给定用户被目标用户关注的时间
   */
  public function getFollowers(
    string $userId,
    ?string $id,
    bool $isNext,
    int $size,
    ?int $page = null
  ) : Collection;

  /**
   * Get the relationship between user and target users
   *
   * @param string $userId 
   * @param array $targetUserIds
   *
   * @return Collection           elements are \stdClass
   *                              - userId string            目标用户userId
   *                              - isFollowing bool    是否是给定用户的following
   *                              - followAt \Carbon\Carbon  给定用户关注目标用户的时间
   *                              - isFollower bool     是否是给定用户的follower
   *                              - followedAt ?\Carbon\Carbon  给定用户被目标用户关注的时间
   */
  public function getUserRelationship(string $userId, array $targetUserIds) : Collection;

  /**
   * Count specified user followers number
   *
   * @param string $userId
   *
   * @return int
   */
  public function countUserFollowers(string $userId) : int;

  /**
   * Get recommend user's followings
   * 
   * @param string $userId
   * @param ?string $id       for pagination
   * @param bool $isNext      for pagination
   * @param int $size         for pagination
   *
   * @return Collection       elements are \stdClass
   *                          - id string
   *                          - userId string
   *                          - followingUserId string
   *                          - isAutoRecommend bool
   */
  public function getRecommendUserFollowings (
    string $userId, ?string $id, bool $isNext, int $size
  ) : Collection;

  /**
   * Get recommend works by country for user who has no followings
   *
   * @param string $countryAbbr
   * @param ?string $id           for pagination
   * @param bool $isNext          for pagination
   * @param int $size             for pagination
   *
   * @return Collection           elements are \stdClass, properties as below:
   *                              - userId string
   *                              - countryAbbr string
   *                              - isAutoRecommend bool
   *                              - workIds array   elements are work id
   */
  public function getRecommendWorksByCountry(
    string $countryAbbr, ?string $id, bool $isNext, int $size
  ) : Collection;

  /**
   * @param string $userId
   * @param string $followUserId
   * @return bool
   *
   */
  public function addUnfollowed(string $userId, string $unfollowUserId) :bool ;

  /**
   * @param string $userId
   * @param string $followUserId
   * @return bool
   */
  public function isUnfollowedUser(string $userId, string $unfollowUserId) :bool ;

    /**
     * @param string $userId
     * @return array    elements are userIds
     */
  public function getFollowingUserIds(string $userId):array ;
}
