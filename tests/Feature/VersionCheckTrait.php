<?php

namespace FeatureTest\SingPlus;

trait VersionCheckTrait
{
  /**
   * @before
   */
  public function disableVersionCheckMiddleware()
  {
    $this->app->instance('middleware.version.check.disable', true);

    return $this;
  }

  public function enableVersionCheckMiddleware()
  {
    $this->app->instance('middleware.version.check.disable', false);

    return $this;
  }
}
