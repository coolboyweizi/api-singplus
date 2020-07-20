<?php

namespace FeatureTest\SingPlus;

trait AuthUserLastInfoTrait
{
  /**
   * @before
   */
  public function disableAuthUserLastVistInfoSaveInAuthenticateMiddleware()
  {
    $this->app->instance('middleware.authUser.lastVistInfoSave.disable', true);

    return $this;
  }

  public function enableAuthUserLastVistInfoSaveInAuthenticateMiddleware()
  {
    $this->app->instance('middleware.authUser.lastVistInfoSave.disable', false);

    return $this;
  }
}
