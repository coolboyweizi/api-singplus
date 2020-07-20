<?php

namespace SingPlus\Listeners\Feeds;

use SingPlus\Events\Friends\UserFollowed;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;
use SingPlus\Services\FeedService;

class CreateUserFollowedFeed implements ShouldQueue
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
     * @param  UserFollowed  $event
     * @return void
     */
    public function handle(UserFollowed $event)
    {
      return $this->feedService->createUserFollowedFeed($event->userId, $event->followedUserId);
    }
}
