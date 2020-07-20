<?php

namespace SingPlus\Listeners\Feeds;

use SingPlus\Events\UserTriggerFavouriteWork;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;
use SingPlus\Services\FeedService;

class CreateWorkFavouriteFeed implements ShouldQueue
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
     * @param  UserTriggerFavouriteWork  $event
     * @return string                    feed id
     */
    public function handle(UserTriggerFavouriteWork $event) : ?string
    {
      return $this->feedService->createWorkFavouriteFeed($event->favouriteId, $event->action);
    }
}
