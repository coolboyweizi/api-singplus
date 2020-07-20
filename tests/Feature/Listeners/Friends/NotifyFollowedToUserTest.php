<?php

namespace FeatureTest\SingPlus\Listeners\Feeds;

use Event;
use Mockery;
use Cache;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use FeatureTest\SingPlus\TestCase;
use FeatureTest\SingPlus\MongodbClearTrait;
use SingPlus\Events\Friends\UserFollowed as UserFollowedEvent;

class NotifyFollowedToUserTest extends TestCase
{
  use MongodbClearTrait; 
  
  //============================================
  //      notify user followed
  //============================================
  public function testSuccesss()
  {
    $user = factory(\SingPlus\Domains\Users\Models\User::class)->create();
    $userProfile = factory(\SingPlus\Domains\Users\Models\UserProfile::class)->create([
      'user_id'   => $user->id,
      'nickname'  => 'zhangsan',
    ]);
    $followedUser = factory(\SingPlus\Domains\Users\Models\User::class)->create([
      'push_alias'  => 'abcdefg',
    ]);
    $followedUserProfile = factory(\SingPlus\Domains\Users\Models\UserProfile::class)->create([
      'user_id'   => $followedUser->id,
      'nickname'  => 'lisi',
    ]);

    Cache::shouldReceive('add')
         ->once()
         ->with(sprintf('notify:lock:%s:%s', $followedUser->id, 'friend_follow'), 'friend_follow', 10)
         ->andReturn(true);

    // mock FCM for sending notification
    $numberSuccess = 1;
    $mockResponse = new \LaravelFCM\Mocks\MockDownstreamResponse($numberSuccess);
    
    $sender = Mockery::mock(\LaravelFCM\Sender\FCMSender::class);
    $sender->shouldReceive('sendTo')
           ->once()
           ->with(
              'abcdefg',
              Mockery::any(),
              Mockery::on(function ($notification) {
                $notification = $notification->toArray();
                return $notification['title'] == 'Sing+' &&
                       $notification['body'] == 'lisi, zhangsan started following you' &&
                       $notification['click_action'] == 'MainActivity';
              }),
              Mockery::on(function ($data) {
                $data = $data->toArray();
                return array_get($data, 'type') == 'friend_follow'; 
              }))
           ->andReturn($mockResponse);

    $this->app->singleton('fcm.sender', function($app) use($sender) {
      return $sender;
    });

    Event::fake();
    $event = new UserFollowedEvent($user->id, $followedUser->id);
    $res = $this->getListener()
                ->handle($event);
  }

  public function testFailed_getLockFailed()
  {
    $user = factory(\SingPlus\Domains\Users\Models\User::class)->create();
    $userProfile = factory(\SingPlus\Domains\Users\Models\UserProfile::class)->create([
      'user_id'   => $user->id,
      'nickname'  => 'zhangsan',
    ]);
    $followedUser = factory(\SingPlus\Domains\Users\Models\User::class)->create([
      'push_alias'  => 'abcdefg',
    ]);
    $followedUserProfile = factory(\SingPlus\Domains\Users\Models\UserProfile::class)->create([
      'user_id'   => $followedUser->id,
      'nickname'  => 'lisi',
    ]);

    Cache::shouldReceive('add')
         ->once()
         ->with(sprintf('notify:lock:%s:%s', $followedUser->id, 'friend_follow'), 'friend_follow', 10)
         ->andReturn(false);

    // mock FCM for sending notification
    $numberSuccess = 1;
    $mockResponse = new \LaravelFCM\Mocks\MockDownstreamResponse($numberSuccess);
    
    $sender = Mockery::mock(\LaravelFCM\Sender\FCMSender::class);
    $sender->shouldReceive('sendTo')->never();

    $this->app->singleton('fcm.sender', function($app) use($sender) {
      return $sender;
    });

    $event = new UserFollowedEvent($user->id, $followedUser->id);
    $res = $this->getListener()
                ->handle($event);
  }

    public function testFailed_UserPrefOff()
    {
        $user = factory(\SingPlus\Domains\Users\Models\User::class)->create();
        $userProfile = factory(\SingPlus\Domains\Users\Models\UserProfile::class)->create([
            'user_id'   => $user->id,
            'nickname'  => 'zhangsan',
        ]);
        $followedUser = factory(\SingPlus\Domains\Users\Models\User::class)->create([
            'push_alias'  => 'abcdefg',
        ]);
        $followedUserProfile = factory(\SingPlus\Domains\Users\Models\UserProfile::class)->create([
            'user_id'   => $followedUser->id,
            'nickname'  => 'lisi',
            'preferences_conf' => [
                'notify_followed' => false,
            ]
        ]);

        Cache::shouldReceive('add')
            ->never()
            ->with(sprintf('notify:lock:%s:%s', $followedUser->id, 'friend_follow'), 'friend_follow', 10)
            ->andReturn(true);

        // mock FCM for sending notification
        $numberSuccess = 1;
        $mockResponse = new \LaravelFCM\Mocks\MockDownstreamResponse($numberSuccess);

        $sender = Mockery::mock(\LaravelFCM\Sender\FCMSender::class);
        $sender->shouldReceive('sendTo')
            ->never()
            ->with(
                'abcdefg',
                Mockery::any(),
                Mockery::on(function ($notification) {
                    $notification = $notification->toArray();
                    return $notification['title'] == 'Sing+' &&
                        $notification['body'] == 'lisi, zhangsan started following you' &&
                        $notification['click_action'] == 'MainActivity';
                }),
                Mockery::on(function ($data) {
                    $data = $data->toArray();
                    return array_get($data, 'type') == 'friend_follow';
                }))
            ->andReturn($mockResponse);

        $this->app->singleton('fcm.sender', function($app) use($sender) {
            return $sender;
        });

        Event::fake();
        $event = new UserFollowedEvent($user->id, $followedUser->id);
        $res = $this->getListener()
            ->handle($event);
    }

  private function getListener()
  {
    return $this->app->make(\SingPlus\Listeners\Friends\NotifyFollowedToUser::class);
  }
}
