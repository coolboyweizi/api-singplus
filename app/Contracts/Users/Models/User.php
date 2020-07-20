<?php

namespace SingPlus\Contracts\Users\Models;

interface User
{
  /**
   * user mobile with country code
   */
  public function getMobile() : ?string;

  /**
   * user mobile country code
   */
  public function getCountryCode() : ?string;
}
