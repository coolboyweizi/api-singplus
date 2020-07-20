<?php

namespace FeatureTest\SingPlus;

trait NationOperationTrait
{
  /**
   * @before
   */
  public function disableNationOperationMiddleware()
  {
    $this->app->instance('middleware.nationality.disable', true);

    return $this;
  }

  public function enableNationOperationMiddleware()
  {
    $this->app->instance('middleware.nationality.disable', false);

    return $this;
  }
}
