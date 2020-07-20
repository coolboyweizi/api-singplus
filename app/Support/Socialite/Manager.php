<?php

namespace SingPlus\Support\Socialite;

use Laravel\Socialite\SocialiteManager;
use SingPlus\Support\Socialite\Two\FacebookProvider;

class Manager extends SocialiteManager
{
  /**
   * Create an instance of the specified driver.
   * For channel singplus
   *
   * @return \Laravel\Socialite\Two\AbstractProvider
   */
  protected function createFacebookDriver()
  {
    $config = $this->app['config']['services.facebook'];

    return $this->buildProvider(
      FacebookProvider::class, $config
    );
  }

  /**
   * Create an instance of the specified driver
   * For channel boomsing
   *
   * @return \Laravel\Socialite\Two\AbstractProvider
   */
  protected function createFacebookBoomsingDriver()
  {
    $config = $this->app['config']['services.facebook_boomsing'];

    return $this->buildProvider(
      FacebookProvider::class, $config
    );
  }
}
