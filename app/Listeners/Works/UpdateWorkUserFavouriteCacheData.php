<?php
/**
 * Created by PhpStorm.
 * User: karl
 * Date: 2018/4/4
 * Time: 下午4:12
 */

namespace SingPlus\Listeners\Works;

use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;
use SingPlus\Events\Works\WorkUpdateUserFavouriteCacheData;
use SingPlus\Services\WorkService;

class UpdateWorkUserFavouriteCacheData implements ShouldQueue
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
     * @param  WorkUpdateUserFavouriteCacheData  $event
     * @return void
     */
    public function handle(WorkUpdateUserFavouriteCacheData $event)
    {
        $this->workService->updateWorkFavouriteCache($event->workId, $event->userId, $event->isFavourite);
    }
}