<?php

namespace SingPlus\Support\Database\Eloquent;

interface ModelInterface
{
  /**
   * Get mongodb identifier: _id
   */
  public function getId() : string;

}
