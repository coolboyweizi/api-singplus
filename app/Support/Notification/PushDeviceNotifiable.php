<?php

namespace SingPlus\Support\Notification;

use SingPlus\Support\Notification\Notifiable;

class PushDeviceNotifiable extends Notifiable
{
  /**
   * @var string|array
   */
  private $target;

  public function __construct($target)
  {
    $this->target = $target;
  }

  /**
   * Route notification for the fcm channel.
   *
   * @return string|array
   */
  public function routeNotificationForFcm()
  {
    return $this->target;
  }

  public function getTarget()
  {
    return $this->target;
  }
}
