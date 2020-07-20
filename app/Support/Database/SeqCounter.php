<?php

namespace SingPlus\Support\Database;

use Cache;

class SeqCounter
{
  const INC_STEP = 100;
  const PREFIX = 'counter:displayorder';

  /**
   * Get next sequence number, in order to generate a unique display order
   * for new mongodb document
   */
  public static function getNext(string $name) : int
  {
    return Cache::driver('counter')->increment($name, self::INC_STEP);
  }

  private static function genKey(string $name) : string
  {
    return sprintf('%s:%s', self::PREFIX, $name);
  }
}
