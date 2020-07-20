<?php

namespace FeatureTest\SingPlus\Listeners\Feeds;

use Mockery;
use Cache;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use FeatureTest\SingPlus\TestCase;
use FeatureTest\SingPlus\MongodbClearTrait;
use SingPlus\Events\Friends\UserFollowed as UserFollowedEvent;
use SingPlus\Events\Friends\UserTriggerFollowed;
use SingPlus\Events\Friends\UserUnfollowed as UserUnfollowedEvent;

class UpdateUserFollowCountTest extends TestCase
{
  use MongodbClearTrait; 
  
  //============================================
  //      triggered by UserFollow event
  //============================================
  public function testSuccess_handleUserFollowedEvent()
  {
    $user = factory(\SingPlus\Domains\Users\Models\User::class)->create();
    $userProfile = factory(\SingPlus\Domains\Users\Models\UserProfile::class)->create([
      'user_id' => $user->id,
    ]);
    $followedUser = factory(\SingPlus\Domains\Users\Models\User::class)->create();
    $followedUserProfile = factory(\SingPlus\Domains\Users\Models\UserProfile::class)->create([
      'user_id' => $followedUser->id,
    ]);

    $event = new UserFollowedEvent($user->id, $followedUser->id);
    $res = $this->getListener()
                ->handle($event);
    self::assertTrue($res);

    self::assertDatabaseHas('user_profiles', [
      'user_id'         => $user->id,
      'following_count' => 1,
    ]);
    self::assertDatabaseHas('user_profiles', [
      'user_id'         => $followedUser->id,
      'follower_count'  => 1,
    ]);
  }

  //============================================
  //      triggered by UserUnfollow event
  //============================================
  public function testSuccess_handleUserUnfollowedEvent()
  {
    $user = factory(\SingPlus\Domains\Users\Models\User::class)->create();
    $userProfile = factory(\SingPlus\Domains\Users\Models\UserProfile::class)->create([
      'user_id'         => $user->id,
      'following_count' => 10,
    ]);
    $followedUser = factory(\SingPlus\Domains\Users\Models\User::class)->create();
    $followedUserProfile = factory(\SingPlus\Domains\Users\Models\UserProfile::class)->create([
      'user_id'         => $followedUser->id,
      'follower_count'  => 4,
    ]);

    $event = new UserUnfollowedEvent($user->id, $followedUser->id);
    $res = $this->getListener()
                ->handle($event);
    self::assertTrue($res);

    self::assertDatabaseHas('user_profiles', [
      'user_id'         => $user->id,
      'following_count' => 9,
    ]);
    self::assertDatabaseHas('user_profiles', [
      'user_id'         => $followedUser->id,
      'follower_count'  => 3,
    ]);
  }

    //============================================
    //      triggered by UserFollow event
    //============================================
    public function testSuccess_handleUserTriggerFollowedEvent()
    {
        $user = factory(\SingPlus\Domains\Users\Models\User::class)->create();
        $userProfile = factory(\SingPlus\Domains\Users\Models\UserProfile::class)->create([
            'user_id' => $user->id,
        ]);
        $followedUser = factory(\SingPlus\Domains\Users\Models\User::class)->create();
        $followedUserProfile = factory(\SingPlus\Domains\Users\Models\UserProfile::class)->create([
            'user_id' => $followedUser->id,
        ]);

        $event = new UserTriggerFollowed($user->id, $followedUser->id);
        $res = $this->getListener()
            ->handle($event);
        self::assertTrue($res);

        self::assertDatabaseHas('user_profiles', [
            'user_id'         => $user->id,
            'following_count' => 1,
        ]);
        self::assertDatabaseHas('user_profiles', [
            'user_id'         => $followedUser->id,
            'follower_count'  => 1,
        ]);
    }

  private function getListener()
  {
    return $this->app->make(\SingPlus\Listeners\Friends\UpdateUserFollowCount::class);
  }
}
