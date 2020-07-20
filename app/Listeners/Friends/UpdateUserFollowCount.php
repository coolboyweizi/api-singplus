<?php

namespace SingPlus\Listeners\Friends;

use SingPlus\Events\Friends\UserTriggerFollowed;
use SingPlus\Events\Friends\UserUnfollowed;
use SingPlus\Events\Friends\UserFollowed;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;
use SingPlus\Services\UserService;
use LogTXIM;

class UpdateUserFollowCount implements ShouldQueue
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
     * @param  UserFollowed|UserUnfollowed|UserTriggerFollowed  $event
     * @return void
     */
    public function handle($event)
    {
      LogTXIM::debug('UpdateUserFollowCount handle begin', []);
      if ($event instanceof UserFollowed) {
        $isFollow = true;
        $userId = $event->userId;
        $followUserId = $event->followedUserId;
          LogTXIM::debug('UpdateUserFollowCount handle userFollowed', []);
      } else if ($event instanceof UserTriggerFollowed){
          $isFollow = true;
          $userId = $event->userId;
          $followUserId = $event->followedUserId;
          LogTXIM::debug('UpdateUserFollowCount handle UserTriggerFollowed', []);
      }else if ($event instanceof UserUnfollowed) {
        $isFollow = false;
        $userId = $event->userId;
        $followUserId = $event->unfollowedUserId;
      } else {
        return null;
      }

      return $this->userService->updateUserFollowCount($userId, $followUserId, $isFollow);
    }
}
