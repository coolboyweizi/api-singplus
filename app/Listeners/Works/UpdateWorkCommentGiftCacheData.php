<?php
/**
 * Created by PhpStorm.
 * User: karl
 * Date: 2018/4/2
 * Time: 上午11:45
 */

namespace SingPlus\Listeners\Works;

use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;
use SingPlus\Events\Works\WorkUpdateCommentGiftCacheData;
use SingPlus\Services\WorkService;

class UpdateWorkCommentGiftCacheData implements ShouldQueue
{

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
     * @param  WorkUpdateCommentGiftCacheData  $event
     * @return void
     */
    public function handle(WorkUpdateCommentGiftCacheData $event)
    {
        $this->workService->updateWorkCommentGiftCacheData($event->workId, $event->senderId, $event->commentId);
    }
}