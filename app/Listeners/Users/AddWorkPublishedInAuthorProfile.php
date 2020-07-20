<?php

namespace SingPlus\Listeners\Users;

use SingPlus\Events\Works\WorkPublished;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;
use SingPlus\Services\UserService;

class AddWorkPublishedInAuthorProfile implements ShouldQueue
{
    /**
     * @var UserService
     */
    private $userService;

    /**
     * Create the event listener.
     *
     * @return void
     */
    public function __construct(UserService $userService)
    {
      $this->userService = $userService;
    }

    /**
     * Handle the event.
     *
     * @param  WorkPublished  $event
     * @return void
     */
    public function handle(WorkPublished $event)
    {
      return $this->userService->saveUserLatestWorkPublishedInfo($event->workId);
    }
}
