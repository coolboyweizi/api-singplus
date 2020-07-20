<?php

namespace SingPlus\Contracts\Users\Services;

use Carbon\Carbon;
use Illuminate\Support\Collection;
use SingPlus\Contracts\Users\Models\UserProfile as UserProfileContract;

interface UserProfileService
{
  /**
   * @param string $userId
   *
   * @return UserProfileContract
   *                            extra property
   *                            - work_listen_count int listen count
   *                            - work_count    int work count
   *                            - work_chorus_start_count    int
   *                            - popularity_herarchy   \stdClass ,properties as below
   *                                - name  string hierarchy name
   *                                - icon  string  url of icon
   *                                - iconSmall string
   *                                - alias string
   *                                - popularity int
   *                                - gapPopularity int
   *                            - wealth_herarchy       \stdClass, properties as below
   *                                - name  string hierachy name
   *                                - icon  string url of icon
   *                                - iconSmall string
   *                                - alias string
   *                                - consumeCoins  int
   *                                - gapCoins  int
   *                            - verified  \stdClass
   *                                - verified bool
   *                                - names array
   */
  public function fetchUserProfile(string $userId) : ?UserProfileContract;

  /**
   * @param string $userId
   * @param ?string $nickname
   * @param ?string $gender
   * @param ?string $signature  user custom signature
   * @param ?string $birthDate  user birth date
   */
  public function modifyUserProfile(
    string $userId,
    ?string $nickname = null,
    ?string $gender = null,
    ?string $signature = null,
    ?string $avatar = null,
    ?string $birthDate = null
  );

  /**
   * Complete user profile
   *
   * if avatarImageId and avatar exists both, only use avatarImageId
   *
   * @param string $userId
   * @param string $nickname
   */
  public function completeUserProfile(
    string $userId,
    string $nickname
  );

  /**
   * Get user profiles by ids
   *
   * @param array $userIds      elements are user id
   *
   * @return Collection         elements are \stdClass, properties as below
   *                            - userId string     user id
   *                            - nickname string   user nickname
   *                            - gender string     user gender
   *                            - avatar string     user avatar uri
   *                            - birthDate string  user birth date. Y-m-d
   *                            - signature string
   *                            - popularity_herarchy   \stdClass ,properties as below
   *                                - name  string hierarchy name
   *                                - icon  string  url of icon
   *                                - iconSmall string
   *                                - alias string
   *                                - popularity int
   *                                - gapPopularity int
   *                            - wealth_herarchy       \stdClass, properties as below
   *                                - name  string hierachy name
   *                                - icon  string url of icon
   *                                - iconSmall string
   *                                - alias string
   *                                - consumeCoins  int
   *                                - gapCoins  int
   *                            - verified  \stdClass
   *                                - verified bool
   *                                - names array
   */
  public function getUserProfiles(array $userIds) : Collection;
  public function getUserSimpleProfiles(array $userIds) : Collection;

  /**
   * Indicate whether nickname aready be used by other user
   *
   * @param string $userId
   * @param string $nickname
   *
   * @return bool
   */
  public function isNickNameUsedByOther(string $userId, string $nickname) : bool;

  /**
   * Indicate whether nickname aready be used
   *
   * @param string $nickname
   */
  public function isNickNameUsed(string $nickname) : bool;

  /**
   * Indicate whether user is new or not
   *
   * @param string $userId
   */
  public function isNewUser(string $userId) : bool;

  /**
   * @param string $userId
   * @param bool $isIncrement   true stands increase user following count, false or else
   *
   * @return bool
   */
  public function updateUserFollowingCount(string $userId, bool $isIncrement) : bool;

  /**
   * @param string $userId
   * @param bool $isIncrement   true stands increase user follower count, false or else
   *
   * @return bool
   */
  public function updateUserFollowerCount(string $userId, bool $isIncrement) : bool;

  /**
   * Get user profile by nickname, fetch max 50 users
   * @param string $nickname
   * @param int $size
   *
   * @return Collection           elements as below:
   *                              - userId string
   *                              - nickname string     user nickname
   *                              - avatar string       user avatar uri
   */
  public function searchUsersByNickname(string $nickname, int $size) : Collection;

  /**
   * Set user geo location
   */
  public function reportUserLocation(
    string $userId,
    ?string $longitude,
    ?string $latitude,
    ?string $countryCode,
    ?string $abbreviation,
    ?string $city,
    ?string $countryName,
    bool $auto
  );

  /**
   * Get user location
   *
   * @param string $userId
   *
   * @return ?\stdClass       elements as below:
   *                          - longitude string
   *                          - latitude string
   *                          - countryCode ?int
   *                          - abbreviation ?string
   */
  public function getUserLocation(string $userId) : ?\stdClass;

  /**
   * @param string $userId
   * @param ?\stdClass $info    elements as below:
   *                              - version ?string     client version
   *                              - lastVisitedAt Carbon\Carbon
   */
  public function updateUserLastVisitInfo(string $userId, \stdClass $info);

  /**
   * @param string $userId
   * @param string $workId
   * @param string $workPublishedAt
   */
  public function updateUserLatestWorkPublishedInfo(
    string $userId,
    string $workId, 
    Carbon $workPublishedAt
  );

    /**
     * Auto Complete user profile
     *
     * if avatarImageId and avatar exists both, only use avatarImageId
     *
     * @param string $userId
     */
    public function autoCompleteUserProfile(
        string $userId,
        ?string $nick,
        ?string $avatar
    );


    /**
     * @param string $userId
     * @param bool $isSync
     * @return int
     */
    public function updateUserImStatus(string $userId, bool $isSync) :int;


    /**
     * @param string $userId
     * @param string $prefName
     * @param bool $on
     * @return mixed
     */
    public function updateUserPreference(string $userId, string $prefName,  bool $on);
}
