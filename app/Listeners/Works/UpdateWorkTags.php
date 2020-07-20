<?php
/**
 * Created by PhpStorm.
 * User: karl
 * Date: 2018/3/23
 * Time: 下午4:06
 */

namespace SingPlus\Listeners\Works;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;
use SingPlus\Events\Works\WorkUpdateTags;
use SingPlus\Services\WorkTagService;

class UpdateWorkTags implements ShouldQueue
{

    private $workTagService;

    /**
     * Create the event listener.
     *
     * @return void
     */
    public function __construct(WorkTagService $workTagService)
    {
        $this->workTagService = $workTagService;
    }

    /**
     * Handle the event.
     *
     * @param  WorkUpdateTags  $event
     * @return void
     */
    public function handle(WorkUpdateTags $event)
    {
        return $this->workTagService->updateWorkTags($event->workId);
    }
}