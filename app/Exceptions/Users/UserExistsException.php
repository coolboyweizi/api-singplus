<?php

namespace SingPlus\Exceptions\Users;

use SingPlus\Exceptions\AppException;
use SingPlus\Exceptions\ExceptionCode;

class UserExistsException extends AppException
{
  /**
   * @var string
   */
  private $mobile;

  public function __construct(string $mobile, string $message = 'user aready exists')
  {
    $this->mobile = $mobile; 

    parent::__construct($message, ExceptionCode::USER_EXISTS);
  }

  public function getMobile() : string
  {
    return $this->mobile;
  }
}
