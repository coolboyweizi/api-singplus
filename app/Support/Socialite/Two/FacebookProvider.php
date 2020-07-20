<?php

namespace SingPlus\Support\Socialite\Two;

use Laravel\Socialite\Two\FacebookProvider as BaseFacebookProvider;

class FacebookProvider extends BaseFacebookProvider
{
  /**
   * The user fields being requested.
   *
   * @var array
   */
  protected $fields = [
    'name', 'email', 'gender', 'verified', 'link', 'token_for_business', 'ids_for_business',
  ];

  /**
   * {@inheritdoc}
   */
  protected function mapUserToObject(array $user)
  {
    $user = parent::mapUserToObject($user);
    return $user->map([
      'unionToken'  => $user['token_for_business'],
    ]);
  }
}
