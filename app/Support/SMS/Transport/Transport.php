<?php

namespace SingPlus\SMS\Transport;

use Illuminate\Foundation\Application;
use Illuminate\Support\Collection;
use SingPlus\SMS\Message;
use SingPlus\SMS\PendingSMS;
use SingPlus\SMS\Balance;

abstract class Transport
{
  /**
   * @var Application
   */
  protected $app;

  /**
   * @var string
   */
  protected $transport;

  /**
   * @var string $from
   */
  protected $from;

  /**
   * @var string[]
   */
  protected $to;

  public function __construct(Application $app, string $transport)
  {
    $this->app = $app;
    $this->transport = $transport;
  }

  /**
   * Send the given message
   *
   * @param Message $message              message which will be send
   * @param Collection $failedRecipients  an collection of failures by-reference
   *                                      elements are failed phone number
   *
   * @return int                        the number of recipients who were accepted for delivery
   */
  abstract public function send(Message $message, Collection &$failedRecipients = null) : int;

  /**
   * Get transport balance
   *
   * @return Balance
   */
  abstract public function balance() : Balance;
}
