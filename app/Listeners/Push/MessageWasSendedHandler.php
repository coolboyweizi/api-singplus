<?php

namespace SingPlus\Listeners\Push;

use NotificationChannels\FCM\MessageWasSended;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;
use SingPlus\Services\NotificationService;

class MessageWasSendedHandler implements ShouldQueue
{
    /**
     * @var NotificationService
     */
    private $notificationService;

    /**
     * Create the event listener.
     *
     * @return void
     */
    public function __construct(NotificationService $notificationService)
    {
      $this->notificationService = $notificationService;
    }

    /**
     * Handle the event.
     *
     * @param  MessageWasSended  $event
     * @return void
     */
    public function handle(MessageWasSended $event)
    {
    /*
      $target = $event->notifiable->getTarget();

      return $this->notificationService
                  ->handleMessagePushed($target, $event->response);
                  */
    }
}
