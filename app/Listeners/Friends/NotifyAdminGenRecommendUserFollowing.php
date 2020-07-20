<?php

namespace SingPlus\Listeners\Friends;

use SingPlus\Events\Friends\GetRecommendUserFollowingAction;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;
use SingPlus\Services\AdminGatewayService;

class NotifyAdminGenRecommendUserFollowing implements ShouldQueue
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
     * @param  GetRecommendUserFollowingAction  $event
     * @return void
     */
    public function handle(GetRecommendUserFollowingAction $event)
    {
      return $this->gatewayService->notifyGenRecommendUserFollowing($event->userId);
    }
}
