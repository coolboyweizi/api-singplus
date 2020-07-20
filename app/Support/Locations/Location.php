<?php

namespace SingPlus\Support\Locations;

use Stevebauman\Location\Location as BaseLocation;

class Location extends BaseLocation
{
  /**
   * @override
   *
   * @param string $ip
   * @param bool $cache
   *
   * @return \Stevebauman\Location\Position|bool
   */
  public function get($ip = '', bool $cache = false)
  {
    if ($cache) {
      return parent::get($ip);
    } else {
      return $this->driver->get($ip ?: $this->getClientIP());
    }
  }
}
