<?php

namespace SingPlus\Exceptions\Feeds;

use SingPlus\Exceptions\AppException;
use SingPlus\Exceptions\ExceptionCode;

class FeedTransmitChannelInvalidException extends AppException
{
  public function __construct(string $message = 'transmit channel invalid')
  {
    parent::__construct($message, ExceptionCode::FEED_TRANSMIT_CHANNEL_INVALID);
  }
}
