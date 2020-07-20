<?php

namespace SingPlus\Listeners\Works;

use SingPlus\Events\Works\RankExpired;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;
use SingPlus\Services\AdminGatewayService;

class NotifyAdminUpdateWorkRankingList implements ShouldQueue
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
     * @param  RankExpired  $event
     * @return void
     */
    public function handle(RankExpired $event)
    {
      return $this->gatewayService->notifyUpdateWorkRanking($event->musicId);
    }
}
