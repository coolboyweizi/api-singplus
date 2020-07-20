<?php

namespace SingPlus\Listeners\Works;

use SingPlus\Events\Works\WorkDeleted;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;
use SingPlus\Services\WorkService;

class DoCleanAfterDelete implements ShouldQueue
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
     * @param  WorkDeleted  $event
     * @return void
     */
    public function handle(WorkDeleted $event)
    {
        return $this->workService->doCleanAfterWorkDeleted($event->workId);
    }
}
