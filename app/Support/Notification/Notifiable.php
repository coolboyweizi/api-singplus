<?php

namespace SingPlus\Support\Notification;

use Illuminate\Notifications\Notifiable as BaseNotifiable;

abstract class Notifiable
{
  use BaseNotifiable;

  abstract public function getTarget();
}
