<?php

namespace SingPlus\Support\Logs\Facades;

use Illuminate\Support\Facades\Facade;

/**
 * @see \SingPlus\SMS\SMSManager
 */
class TUDC extends Facade
{
  /**
   * Get the registered name of the component
   *
   * @return string
   */
  public static function getFacadeAccessor()
  {
    return 'log.tudc';
  }
}
