<?php

namespace SingPlus\Domains\Users\Repositories;

use SingPlus\Domains\Users\Models\User;

class UserRepository
{
  /**
   * @param string $userId    user id
   */
  public function findOneById(string $userId)
  {
    return User::find($userId);
  }

  /**
   * @param string $mobile
   */
  public function findOneByMobile(string $mobile)
  {
    return User::where('mobile', $mobile)->first();
  }

  /**
   * @param string $alias     push alias
   */
  public function findOneByPushAlias(string $appChannel, string $alias) : ?User
  {
    $aliasKeyName = $appChannel == config('tudc.defaultChannel')
                ? 'push_alias' : sprintf('%s_push_alias', $appChannel);
    return User::where($aliasKeyName, $alias)->first();
  }
}
