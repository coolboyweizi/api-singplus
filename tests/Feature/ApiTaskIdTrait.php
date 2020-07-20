<?php

namespace FeatureTest\SingPlus;

trait ApiTaskIdTrait
{
  /**
   * @before
   */
  public function disableApiTaskIdMiddleware()
  {
    $this->app->instance('middleware.request.taskid.disable', true);

    return $this;
  }

  public function enableApiTaskIdMiddleware()
  {
    $this->app->instance('middleware.request.taskid.disable', false);

    return $this;
  }
}
