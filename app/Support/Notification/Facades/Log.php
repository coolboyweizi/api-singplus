<?php

namespace SingPlus\Support\Notification\Facades;

use Illuminate\Support\Facades\Facade;

/**
 * @see \SingPlus\SMS\SMSManager
 */
class Log extends Facade
{
  /**
   * Get the registered name of the component
   *
   * @return string
   */
  public static function getFacadeAccessor()
  {
    return 'log.notification';
  }
}
