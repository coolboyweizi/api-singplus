<?php

namespace SingPlus\Listeners\Friends;

use SingPlus\Events\Friends\UserFollowed;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;
use SingPlus\Services\AdminGatewayService;

class NotifyAdminUserFollowAction implements ShouldQueue
{
    /**
     * @var AdminGatewayService
     */
    private $gatewayService;

    /**
     * Create the event listener.
     *
     * @return void
     */
    public function __construct(AdminGatewayService $gatewayService)
    {
      $this->gatewayService = $gatewayService;
    }

    /**
     * Handle the event.
     *
     * @param  UserFollowed  $event
     * @return void
     */
    public function handle(UserFollowed $event)
    {
      return $this->gatewayService
                  ->notifyUserFollowAction($event->userId, $event->followedUserId);
    }
}
