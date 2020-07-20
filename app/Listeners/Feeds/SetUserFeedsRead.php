<?php

namespace SingPlus\Listeners\Feeds;

use SingPlus\Events\Feeds\FeedReaded;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;
use SingPlus\Services\FeedService;

class SetUserFeedsRead implements ShouldQueue
{
    /**
     * @var FeedService
     */
    private $feedService;

    /**
     * Create the event listener.
     *
     * @return void
     */
    public function __construct(FeedService $feedService)
    {
      $this->feedService = $feedService;
    }

    /**
     * Handle the event.
     *
     * @param  FeedReaded  $event
     * @return void
     */
    public function handle(FeedReaded $event)
    {
      return $this->feedService->setUserFeedsReaded($event->userId, $event->feedTypes);
    }
}
