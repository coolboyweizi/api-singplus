<?php

namespace SingPlus\SMS;

use Illuminate\Bus\Queueable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;
use SingPlus\SMS\Message;
use SingPlus\SMS\SMSer;

class SendQueuedMessage implements ShouldQueue
{
  use InteractsWithQueue;
  use Queueable;

  /**
   * @var Message
   */
  public $message;

  /**
   * @var string
   */
  public $transport;

  /**
   * Create a new job instance
   *
   * @param Message $message
   * @param string $transport
   *
   * @return void
   */
  public function __construct(Message $message, string $transport = null)
  {
    $this->message = $message;
    $this->transport = $transport;
  }

  /**
   * Execute the job
   */
  public function handle(SMSer $smser)
  {
    $smser->send($this->message, $this->transport);
  }
}
