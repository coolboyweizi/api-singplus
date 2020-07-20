<?php

namespace SingPlus\SMS\Facades;

use Illuminate\Support\Facades\Facade;

/**
 * @see \SingPlus\SMS\SMSManager
 */
class SMS extends Facade
{
  /**
   * Get the registered name of the component
   *
   * @return string
   */
  public static function getFacadeAccessor()
  {
    return 'sms';
  }
}
