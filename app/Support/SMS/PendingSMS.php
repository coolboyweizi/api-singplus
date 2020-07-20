<?php

namespace SingPlus\SMS;

use SingPlus\SMS\Message;
use SingPlus\SMS\SMSer;

class PendingSMS
{
  /**
   * @var SMSer
   */
  protected $smser;

  /**
   * the transport name
   *
   * @var string
   */
  protected $transport;

  /**
   * @var string
   */
  protected $from;

  /**
   * @var array
   */
  protected $to;

  /**
   * delay sending message after (n) seconds
   *
   * @var ?int
   */
  protected $delay;

  /**
   * Set the desired queue for message sending
   *
   * @var ?string
   */
  protected $queue;

  /**
   * Set the recipients of the message.
   *
   * @param
   */

  public function __construct(SMSer $smser)
  {
    $this->smser = $smser;
  }

  /**
   * Set the transport
   *
   * @param string $transport
   *
   * @return $this;
   */
  public function driver(string $transport)
  {
    $this->transport = $transport;
    return $this;
  }

  /**
   * Set the sender
   *
   * @param string $mobile
   *
   * @return $this
   */
  public function from(string $mobile)
  {
    $this->from = $mobile;
    return $this;
  }

  /**
   * Set the recipients of the messages
   *
   * @param string|string[] $mobiles
   *
   * @return $this
   */
  public function to($mobiles)
  {
    $this->to = (array) $mobiles;
    return $this;
  }

  /**
   * Queue a message for sending after (n) seconds
   *
   * @param int $delay        delay seconds
   *
   * @return $this;
   */
  public function delay(int $seconds)
  {
    $this->delay = $seconds;
    return $this;
  }

  /**
   * Set the desired queue for the job
   *
   * @param string $queue
   *
   * @return $this
   */
  public function onQueue(string $queue)
  {
    $this->queue = $queue;
    return $this;
  }

  /**
   * Put a message in send queue
   *
   * @param SMSable $smsable
   *
   * @return int
   */
  public function send(string $view, array $data)
  {
    $message = $this->genMessage($view, $data);
    $queueable = new SendQueuedMessage($message, $this->transport);
    if ($this->delay) {
      $queueable->delay($this->delay);
    }
    if ($this->queue) {
      $queueable->onQueue($this->queue);
    }

    dispatch($queueable);
  }

  /**
   * Send message immediately
   *
   * @param string $view
   * @param array $data
   */
  public function sendNow(string $view, array $data) : int
  {
    $message = $this->genMessage($view, $data);
    return $this->smser->send($message);
  }

  /**
   * Generate message
   */
  public function genMessage(string $view, array $data = []) : Message
  {
    $body = $this->smser->genMessageBodyFromView($view, $data);
    return (new Message)->from($this->from)->to($this->to)->body($body);
  }

  public function __call($method, $parameters)
  {
    return $this->smser->getTransport($this->transport)->$method(...$parameters);
  }
}
