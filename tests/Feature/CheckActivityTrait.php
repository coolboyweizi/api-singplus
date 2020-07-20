<?php

namespace FeatureTest\SingPlus;

trait CheckActivityTrait
{
    /**
     * @before
     */
    public function disableActvityCheckMiddleware()
    {
        $this->app->instance('middleware.activity.check.disable', true);

        return $this;
    }

    public function enableActivityCheckMiddleware()
    {
        $this->app->instance('middleware.activity.check.disable', false);

        return $this;
    }
}
