<?php

namespace FeatureTest\SingPlus\Listeners\Feeds;

use Event;
use Mockery;
use Cache;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use FeatureTest\SingPlus\TestCase;
use FeatureTest\SingPlus\MongodbClearTrait;
use SingPlus\Events\UserCommentWork as UserCommentWorkEvent;
use SingPlus\Events\FeedCreated as FeedCreatedEvent;

class NotifyFeedToUserTest extends TestCase
{
  use MongodbClearTrait; 

  //============================================
  //      notify work comment
  //============================================
  public function testSuccess_CommentWork()
  {
    config([
      'tudc.currentChannel' => 'boomsing',
    ]);
    $user = factory(\SingPlus\Domains\Users\Models\User::class)->create([
      'boomsing_push_alias'  => 'abcdefg',
    ]);
    $userProfile = factory(\SingPlus\Domains\Users\Models\UserProfile::class)->create([
      'user_id'   => $user->id,
      'nickname'  => 'lisi',
    ]);
    $optUser = factory(\SingPlus\Domains\Users\Models\User::class)->create();
    $optUserProfile = factory(\SingPlus\Domains\Users\Models\UserProfile::class)->create([
      'user_id'   => $optUser->id,
      'nickname'  => 'zhangsan',
    ]);
    $music = factory(\SingPlus\Domains\Musics\Models\Music::class)->create([
      'name'  => 'Hello',
    ]);
    $work = factory(\SingPlus\Domains\Works\Models\Work::class)->create([
      'music_id'  => $music->id,
    ]);
    $comment = factory(\SingPlus\Domains\Works\Models\Comment::class)->create([
      'work_id' => $work->id,
      'status'  => 1,
    ]);
    $feed = factory(\SingPlus\Domains\Feeds\Models\Feed::class)->create([
      'user_id'           => $user->id,
      'operator_user_id'  => $optUser->id,
      'type'              => 'work_comment',
      'status'            => 1,
      'detail'            => [
        'work_id'     => $work->id,
        'comment_id'  => $comment->id,
      ],
    ]);

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
                       $notification['body'] == 'lisi, zhangsan commented on your song Hello' &&
                       $notification['click_action'] == 'MainActivity';
              }),
              Mockery::on(function ($data) {
                $data = $data->toArray();
                return array_get($data, 'type') == 'work_comment'; 
              }))
           ->andReturn($mockResponse);

    $this->app->singleton('fcm.sender', function($app) use($sender) {
      return $sender;
    });

    Event::fake();
    $event = new FeedCreatedEvent($feed->id);
    $res = $this->getListener()
                ->handle($event);
  }

  public function testSuccess_CommentOtherComment()
  {
    $user = factory(\SingPlus\Domains\Users\Models\User::class)->create([
      'push_alias'  => 'abcdefg',
    ]);
    $userProfile = factory(\SingPlus\Domains\Users\Models\UserProfile::class)->create([
      'user_id'   => $user->id,
      'nickname'  => 'lisi',
    ]);
    $optUser = factory(\SingPlus\Domains\Users\Models\User::class)->create();
    $optUserProfile = factory(\SingPlus\Domains\Users\Models\UserProfile::class)->create([
      'user_id'   => $optUser->id,
      'nickname'  => 'zhangsan',
    ]);
    $music = factory(\SingPlus\Domains\Musics\Models\Music::class)->create([
      'name'  => 'Hello',
    ]);
    $work = factory(\SingPlus\Domains\Works\Models\Work::class)->create([
      'music_id'  => $music->id,
    ]);
    $comment = factory(\SingPlus\Domains\Works\Models\Comment::class)->create([
      'work_id'     => $work->id,
      'comment_id'  => '1a1be119bd6f46c28810f6c3d0a91135',
      'status'      => 1,
    ]);
    $feed = factory(\SingPlus\Domains\Feeds\Models\Feed::class)->create([
      'user_id'           => $user->id,
      'operator_user_id'  => $optUser->id,
      'type'              => 'work_comment',
      'status'            => 1,
      'detail'            => [
        'work_id'     => $work->id,
        'comment_id'  => $comment->id,
      ],
    ]);

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
              return true;
                $notification = $notification->toArray();
                return ! isset($notification['title']) &&
                       $notification['body'] == 'lisi, zhangsan replied your comment' &&
                       $notification['click_action'] == 'MainActivity';
              }),
              Mockery::on(function ($data) {
                $data = $data->toArray();
                return array_get($data, 'type') == 'work_comment'; 
              }))
           ->andReturn($mockResponse);

    $this->app->singleton('fcm.sender', function($app) use($sender) {
      return $sender;
    });

    Event::fake();
    $event = new FeedCreatedEvent($feed->id);
    $res = $this->getListener()
                ->handle($event);
  }


    public function testSuccess_CommentWorkForJoinCollab()
    {
        config([
            'tudc.currentChannel' => 'boomsing',
        ]);
        $user = factory(\SingPlus\Domains\Users\Models\User::class)->create([
            'boomsing_push_alias'  => 'abcdefg',
        ]);
        $userProfile = factory(\SingPlus\Domains\Users\Models\UserProfile::class)->create([
            'user_id'   => $user->id,
            'nickname'  => 'lisi',
        ]);
        $optUser = factory(\SingPlus\Domains\Users\Models\User::class)->create();
        $optUserProfile = factory(\SingPlus\Domains\Users\Models\UserProfile::class)->create([
            'user_id'   => $optUser->id,
            'nickname'  => 'zhangsan',
        ]);
        $music = factory(\SingPlus\Domains\Musics\Models\Music::class)->create([
            'name'  => 'Hello',
        ]);
        $work = factory(\SingPlus\Domains\Works\Models\Work::class)->create([
            'music_id'  => $music->id,
        ]);
        $comment = factory(\SingPlus\Domains\Works\Models\Comment::class)->create([
            'work_id' => $work->id,
            'status'  => 1,
            'type'    => 1,
        ]);
        $feed = factory(\SingPlus\Domains\Feeds\Models\Feed::class)->create([
            'user_id'           => $user->id,
            'operator_user_id'  => $optUser->id,
            'type'              => 'work_comment',
            'status'            => 1,
            'detail'            => [
                'work_id'     => $work->id,
                'comment_id'  => $comment->id,
            ],
        ]);

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
                        $notification['body'] == 'lisi, zhangsan commented on your song Hello' &&
                        $notification['click_action'] == 'MainActivity';
                }),
                Mockery::on(function ($data) {
                    $data = $data->toArray();
                    return array_get($data, 'type') == 'work_comment';
                }))
            ->andReturn($mockResponse);

        $this->app->singleton('fcm.sender', function($app) use($sender) {
            return $sender;
        });

        Event::fake();
        $event = new FeedCreatedEvent($feed->id);
        $res = $this->getListener()
            ->handle($event);
    }

    public function testSuccess_CommentWorkForTransmit()
    {
        config([
            'tudc.currentChannel' => 'boomsing',
        ]);
        $user = factory(\SingPlus\Domains\Users\Models\User::class)->create([
            'boomsing_push_alias'  => 'abcdefg',
        ]);
        $userProfile = factory(\SingPlus\Domains\Users\Models\UserProfile::class)->create([
            'user_id'   => $user->id,
            'nickname'  => 'lisi',
        ]);
        $optUser = factory(\SingPlus\Domains\Users\Models\User::class)->create();
        $optUserProfile = factory(\SingPlus\Domains\Users\Models\UserProfile::class)->create([
            'user_id'   => $optUser->id,
            'nickname'  => 'zhangsan',
        ]);
        $music = factory(\SingPlus\Domains\Musics\Models\Music::class)->create([
            'name'  => 'Hello',
        ]);
        $work = factory(\SingPlus\Domains\Works\Models\Work::class)->create([
            'music_id'  => $music->id,
        ]);
        $comment = factory(\SingPlus\Domains\Works\Models\Comment::class)->create([
            'work_id' => $work->id,
            'status'  => 1,
            'type'    => 2,
        ]);
        $feed = factory(\SingPlus\Domains\Feeds\Models\Feed::class)->create([
            'user_id'           => $user->id,
            'operator_user_id'  => $optUser->id,
            'type'              => 'work_comment',
            'status'            => 1,
            'detail'            => [
                'work_id'     => $work->id,
                'comment_id'  => $comment->id,
            ],
        ]);

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
                        $notification['body'] == 'lisi, zhangsan commented on your song Hello' &&
                        $notification['click_action'] == 'MainActivity';
                }),
                Mockery::on(function ($data) {
                    $data = $data->toArray();
                    return array_get($data, 'type') == 'work_comment';
                }))
            ->andReturn($mockResponse);

        $this->app->singleton('fcm.sender', function($app) use($sender) {
            return $sender;
        });

        Event::fake();
        $event = new FeedCreatedEvent($feed->id);
        $res = $this->getListener()
            ->handle($event);
    }

    public function testFailed_CommentWorkWithUserPrefOff()
    {
        config([
            'tudc.currentChannel' => 'boomsing',
        ]);
        $user = factory(\SingPlus\Domains\Users\Models\User::class)->create([
            'boomsing_push_alias'  => 'abcdefg',
        ]);
        $userProfile = factory(\SingPlus\Domains\Users\Models\UserProfile::class)->create([
            'user_id'   => $user->id,
            'nickname'  => 'lisi',
            'preferences_conf' => [
                'notify_comment' => false,
            ]
        ]);
        $optUser = factory(\SingPlus\Domains\Users\Models\User::class)->create();
        $optUserProfile = factory(\SingPlus\Domains\Users\Models\UserProfile::class)->create([
            'user_id'   => $optUser->id,
            'nickname'  => 'zhangsan',
        ]);
        $music = factory(\SingPlus\Domains\Musics\Models\Music::class)->create([
            'name'  => 'Hello',
        ]);
        $work = factory(\SingPlus\Domains\Works\Models\Work::class)->create([
            'music_id'  => $music->id,
        ]);
        $comment = factory(\SingPlus\Domains\Works\Models\Comment::class)->create([
            'work_id' => $work->id,
            'status'  => 1,
        ]);
        $feed = factory(\SingPlus\Domains\Feeds\Models\Feed::class)->create([
            'user_id'           => $user->id,
            'operator_user_id'  => $optUser->id,
            'type'              => 'work_comment',
            'status'            => 1,
            'detail'            => [
                'work_id'     => $work->id,
                'comment_id'  => $comment->id,
            ],
        ]);

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
                        $notification['body'] == 'lisi, zhangsan commented on your song Hello' &&
                        $notification['click_action'] == 'MainActivity';
                }),
                Mockery::on(function ($data) {
                    $data = $data->toArray();
                    return array_get($data, 'type') == 'work_comment';
                }))
            ->andReturn($mockResponse);

        $this->app->singleton('fcm.sender', function($app) use($sender) {
            return $sender;
        });

        Event::fake();
        $event = new FeedCreatedEvent($feed->id);
        $res = $this->getListener()
            ->handle($event);
    }


  //============================================
  //      notify work transmit
  //============================================
  public function testSuccess_TransmitWork()
  {
    $user = factory(\SingPlus\Domains\Users\Models\User::class)->create([
      'push_alias'  => 'abcdefg',
    ]);
    $userProfile = factory(\SingPlus\Domains\Users\Models\UserProfile::class)->create([
      'user_id'   => $user->id,
      'nickname'  => 'lisi',
    ]);
    $optUser = factory(\SingPlus\Domains\Users\Models\User::class)->create();
    $optUserProfile = factory(\SingPlus\Domains\Users\Models\UserProfile::class)->create([
      'user_id'   => $optUser->id,
      'nickname'  => 'zhangsan',
    ]);
    $music = factory(\SingPlus\Domains\Musics\Models\Music::class)->create([
      'name'  => 'Hello',
    ]);
    $work = factory(\SingPlus\Domains\Works\Models\Work::class)->create([
      'music_id'  => $music->id,
    ]);
    $comment = factory(\SingPlus\Domains\Works\Models\Comment::class)->create([
      'work_id' => $work->id,
      'status'  => 1,
    ]);
    $feed = factory(\SingPlus\Domains\Feeds\Models\Feed::class)->create([
      'user_id'           => $user->id,
      'operator_user_id'  => $optUser->id,
      'type'              => 'work_transmit',
      'status'            => 1,
      'detail'            => [
        'work_id'     => $work->id,
        'music_id'    => $music->id,
        'channel'     => 'facebook',
      ],
    ]);
    Cache::shouldReceive('add')
         ->once()
         ->with(sprintf('notify:lock:%s:%s', $user->id, 'work_transmit'), 'work_transmit', 10)
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
                       $notification['body'] == 'lisi, zhangsan shared your song Hello' &&
                       $notification['click_action'] == 'MainActivity';
              }),
              Mockery::on(function ($data) {
                $data = $data->toArray();
                return array_get($data, 'type') == 'work_transmit'; 
              }))
           ->andReturn($mockResponse);

    $this->app->singleton('fcm.sender', function($app) use($sender) {
      return $sender;
    });

    Event::fake();
    $event = new FeedCreatedEvent($feed->id);
    $res = $this->getListener()
                ->handle($event);
  }

  public function testFailed_TransmitWorkCannotGetLock()
  {
    $user = factory(\SingPlus\Domains\Users\Models\User::class)->create([
      'push_alias'  => 'abcdefg',
    ]);
    $userProfile = factory(\SingPlus\Domains\Users\Models\UserProfile::class)->create([
      'user_id'   => $user->id,
      'nickname'  => 'lisi',
    ]);
    $optUser = factory(\SingPlus\Domains\Users\Models\User::class)->create();
    $optUserProfile = factory(\SingPlus\Domains\Users\Models\UserProfile::class)->create([
      'user_id'   => $optUser->id,
      'nickname'  => 'zhangsan',
    ]);
    $music = factory(\SingPlus\Domains\Musics\Models\Music::class)->create([
      'name'  => 'Hello',
    ]);
    $work = factory(\SingPlus\Domains\Works\Models\Work::class)->create([
      'music_id'  => $music->id,
    ]);
    $comment = factory(\SingPlus\Domains\Works\Models\Comment::class)->create([
      'work_id' => $work->id,
      'status'  => 1,
    ]);
    $feed = factory(\SingPlus\Domains\Feeds\Models\Feed::class)->create([
      'user_id'           => $user->id,
      'operator_user_id'  => $optUser->id,
      'type'              => 'work_transmit',
      'status'            => 1,
      'detail'            => [
        'work_id'     => $work->id,
        'music_id'    => $music->id,
        'channel'     => 'facebook',
      ],
    ]);
    Cache::shouldReceive('add')
         ->once()
         ->with(sprintf('notify:lock:%s:%s', $user->id, 'work_transmit'), 'work_transmit', 10)
         ->andReturn(false);

    // mock FCM for sending notification
    $numberSuccess = 1;
    $mockResponse = new \LaravelFCM\Mocks\MockDownstreamResponse($numberSuccess);
    
    $sender = Mockery::mock(\LaravelFCM\Sender\FCMSender::class);
    $sender->shouldReceive('sendTo')->never();

    $this->app->singleton('fcm.sender', function($app) use($sender) {
      return $sender;
    });

    Event::fake();
    $event = new FeedCreatedEvent($feed->id);
    $res = $this->getListener()
                ->handle($event);
  }

  //============================================
  //      notify work favourite
  //============================================
  public function testSuccess_FavouriteWork()
  {
    $user = factory(\SingPlus\Domains\Users\Models\User::class)->create([
      'push_alias'  => 'abcdefg',
    ]);
    $userProfile = factory(\SingPlus\Domains\Users\Models\UserProfile::class)->create([
      'user_id'   => $user->id,
      'nickname'  => 'lisi',
    ]);
    $optUser = factory(\SingPlus\Domains\Users\Models\User::class)->create();
    $optUserProfile = factory(\SingPlus\Domains\Users\Models\UserProfile::class)->create([
      'user_id'   => $optUser->id,
      'nickname'  => 'zhangsan',
    ]);
    $music = factory(\SingPlus\Domains\Musics\Models\Music::class)->create([
      'name'  => 'Hello',
    ]);
    $work = factory(\SingPlus\Domains\Works\Models\Work::class)->create([
      'music_id'  => $music->id,
    ]);
    $favourite = factory(\SingPlus\Domains\Works\Models\WorkFavourite::class)->create([
      'user_id' => $optUser->id,
      'work_id' => $work->id,
    ]);
    $feed = factory(\SingPlus\Domains\Feeds\Models\Feed::class)->create([
      'user_id'           => $user->id,
      'operator_user_id'  => $optUser->id,
      'type'              => 'work_favourite',
      'status'            => 1,
      'detail'            => [
        'work_id'     => $work->id,
        'favourit_id' => $favourite->id,
      ],
    ]);
    Cache::shouldReceive('add')
         ->once()
         ->with(sprintf('notify:lock:%s:%s', $user->id, 'work_favourite'), 'work_favourite', 10)
         ->andReturn(true);
    Cache::shouldReceive('get')
         ->once()
         ->with(sprintf('work:%s:listennum', $work->id))
         ->andReturn(10);

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
                       $notification['body'] == 'lisi, zhangsan liked your song Hello' &&
                       $notification['click_action'] == 'MainActivity';
              }),
              Mockery::on(function ($data) {
                $data = $data->toArray();
                return array_get($data, 'type') == 'work_favourite'; 
              }))
           ->andReturn($mockResponse);

    $this->app->singleton('fcm.sender', function($app) use($sender) {
      return $sender;
    });

    Event::fake();
    $event = new FeedCreatedEvent($feed->id);
    $res = $this->getListener()
                ->handle($event);
  }

  public function testFailed_FavouriteWorkCannotGetLock()
  {
    $user = factory(\SingPlus\Domains\Users\Models\User::class)->create([
      'push_alias'  => 'abcdefg',
    ]);
    $userProfile = factory(\SingPlus\Domains\Users\Models\UserProfile::class)->create([
      'user_id'   => $user->id,
      'nickname'  => 'lisi',
    ]);
    $optUser = factory(\SingPlus\Domains\Users\Models\User::class)->create();
    $optUserProfile = factory(\SingPlus\Domains\Users\Models\UserProfile::class)->create([
      'user_id'   => $optUser->id,
      'nickname'  => 'zhangsan',
    ]);
    $music = factory(\SingPlus\Domains\Musics\Models\Music::class)->create([
      'name'  => 'Hello',
    ]);
    $work = factory(\SingPlus\Domains\Works\Models\Work::class)->create([
      'music_id'  => $music->id,
    ]);
    $favourite = factory(\SingPlus\Domains\Works\Models\WorkFavourite::class)->create([
      'user_id' => $optUser->id,
      'work_id' => $work->id,
    ]);
    $feed = factory(\SingPlus\Domains\Feeds\Models\Feed::class)->create([
      'user_id'           => $user->id,
      'operator_user_id'  => $optUser->id,
      'type'              => 'work_favourite',
      'status'            => 1,
      'detail'            => [
        'work_id'     => $work->id,
        'favourit_id' => $favourite->id,
      ],
    ]);
    Cache::shouldReceive('add')
         ->once()
         ->with(sprintf('notify:lock:%s:%s', $user->id, 'work_favourite'), 'work_favourite', 10)
         ->andReturn(false);
    Cache::shouldReceive('get')->never();

    // mock FCM for sending notification
    $numberSuccess = 1;
    $mockResponse = new \LaravelFCM\Mocks\MockDownstreamResponse($numberSuccess);
    
    $sender = Mockery::mock(\LaravelFCM\Sender\FCMSender::class);
    $sender->shouldReceive('sendTo')->never();

    $this->app->singleton('fcm.sender', function($app) use($sender) {
      return $sender;
    });

    Event::fake();
    $event = new FeedCreatedEvent($feed->id);
    $res = $this->getListener()
                ->handle($event);
  }

    public function testFailed_FavouriteWorkWithUserPrefOff()
    {
        $user = factory(\SingPlus\Domains\Users\Models\User::class)->create([
            'push_alias'  => 'abcdefg',
        ]);
        $userProfile = factory(\SingPlus\Domains\Users\Models\UserProfile::class)->create([
            'user_id'   => $user->id,
            'nickname'  => 'lisi',
            'preferences_conf' => [
                'notify_favourite' => false,
            ]
        ]);
        $optUser = factory(\SingPlus\Domains\Users\Models\User::class)->create();
        $optUserProfile = factory(\SingPlus\Domains\Users\Models\UserProfile::class)->create([
            'user_id'   => $optUser->id,
            'nickname'  => 'zhangsan',
        ]);
        $music = factory(\SingPlus\Domains\Musics\Models\Music::class)->create([
            'name'  => 'Hello',
        ]);
        $work = factory(\SingPlus\Domains\Works\Models\Work::class)->create([
            'music_id'  => $music->id,
        ]);
        $favourite = factory(\SingPlus\Domains\Works\Models\WorkFavourite::class)->create([
            'user_id' => $optUser->id,
            'work_id' => $work->id,
        ]);
        $feed = factory(\SingPlus\Domains\Feeds\Models\Feed::class)->create([
            'user_id'           => $user->id,
            'operator_user_id'  => $optUser->id,
            'type'              => 'work_favourite',
            'status'            => 1,
            'detail'            => [
                'work_id'     => $work->id,
                'favourit_id' => $favourite->id,
            ],
        ]);
        Cache::shouldReceive('add')
            ->never()
            ->with(sprintf('notify:lock:%s:%s', $user->id, 'work_favourite'), 'work_favourite', 10)
            ->andReturn(true);
        Cache::shouldReceive('get')
            ->never()
            ->with(sprintf('work:%s:listennum', $work->id))
            ->andReturn(10);

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
                        $notification['body'] == 'lisi, zhangsan liked your song Hello' &&
                        $notification['click_action'] == 'MainActivity';
                }),
                Mockery::on(function ($data) {
                    $data = $data->toArray();
                    return array_get($data, 'type') == 'work_favourite';
                }))
            ->andReturn($mockResponse);

        $this->app->singleton('fcm.sender', function($app) use($sender) {
            return $sender;
        });

        Event::fake();
        $event = new FeedCreatedEvent($feed->id);
        $res = $this->getListener()
            ->handle($event);
    }

  //============================================
  //      notify work chorus joined
  //============================================
  public function testSuccess_JoinChorusWork()
  {
    $user = factory(\SingPlus\Domains\Users\Models\User::class)->create([
      'push_alias'  => 'abcdefg',
    ]);
    $userProfile = factory(\SingPlus\Domains\Users\Models\UserProfile::class)->create([
      'user_id'   => $user->id,
      'nickname'  => 'lisi',
    ]);
    $optUser = factory(\SingPlus\Domains\Users\Models\User::class)->create();
    $optUserProfile = factory(\SingPlus\Domains\Users\Models\UserProfile::class)->create([
      'user_id'   => $optUser->id,
      'nickname'  => 'zhangsan',
    ]);

    $feed = factory(\SingPlus\Domains\Feeds\Models\Feed::class)->create([
      'user_id'           => $user->id,
      'operator_user_id'  => $optUser->id,
      'type'              => 'work_chorus_join',
      'status'            => 1,
      'detail'            => [
        'work_id'               => 'b614bb9e863042c8a6603d8d47f63e61',
        'work_name'             => 'chorus-start',
        'work_chorus_join_id'   => 'fd2400c8e0f942f8bf7cca9deb58dd54',
        'work_chorus_join_name' => 'chorus-join',
        'work_chorus_join_description'  => 'morning!',
      ],
    ]);

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
                       $notification['body'] == 'lisi, zhangsan joined your collab' &&
                       $notification['click_action'] == 'MainActivity';
              }),
              Mockery::on(function ($data) {
                $data = $data->toArray();
                return array_get($data, 'type') == 'work_chorus_join'; 
              }))
           ->andReturn($mockResponse);

    $this->app->singleton('fcm.sender', function($app) use($sender) {
      return $sender;
    });

    Event::fake();
    $event = new FeedCreatedEvent($feed->id);
    $res = $this->getListener()
                ->handle($event);
  }

    //============================================
    //      notify send gift to work
    //============================================
    public function testSuccess_SendGiftToWork(){
        $user = factory(\SingPlus\Domains\Users\Models\User::class)->create([
            'push_alias'  => 'abcdefg',
        ]);
        $userProfile = factory(\SingPlus\Domains\Users\Models\UserProfile::class)->create([
            'user_id'   => $user->id,
            'nickname'  => 'lisi',
        ]);
        $optUser = factory(\SingPlus\Domains\Users\Models\User::class)->create();
        $optUserProfile = factory(\SingPlus\Domains\Users\Models\UserProfile::class)->create([
            'user_id'   => $optUser->id,
            'nickname'  => 'zhangsan',
        ]);

        $giftHistory = factory(\SingPlus\Domains\Gifts\Models\GiftHistory::class)->create();

        $feed = factory(\SingPlus\Domains\Feeds\Models\Feed::class)->create([
            'user_id'           => $user->id,
            'operator_user_id'  => $optUser->id,
            'type'              => 'gift_send_for_work',
            'status'            => 1,
            'detail'            => [
                'work_id'               => 'b614bb9e863042c8a6603d8d47f63e61',
                'giftHistory_id'             => $giftHistory->id,
            ],
        ]);

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
                        $notification['body'] == 'lisi, zhangsan sent you some gifts. Check out now' &&
                        $notification['click_action'] == 'MainActivity';
                }),
                Mockery::on(function ($data) {
                    $data = $data->toArray();
                    return array_get($data, 'type') == 'gift_send_for_work';
                }))
            ->andReturn($mockResponse);

        $this->app->singleton('fcm.sender', function($app) use($sender) {
            return $sender;
        });

        Event::fake();
        $event = new FeedCreatedEvent($feed->id);
        $res = $this->getListener()
            ->handle($event);
    }

    public function testFailed_SendGiftToWorkWithPrefOff(){
        $user = factory(\SingPlus\Domains\Users\Models\User::class)->create([
            'push_alias'  => 'abcdefg',
        ]);
        $userProfile = factory(\SingPlus\Domains\Users\Models\UserProfile::class)->create([
            'user_id'   => $user->id,
            'nickname'  => 'lisi',
            'preferences_conf' => [
                'notify_gift' => false,
            ]
        ]);
        $optUser = factory(\SingPlus\Domains\Users\Models\User::class)->create();
        $optUserProfile = factory(\SingPlus\Domains\Users\Models\UserProfile::class)->create([
            'user_id'   => $optUser->id,
            'nickname'  => 'zhangsan',
        ]);

        $giftHistory = factory(\SingPlus\Domains\Gifts\Models\GiftHistory::class)->create();

        $feed = factory(\SingPlus\Domains\Feeds\Models\Feed::class)->create([
            'user_id'           => $user->id,
            'operator_user_id'  => $optUser->id,
            'type'              => 'gift_send_for_work',
            'status'            => 1,
            'detail'            => [
                'work_id'               => 'b614bb9e863042c8a6603d8d47f63e61',
                'giftHistory_id'             => $giftHistory->id,
            ],
        ]);

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
                        $notification['body'] == 'lisi, zhangsan sent you some gifts. Check out now' &&
                        $notification['click_action'] == 'MainActivity';
                }),
                Mockery::on(function ($data) {
                    $data = $data->toArray();
                    return array_get($data, 'type') == 'gift_send_for_work';
                }))
            ->andReturn($mockResponse);

        $this->app->singleton('fcm.sender', function($app) use($sender) {
            return $sender;
        });

        Event::fake();
        $event = new FeedCreatedEvent($feed->id);
        $res = $this->getListener()
            ->handle($event);
    }


  private function getListener()
  {
    return $this->app->make(\SingPlus\Listeners\Feeds\NotifyFeedToUser::class);
  }
}
