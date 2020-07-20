<?php

namespace FeatureTest\SingPlus;

trait RequestSignCheckTrait
{
  /**
   * @before
   */
  public function disableRequestSignCheckMiddleware()
  {
    $this->app->instance('middleware.request.sign.disable', true);

    return $this;
  }

  public function enableRequestSignCheckMiddleware()
  {
    $this->app->instance('middleware.request.sign.disable', false);

    return $this;
  }
}
