<?php

namespace SingPlus\Domains\Users\Repositories;

use Carbon\Carbon;
use Illuminate\Support\Collection;
use SingPlus\Domains\Users\Models\UserProfile;

class UserProfileRepository
{
  /**
   * @param string $userId
   *
   * @return ?UserProfileContract
   */
  public function findOneByUserId(string $userId) : ?UserProfile
  {
    return UserProfile::where('user_id', $userId)->first();
  }

  /**
   * @param string $nickname
   *
   * @return ?UserProfile
   */
  public function findOneByNickname(string $nickname) : ?UserProfile
  {
    return UserProfile::where('nickname', $nickname)->first();
  }

  /**
   * @param array $userIds
   *
   * @return Collection         elements are UserProfileContract
   */
  public function findAllByUserIds(array $userIds) : Collection
  {
    if (empty($userIds)) {
      return collect();
    }
    return UserProfile::whereIn('user_id', $userIds)->get();
  }

  /**
   * @param string $nickname        using $nickname for regexp search
   * @param int $size               limit number will be fetch
   *
   * @return Collection             elements are UserProfile
   */
  public function findAllBySearchNickname(string $nickname, int $size) : Collection
  {
    $pattern = sprintf('/%s/i', $nickname);
    return UserProfile::where('nickname', 'regexp', $pattern)
                      ->take($size)
                      ->get();
  }

  /**
   * @param string $userId
   * @param ?string $version          client version
   * @param Carbon $lastVisitedAt
   */
  public function updateLastVisitInfo(string $userId, ?string $version, Carbon $lastVisitedAt)
  {
    return UserProfile::where('user_id', $userId)
                      ->update([
                        'last_visit_at'   => $lastVisitedAt->format('Y-m-d H:i:s'),
                        'client_version'  => $version,
                      ]);
  }

  /**
   * @param string $userId
   * @param string $workId
   * @param Carbon $workPublishedAt
   */
  public function updateUserLatestWorkPublishedInfo(
    string $userId,
    string $workId,
    Carbon $workPublishedAt
  ) {
    return UserProfile::where('user_id', $userId)
                      ->update([
                        'statistics_info.latest_work_pub_at'  => $workPublishedAt->format('Y-m-d H:i:s'),
                        'statistics_info.latest_work_id'      => $workId,
                      ]);
  }

    /**
     * @param string $userId
     * @param ?string $version          client version
     * @param Carbon $lastVisitedAt
     */
    public function updateLastDailyTaskAt(string $userId,  Carbon $lastDailyTaskAt)
    {
        return UserProfile::where('user_id', $userId)
            ->update([
                'last_dailytask_at'   => $lastDailyTaskAt->format('Y-m-d H:i:s'),
            ]);
    }

    /**
     * Update user profile balance
     *
     * @param string $userId
     * @param int $amount           add amount to balance if amount is positive number,
     *                              or minus amount to balance if amount is negative number
     */
    public function updateUserBalance(string $userId, $amount)
    {
        return UserProfile::where('user_id', $userId)
                          ->increment('coins.balance', $amount);
    }

    /**
     * @param string $userId
     * @param $popularity
     * @return mixed
     */
    public function updateUserPopularity(string $userId, $popularity){
        return UserProfile::where('user_id', $userId)
            ->increment('popularity_info.work_popularity', $popularity);
    }

    /**
     * @param string $userId
     * @param $coins
     */
    public function incrGiftConsumeCoins(string $userId, $coins){
        UserProfile::where('user_id', $userId)
            ->increment('coins.gift_consume_amount', $coins);
    }

    /**
     * @param string $userId
     * @param $coins
     */
    public function incrTotalConsumeCoins(string $userId, $coins){
        UserProfile::where('user_id', $userId)
            ->increment('consume_coins_info.consume_coins', $coins);
    }

    /**
     * @param string $userId
     * @param string $name
     * @param $value
     * @return mixed
     */
    public function updatePreferencConf(string $userId, string $name, $value){
        $validPrefs = [
            'notifyComment'=>UserProfile::PREF_COMMENT,
            'notifyFavourite' => UserProfile::PREF_FAVOURITE,
            'notifyFollowed' =>UserProfile::PREF_FOLLOWED,
            'notifyGift' => UserProfile::PREF_GIFT,
            'notifyImMsg' =>UserProfile::PREF_IM_MSG,
            'privacyUnfollowedMsg' => UserProfile::PREF_UNFOLLOWED_MSG
        ];
        if (array_has($validPrefs, $name)){
            return UserProfile::where('user_id', $userId)
                ->update([
                    'preferences_conf.'.array_get($validPrefs, $name)  => $value,
                ]);
        }
    }
}
