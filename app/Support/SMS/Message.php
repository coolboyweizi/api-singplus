<?php

namespace SingPlus\SMS;

use Carbon\Carbon;

class Message
{
  /**
   * @var string
   */
  private $from;

  /**
   * Should be mobile number with country code
   *
   * @var string
   */
  private $to;

  /**
   * @var string
   */
  private $body;

  /**
   * Set message send time. null indicate message should be send immediately
   *
   * @var Carbon
   */
  private $sendAt;

  public function getFrom() : ?string
  {
    return $this->from;
  }

  public function from(?string $mobile)
  {
    $this->from = $mobile;
    return $this;
  }

  public function getTo() : array
  {
    return $this->to;
  }

  public function to($mobiles)
  {
    $this->to = (array) $mobiles;
    return $this;
  }

  public function getBody() : string
  {
    return $this->body;
  }

  public function body(string $body)
  {
    $this->body = $body;
    return $this; 
  }
}
