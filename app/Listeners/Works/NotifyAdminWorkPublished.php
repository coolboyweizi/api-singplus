<?php

namespace SingPlus\Listeners\Works;

use SingPlus\Events\Works\WorkPublished;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;
use SingPlus\Services\AdminGatewayService;

class NotifyAdminWorkPublished implements ShouldQueue
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
     * @param  WorkPublished  $event
     * @return void
     */
    public function handle(WorkPublished $event)
    {
      return $this->gatewayService->notifyWorkPublished($event->workId);
    }
}
