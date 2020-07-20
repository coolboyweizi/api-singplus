<?php

namespace SingPlus\Domains\Users\Repositories;

use Illuminate\Support\Collection;
use SingPlus\Domains\Users\Models\SocialiteUser;

class SocialiteUserç
{
  /**
   * @param string $socialiteUserId
   * @param string $provider
   *
   * @return ?SocialiteUser
   */
  public function findOneByProvider(
    string $appChannel,
    string $socialiteUserId,
    string $provider
  ) : ?SocialiteUser {
    return SocialiteUser::where('provider', $provider)
                        ->where(function ($query) use ($socialiteUserId, $appChannel) {
                          // 注意，这里使用socialite_user_id是为了兼容历史数据，只有
                          // socialite_user_id的情况
                          $key = sprintf('channels.%s.openid', $appChannel);
                          $query->where('socialite_user_id', $socialiteUserId)
                                ->orWhere($key, $socialiteUserId);
                        })
                        ->first();
  }

  /**
   * @param string $provider
   * @param string $unionToken
   *
   * @return ?SocialiteUser
   */
  public function findOneByUnionToken(string $provider, string $unionToken) : ?SocialiteUser
  {
    return SocialiteUser::where('provider', $provider)
                        ->where('union_token', $unionToken)
                        ->first();
  }

  /**
   * Update socialite user or create new socialite if not exists
   *
   * @param string $appChannel      such as singplus | boomsing
   * @param string $userId
   * @param string $provider        socialite provider
   * @param string $openid          socialite user id
   * @param string $token           socialite user access token
   * @param string $unionToken      union token for socialite user
   *                                such as token_for_business in facebook
   *
   * @return bool
   */
  public function upsertSocialiteUser(
    string $appChannel,
    string $userId,
    string $provider,
    string $openid,
    string $token,
    string $unionToken
  ) {
    $key = sprintf('channels.%s', $appChannel);
    $old = SocialiteUser::where('user_id', $userId)
                        ->where('provider', $provider)
                        ->first();
    if ($old) {
      return SocialiteUser::where('user_id', $userId)
                          ->where('provider', $provider)
                          ->update([
                            $key => [
                              'openid'  => $openid,
                              'token'   => $token,
                            ],
                            'union_token' => $unionToken,
                          ]);
    } else {
      SocialiteUser::create([
        'user_id'     => $userId,
        'provider'    => $provider,
        'channels'    => [
          $appChannel => [
            'openid'  => $openid,
            'token'   => $token,
          ],
        ],
        'union_token' => $unionToken,
      ]);

      return true;
    }
  }

  /**
   * Update socialite user or create new socialite if not exists
   *
   * @param string $appChannel      such as singplus | boomsing
   * @param string $userId
   * @param string $provider        socialite provider
   * @param string $openid          socialite user id
   * @param string $token           socialite user access token
   * @param string $unionToken      union token for socialite user
   *                                such as token_for_business in facebook
   *
   * @return bool
   */
  public function upsertStaleSocialiteUser(
    string $appChannel,
    string $userId,
    string $provider,
    string $openid,
    string $token,
    ?string $unionToken
  ) {
    $key = sprintf('channels.%s', $appChannel);
    $old = SocialiteUser::where('user_id', $userId)
                        ->where('provider', $provider)
                        ->first();
    if ($old) {
      return SocialiteUser::where('user_id', $userId)
                          ->where('provider', $provider)
                          ->where('socialite_user_id', $openid)
                          ->whereNull('channels')
                          ->update([
                            'channels' => [
                              $appChannel => [
                                'openid'  => $openid,
                                'token'   => $token,
                              ],
                            ],
                            'union_token' => $unionToken,
                            'socialite_user_id' => null,
                          ]);
    } else {
      SocialiteUser::create([
        'user_id'       => $userId,
        'provider'      => $provider,
        'channels'  => [
          $appChannel => [
            'openid'  => $openid,
            'token'   => $token,
          ],
        ],
        'unionToken'        => $unionToken,
        'socialite_user_id' => null,
      ]);
      return true;
    }
  }

  /**
   * @param array $socialiteUserIds     elements are socialite user id
   * @param string $provider
   *
   * @return Collection                 elements are SocialiteUser
   */
  public function findAllBySocialiteUserIdAndProvider(
    string $appChannel,
    array $socialiteUserIds,
    string $provider
  ) {
    if (empty($socialiteUserIds)) {
      return collect();
    }

    return SocialiteUser::where('provider', $provider)
                        ->where(function ($query) use ($socialiteUserIds, $appChannel) {
                          // 注意，这里使用socialite_user_id是为了兼容历史数据，只有
                          // socialite_user_id的情况
                          $key = sprintf('channels.%s.openid', $appChannel);
                          $query->whereIn('socialite_user_id', $socialiteUserIds)
                                ->orWhereIn($key, $socialiteUserIds);
                        })
                        ->get();
  }
}
