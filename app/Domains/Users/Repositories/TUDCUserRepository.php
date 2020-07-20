<?php

namespace SingPlus\Domains\Users\Repositories;

use Illuminate\Support\Collection;
use SingPlus\Domains\Users\Models\TUDCUser;

class TUDCUserRepository
{
  /**
   * @param string $openid
   *
   * @return ?TUDCUser
   */
  public function findOneByOpenid(string $appChannel, string $openid) : ?TUDCUser
  {
    $key = sprintf('channels.%s.openid', $appChannel);
    return TUDCUser::where($key, $openid)->first();
  }

  /**
   * Upsert tudc user info by channel
   *
   * @param string $appChannel
   * @param string $userId
   * @param string $openid
   * @param string $token
   */
  public function upsertTUDCUser(
    string $appChannel,
    string $userId,
    string $openid,
    string $token
  ) {
    $key = sprintf('channels.%s', $appChannel);
    $old = TUDCUser::where('user_id', $userId)->first();
    if ($old) {
      return TUDCUser::where('user_id', $userId)
              ->update([
                $key  => [
                  'openid'  => $openid,
                  'token'   => $token,
                ],
              ]);
    } else {
      TUDCUser::create([
        'user_id'   => $userId,
        'channels'  => [
          $appChannel => [
            'openid'  => $openid,
            'token'   => $token,
          ],
        ],
      ]);

      return true;
    }
  }
}
