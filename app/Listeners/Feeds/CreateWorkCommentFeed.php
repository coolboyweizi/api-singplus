<?php

namespace SingPlus\Listeners\Feeds;

use SingPlus\Events\UserCommentWork;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;
use SingPlus\Services\FeedService;

class CreateWorkCommentFeed implements ShouldQueue
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
     * @param  UserCommentWork  $event
     * @return void
     */
    public function handle(UserCommentWork $event)
    {
      return $this->feedService->createWorkCommentFeed($event->commentId, $event->action);
    }
}
