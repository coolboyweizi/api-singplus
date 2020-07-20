<?php

namespace SingPlus\Listeners\Feeds;

use SingPlus\Events\FeedCreated;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;
use SingPlus\Services\NotificationService;

class NotifyFeedToUser implements ShouldQueue
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
     * @param  FeedCreated  $event
     * @return void
     */
    public function handle(FeedCreated $event)
    {
      return $this->notificationService->notifyFeedToUser($event->feedId);
    }
}
