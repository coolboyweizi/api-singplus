<?php

namespace SingPlus\Listeners\Friends;

use SingPlus\Events\Friends\UserFollowed;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;
use SingPlus\Services\NotificationService;

class NotifyFollowedToUser implements ShouldQueue
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
     * @param  UserFollowed  $event
     * @return void
     */
    public function handle(UserFollowed $event)
    {
      return $this->notificationService
                  ->notifyFollowedToUser($event->userId, $event->followedUserId);
    }
}
