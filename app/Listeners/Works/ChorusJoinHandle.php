<?php

namespace SingPlus\Listeners\Works;

use SingPlus\Events\Works\WorkPublished;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;
use SingPlus\Services\WorkService;

class ChorusJoinHandle implements ShouldQueue
{
   /**
    * @var WorkService
    */
    private $workService;

    /**
     * Create the event listener.
     *
     * @return void
     */
    public function __construct(WorkService $workService)
    {
      $this->workService = $workService;
    }

    /**
     * Handle the event.
     *
     * @param  WorkPublished  $event
     * @return void
     */
    public function handle(WorkPublished $event)
    {
      return $this->workService->handleChorusJoinWorkPublished($event->workId);
    }
}
