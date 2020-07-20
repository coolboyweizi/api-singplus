<?php
/**
 * 1. vendor/enniel/laravel-fcm-notification-channel/src/FCMChannel.php
 * 2. vendor/laravel/framework/src/Illuminate/Notifications/NotificationSender.php
 * 3. vendor/enniel/laravel-fcm-notification-channel/src/ServiceProvider.php
 * 需要创建子类，继承FCMChannel::send()方法，返回response
 * 重鞋provider，使用新的FCMChannel子类
 * 现在可以正常使用NotificationSent event
 */

namespace FeatureTest\SingPlus\Controllers\Api;

use Bus;
use Illuminate\Support\Facades\Queue;
use Mockery;
use Cache;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use FeatureTest\SingPlus\TestCase;
use FeatureTest\SingPlus\MongodbClearTrait;
use SingPlus\Jobs\IMSendTopicMsg;

class NotificationControllerTest extends TestCase
{
  use MongodbClearTrait; 

  //=================================
  //       notifyAnnouncementCreated 
  //=================================
  public function testNotifyAnnouncementCreated()
  {
    \Event::fake();

    // mock FCM for sending notification
    $numberSuccess = 1;
    $mockResponse = new \LaravelFCM\Mocks\MockDownstreamResponse($numberSuccess);
    
    $sender = Mockery::mock(\LaravelFCM\Sender\FCMSender::class);
    $sender->shouldReceive('sendToTopic')
           ->once()
           ->with(
              Mockery::on(function(\LaravelFCM\Message\Topics $topic) {
                return $topic->build() == '/topics/topic_all_client';
              }),
              Mockery::any(),
              null,
              Mockery::on(function ($data) {
                $data = $data->toArray();
                return $data['type'] == 'topic_announcement' &&
                       $data['redirectTo'] == 'singplus://announcement';
              })
           )
           ->andReturn($mockResponse);

    $this->app->singleton('fcm.sender', function($app) use($sender) {
      return $sender;
    });

    $response = $this->postJson('api/notification/announcement')
                     ->assertJson(['code' => 0]);

    \Event::assertDispatched(
      \Illuminate\Notifications\Events\NotificationSent::class,
      function ($event) {
        return $event->channel === 'fcm' &&
               $event->notifiable->getTarget() == 'topic_all_client' &&
               $event->notification->getType() == 'topic_announcement' &&
               $event->response->numberSuccess() == 1;
      });
  }

  //==============================================
  //       pushMessages_UserUnRegisterActive
  //==============================================
  public function testPushMessagesSuccess_UserUnRegisterActive_TaskIdMissed()
  {
    $this->enableApiTaskIdMiddleware();
    Bus::fake();

    $mockIm = $this->mockImService();
    $mockIm->shouldReceive('sendGroupMsg')
        ->never();

    $mockIm->shouldReceive('sendTopicMsg')
        ->never();

    $response = $this->postJson('api/notification/push-messages', [
                      'tos'   => ['aaaaaaaa', 'bbbbbbbb'], 
                      'type'  => 'user_unregister_active',
                      'data'  => [],
                     ])
                     ->assertJson(['code' => 90001]);
  }

  public function testPushMessagesSuccess_UserUnRegisterActive_TaskIdCheck()
  {
    $this->enableApiTaskIdMiddleware();
    Bus::fake();

    $mockIm = $this->mockImService();
    $mockIm->shouldReceive('sendGroupMsg')
        ->never();
    $mockIm->shouldReceive('sendTopicMgs')
        ->never();

    $response = $this->postJson('api/notification/push-messages', [
                      'taskId'  => '24ab7c5e173d4c2da234845734139b00',
                      'tos'     => ['aaaaaaaa', 'bbbbbbbb'], 
                      'type'    => 'user_unregister_active',
                      'data'    => [],
                     ])
                     ->assertJson(['code' => 0]);
    self::assertDatabaseHas('admin_tasks', [
      'task_id' => '24ab7c5e173d4c2da234845734139b00',
    ]);

    $task = \SingPlus\Domains\Admins\Models\AdminTask::where('task_id', '24ab7c5e173d4c2da234845734139b00')->first();
    self::assertTrue(ends_with($task->data['url'], 'api/notification/push-messages'));
  }

  public function testPushMessagesSuccess_UserUnRegisterActive_TaskIdExists()
  {
    $this->enableApiTaskIdMiddleware();
    factory(\SingPlus\Domains\Admins\Models\AdminTask::class)->create([
      'task_id' => '24ab7c5e173d4c2da234845734139b00',
    ]);
    Bus::fake();

    $mockIm = $this->mockImService();
    $mockIm->shouldReceive('sendGroupMsg')
          ->never();
    $mockIm->shouldReceive('sendTopicMgs')
          ->never();

    $response = $this->postJson('api/notification/push-messages', [
                      'taskId'  => '24ab7c5e173d4c2da234845734139b00',
                      'tos'   => ['aaaaaaaa', 'bbbbbbbb'], 
                      'type'  => 'user_unregister_active',
                      'data'  => [],
                     ])
                     ->assertJson(['code' => 90002]);
  }

  public function testPushMessagesSuccess_UserUnRegisterActive_QueueCheck()
  {
    Bus::fake();

    $mockIm = $this->mockImService();
    $mockIm->shouldReceive('sendGroupMsg')
          ->never();
    $mockIm->shouldReceive('sendTopicMgs')
          ->never();

    $response = $this->postJson('api/notification/push-messages', [
                      'tos'   => ['aaaaaaaa', 'bbbbbbbb'], 
                      'type'  => 'user_unregister_active',
                      'data'  => [],
                     ])
                     ->assertJson(['code' => 0]);
    // 注意：laravel在实际dispatch notification时，会将实际发送的notification
    //       使用\Illuminate\Notifications\SendQueuedNotifications进行wrap，
    //       因此，此处应该判断\Illuminate\Notifications\SendQueuedNotifications
    //       是否被dispatch，而不是使用原始的notification class
    Bus::assertDispatched(
      \Illuminate\Notifications\SendQueuedNotifications::class,
      function ($job) {
        return $job->queue === 'sing_plus_api_push';
      });
  }

  public function testPushMessagesSuccess_UserUnRegisterActive()
  {
    \Event::fake();

    // mock FCM for sending notification
    $numberSuccess = 1;
    $mockResponse = new \LaravelFCM\Mocks\MockDownstreamResponse($numberSuccess);
    
    $sender = Mockery::mock(\LaravelFCM\Sender\FCMSender::class);
    $sender->shouldReceive('sendTo')
           ->once()
           ->with(
              ['aaaaaaaa', 'bbbbbbbb'],
              Mockery::any(),
              Mockery::on(function ($notification) {
                return is_null($notification);
              }),
              Mockery::on(function ($data) {
                $data = $data->toArray();
                return $data['title'] == 'Sing+' &&
                       $data['body'] == 'An undiscovered star or a talent seeker? Who are you? Let’s find out in Sing Plus' &&
                       $data['click_action'] == 'MainActivity' &&
                       $data['type'] == 'user_unregister_active' &&
                       $data['redirectTo'] === null;
              })
           )
           ->andReturn($mockResponse);

    $this->app->singleton('fcm.sender', function($app) use($sender) {
      return $sender;
    });

    $mockIm = $this->mockImService();
    $mockIm->shouldReceive('sendGroupMsg')
          ->never();
    $mockIm->shouldReceive('sendTopicMgs')
          ->never();

    $response = $this->postJson('api/notification/push-messages', [
                      'taskId'  => '24ab7c5e173d4c2da234845734139b00',
                      'tos'   => ['aaaaaaaa', 'bbbbbbbb'], 
                      'type'  => 'user_unregister_active',
                      'data'  => [],
                     ])
                     ->assertJson(['code' => 0]);
  }

  public function testPushMessagesSuccess_UserUnRegisterActive_WithCustomizeData()
  {
    \Event::fake();

    $mockIm = $this->mockImService();
    $mockIm->shouldReceive('sendGroupMsg')
          ->never();
    $mockIm->shouldReceive('sendTopicMgs')
          ->never();

    // mock FCM for sending notification
    $numberSuccess = 1;
    $mockResponse = new \LaravelFCM\Mocks\MockDownstreamResponse($numberSuccess);
    
    $sender = Mockery::mock(\LaravelFCM\Sender\FCMSender::class);
    $sender->shouldReceive('sendTo')
           ->once()
           ->with(
              ['aaaaaaaa', 'bbbbbbbb'],
              Mockery::any(),
              Mockery::on(function ($notification) {
                return is_null($notification);
              }),
              Mockery::on(function ($data) {
                $data = $data->toArray();
                return $data['title'] == 'Sing+' &&
                       $data['body'] == 'my content' &&
                       $data['icon'] == 'myicon' &&
                       $data['click_action'] == 'MainActivity' &&
                       $data['type'] == 'user_unregister_active' &&
                       $data['redirectTo'] == 'singplus://a/b/c';
              })
           )
           ->andReturn($mockResponse);

    $this->app->singleton('fcm.sender', function($app) use($sender) {
      return $sender;
    });
    $response = $this->postJson('api/notification/push-messages', [
                      'tos'   => ['aaaaaaaa', 'bbbbbbbb'], 
                      'type'  => 'user_unregister_active',
                      'data'  => [
                        'icon'        => 'myicon', 
                        'content'     => 'my content',
                        'redirectTo'  => 'singplus://a/b/c',
                      ],
                     ])
                     ->assertJson(['code' => 0]);
  }

  //==============================================
  //       pushMessages_UserNewNextday
  //==============================================
  public function testPushMessagesSuccess_UserNewNextday()
  {
    \Event::fake();

    // mock FCM for sending notification
    $numberSuccess = 1;
    $mockResponse = new \LaravelFCM\Mocks\MockDownstreamResponse($numberSuccess);
    
    $sender = Mockery::mock(\LaravelFCM\Sender\FCMSender::class);
    $sender->shouldReceive('sendTo')
           ->once()
           ->with(
              ['aaaaaaaa', 'bbbbbbbb'],
              Mockery::any(),
              Mockery::on(function ($notification) {
                return is_null($notification);
              }),
              Mockery::on(function ($data) {
                $data = $data->toArray();
                return $data['title'] == 'Sing+' &&
                       $data['body'] == 'Which songs describe your mood today? Sing out now' &&
                       $data['click_action'] == 'MainActivity' &&
                       $data['type'] == 'user_new_nextday' &&
                       $data['redirectTo'] === null;
              })
           )
           ->andReturn($mockResponse);

    $this->app->singleton('fcm.sender', function($app) use($sender) {
      return $sender;
    });

    $mockIm = $this->mockImService();
    $mockIm->shouldReceive('sendGroupMsg')
          ->never();
    $mockIm->shouldReceive('sendTopicMgs')
          ->never();

    $response = $this->postJson('api/notification/push-messages', [
                      'tos'   => ['aaaaaaaa', 'bbbbbbbb'], 
                      'type'  => 'user_new_nextday',
                      'data'  => [],
                     ])
                     ->assertJson(['code' => 0]);
  }

  public function testPushMessagesSuccess_UserNewNextday_WithCustomizeData()
  {
    \Event::fake();

    // mock FCM for sending notification
    $numberSuccess = 1;
    $mockResponse = new \LaravelFCM\Mocks\MockDownstreamResponse($numberSuccess);
    
    $sender = Mockery::mock(\LaravelFCM\Sender\FCMSender::class);
    $sender->shouldReceive('sendTo')
           ->once()
           ->with(
              ['aaaaaaaa', 'bbbbbbbb'],
              Mockery::any(),
              Mockery::on(function ($notification) {
                return is_null($notification);
              }),
              Mockery::on(function ($data) {
                $data = $data->toArray();
                return $data['title'] == 'Sing+' &&
                       $data['body'] == 'my content' &&
                       $data['icon'] == 'myicon' &&
                       $data['click_action'] == 'MainActivity' &&
                       $data['type'] == 'user_new_nextday' &&
                       $data['redirectTo'] == 'singplus://a/b/c';
              })
           )
           ->andReturn($mockResponse);

    $this->app->singleton('fcm.sender', function($app) use($sender) {
      return $sender;
    });

      $mockIm = $this->mockImService();
      $mockIm->shouldReceive('sendGroupMsg')
          ->never();
      $mockIm->shouldReceive('sendTopicMgs')
          ->never();

    $response = $this->postJson('api/notification/push-messages', [
                      'tos'   => ['aaaaaaaa', 'bbbbbbbb'], 
                      'type'  => 'user_new_nextday',
                      'data'  => [
                        'icon'        => 'myicon', 
                        'content'     => 'my content',
                        'redirectTo'  => 'singplus://a/b/c',
                      ],
                     ])
                     ->assertJson(['code' => 0]);
  }

  //==============================================
  //       pushMessages_UserNew7Day
  //==============================================
  public function testPushMessagesSuccess_UserNew7day()
  {
    \Event::fake();

    // mock FCM for sending notification
    $numberSuccess = 1;
    $mockResponse = new \LaravelFCM\Mocks\MockDownstreamResponse($numberSuccess);
    
    $sender = Mockery::mock(\LaravelFCM\Sender\FCMSender::class);
    $sender->shouldReceive('sendTo')
           ->once()
           ->with(
              ['aaaaaaaa', 'bbbbbbbb'],
              Mockery::any(),
              Mockery::on(function ($notification) {
                return is_null($notification);
              }),
              Mockery::on(function ($data) {
                $data = $data->toArray();
                return $data['title'] == 'Sing+' &&
                       $data['body'] == 'You have a new message' &&
                       $data['click_action'] == 'MainActivity' &&
                       $data['type'] == 'user_new_7day' &&
                       $data['redirectTo'] === null;
              })
           )
           ->andReturn($mockResponse);

    $this->app->singleton('fcm.sender', function($app) use($sender) {
      return $sender;
    });

      $mockIm = $this->mockImService();
      $mockIm->shouldReceive('sendGroupMsg')
          ->once()
          ->with(['aaaa1aaaa', 'bbbb1bbbb'],'user_new_7day',[],[]);
      $mockIm->shouldReceive('sendTopicMgs')
          ->never();

    $response = $this->postJson('api/notification/push-messages', [
                      'tos'   => ['aaaaaaaa', 'bbbbbbbb'], 
                      'type'  => 'user_new_7day',
                      'data'  => [],
                      'toUserIds' => ['aaaa1aaaa', 'bbbb1bbbb'],
                     ])
                     ->assertJson(['code' => 0]);
  }

  public function testPushMessagesSuccess_UserNew7day_WithCustomizeData()
  {
    \Event::fake();

    // mock FCM for sending notification
    $numberSuccess = 1;
    $mockResponse = new \LaravelFCM\Mocks\MockDownstreamResponse($numberSuccess);
    
    $sender = Mockery::mock(\LaravelFCM\Sender\FCMSender::class);
    $sender->shouldReceive('sendTo')
           ->once()
           ->with(
              ['aaaaaaaa', 'bbbbbbbb'],
              Mockery::any(),
              Mockery::on(function ($notification) {
                return is_null($notification);
              }),
              Mockery::on(function ($data) {
                $data = $data->toArray();
                return $data['title'] == 'Sing+' &&
                       $data['body'] == 'my content' &&
                       $data['icon'] == 'myicon' &&
                       $data['click_action'] == 'MainActivity' &&
                       $data['type'] == 'user_new_7day' &&
                       $data['redirectTo'] == 'singplus://a/b/c';
              })
           )
           ->andReturn($mockResponse);

    $this->app->singleton('fcm.sender', function($app) use($sender) {
      return $sender;
    });

      $mockIm = $this->mockImService();
      $mockIm->shouldReceive('sendGroupMsg')
          ->once()
          ->with(['aaaa1aaaa', 'bbbb1bbbb'],'user_new_7day',[],[
              'icon'        => 'myicon',
              'content'     => 'my content',
              'redirectTo'  => 'singplus://a/b/c',
          ]);
      $mockIm->shouldReceive('sendTopicMgs')
          ->never();

    $response = $this->postJson('api/notification/push-messages', [
                      'tos'   => ['aaaaaaaa', 'bbbbbbbb'], 
                      'type'  => 'user_new_7day',
                      'data'  => [
                        'icon'        => 'myicon', 
                        'content'     => 'my content',
                        'redirectTo'  => 'singplus://a/b/c',
                      ],
                      'toUserIds' => ['aaaa1aaaa', 'bbbb1bbbb'],
                     ])
                     ->assertJson(['code' => 0]);
  }

  //==============================================
  //       pushMessages_UserNew30Day
  //==============================================
  public function testPushMessagesSuccess_UserNew30day()
  {
    \Event::fake();

    // mock FCM for sending notification
    $numberSuccess = 1;
    $mockResponse = new \LaravelFCM\Mocks\MockDownstreamResponse($numberSuccess);
    
    $sender = Mockery::mock(\LaravelFCM\Sender\FCMSender::class);
    $sender->shouldReceive('sendTo')
           ->once()
           ->with(
              ['aaaaaaaa', 'bbbbbbbb'],
              Mockery::any(),
              Mockery::on(function ($notification) {
                return is_null($notification);
              }),
              Mockery::on(function ($data) {
                $data = $data->toArray();
                return $data['title'] == 'Sing+' &&
                       $data['body'] == 'You’re with us for 1 month. Sing a song to celebrate!' &&
                       $data['click_action'] == 'MainActivity' &&
                       $data['type'] == 'user_new_30day' &&
                       $data['redirectTo'] === null;
              })
           )
           ->andReturn($mockResponse);

    $this->app->singleton('fcm.sender', function($app) use($sender) {
      return $sender;
    });

    $mockIm = $this->mockImService();
    $mockIm->shouldReceive('sendGroupMsg')
          ->never();
    $mockIm->shouldReceive('sendTopicMgs')
          ->never();


      $response = $this->postJson('api/notification/push-messages', [
                      'tos'   => ['aaaaaaaa', 'bbbbbbbb'], 
                      'type'  => 'user_new_30day',
                      'data'  => [],
                     ])
                     ->assertJson(['code' => 0]);
  }

  public function testPushMessagesSuccess_UserNew30day_WithCustomizeData()
  {
    \Event::fake();

    // mock FCM for sending notification
    $numberSuccess = 1;
    $mockResponse = new \LaravelFCM\Mocks\MockDownstreamResponse($numberSuccess);
    
    $sender = Mockery::mock(\LaravelFCM\Sender\FCMSender::class);
    $sender->shouldReceive('sendTo')
           ->once()
           ->with(
              ['aaaaaaaa', 'bbbbbbbb'],
              Mockery::any(),
              Mockery::on(function ($notification) {
                return is_null($notification);
              }),
              Mockery::on(function ($data) {
                $data = $data->toArray();
                return $data['title'] == 'Sing+' &&
                       $data['body'] == 'my content' &&
                       $data['icon'] == 'myicon' &&
                       $data['click_action'] == 'MainActivity' &&
                       $data['type'] == 'user_new_30day' &&
                       $data['redirectTo'] == 'singplus://a/b/c';
              })
           )
           ->andReturn($mockResponse);

    $this->app->singleton('fcm.sender', function($app) use($sender) {
      return $sender;
    });

      $mockIm = $this->mockImService();
      $mockIm->shouldReceive('sendGroupMsg')
          ->never();
      $mockIm->shouldReceive('sendTopicMgs')
          ->never();


      $response = $this->postJson('api/notification/push-messages', [
                      'tos'   => ['aaaaaaaa', 'bbbbbbbb'], 
                      'type'  => 'user_new_30day',
                      'data'  => [
                        'icon'        => 'myicon', 
                        'content'     => 'my content',
                        'redirectTo'  => 'singplus://a/b/c',
                      ],
                     ])
                     ->assertJson(['code' => 0]);
  }

  //==============================================
  //       pushMessages_UserNewConversion
  //==============================================
  public function testPushMessagesSuccess_UserNewConversion()
  {
    \Event::fake();

    // mock FCM for sending notification
    $numberSuccess = 1;
    $mockResponse = new \LaravelFCM\Mocks\MockDownstreamResponse($numberSuccess);
    
    $sender = Mockery::mock(\LaravelFCM\Sender\FCMSender::class);
    $sender->shouldReceive('sendTo')
           ->once()
           ->with(
              ['aaaaaaaa', 'bbbbbbbb'],
              Mockery::any(),
              Mockery::on(function ($notification) {
                return is_null($notification);
              }),
              Mockery::on(function ($data) {
                $data = $data->toArray();
                return $data['title'] == 'Sing+' &&
                       $data['body'] == 'You have a new message' &&
                       $data['click_action'] == 'MainActivity' &&
                       $data['type'] == 'user_new_conversion' &&
                       $data['redirectTo'] === null;
              })
           )
           ->andReturn($mockResponse);

    $this->app->singleton('fcm.sender', function($app) use($sender) {
      return $sender;
    });

    $mockIm = $this->mockImService();
    $mockIm->shouldReceive('sendGroupMsg')
          ->once()
          ->with(['aaaa1aaaa', 'bbbb1bbbb'],'user_new_conversion',[],[]);
    $mockIm->shouldReceive('sendTopicMgs')
          ->never();


      $response = $this->postJson('api/notification/push-messages', [
                      'tos'   => ['aaaaaaaa', 'bbbbbbbb'], 
                      'type'  => 'user_new_conversion',
                      'data'  => [],
                      'toUserIds' => ['aaaa1aaaa', 'bbbb1bbbb'],
                     ])
                     ->assertJson(['code' => 0]);
  }

  public function testPushMessagesSuccess_UserNewConversion_WithCustomizeData()
  {
    \Event::fake();

    // mock FCM for sending notification
    $numberSuccess = 1;
    $mockResponse = new \LaravelFCM\Mocks\MockDownstreamResponse($numberSuccess);
    
    $sender = Mockery::mock(\LaravelFCM\Sender\FCMSender::class);
    $sender->shouldReceive('sendTo')
           ->once()
           ->with(
              ['aaaaaaaa', 'bbbbbbbb'],
              Mockery::any(),
              Mockery::on(function ($notification) {
                return is_null($notification);
              }),
              Mockery::on(function ($data) {
                $data = $data->toArray();
                return $data['title'] == 'Sing+' &&
                       $data['body'] == 'my content' &&
                       $data['icon'] == 'myicon' &&
                       $data['click_action'] == 'MainActivity' &&
                       $data['type'] == 'user_new_conversion' &&
                       $data['redirectTo'] == 'singplus://a/b/c';
              })
           )
           ->andReturn($mockResponse);

    $this->app->singleton('fcm.sender', function($app) use($sender) {
      return $sender;
    });

      $mockIm = $this->mockImService();
      $mockIm->shouldReceive('sendGroupMsg')
          ->once()
          ->with(['aaaa1aaaa', 'bbbb1bbbb'],'user_new_conversion',[],[
              'icon'        => 'myicon',
              'content'     => 'my content',
              'redirectTo'  => 'singplus://a/b/c',
          ]);
      $mockIm->shouldReceive('sendTopicMgs')
          ->never();

    $response = $this->postJson('api/notification/push-messages', [
                      'tos'   => ['aaaaaaaa', 'bbbbbbbb'], 
                      'type'  => 'user_new_conversion',
                      'data'  => [
                        'icon'        => 'myicon', 
                        'content'     => 'my content',
                        'redirectTo'  => 'singplus://a/b/c',
                      ],
                      'toUserIds' => ['aaaa1aaaa', 'bbbb1bbbb'],
                     ])
                     ->assertJson(['code' => 0]);
  }

  //==============================================
  //       pushMessages_UserActive1st
  //==============================================
  public function testPushMessagesSuccess_UserActive1st()
  {
    \Event::fake();

    // mock FCM for sending notification
    $numberSuccess = 1;
    $mockResponse = new \LaravelFCM\Mocks\MockDownstreamResponse($numberSuccess);
    
    $sender = Mockery::mock(\LaravelFCM\Sender\FCMSender::class);
    $sender->shouldReceive('sendTo')
           ->once()
           ->with(
              ['aaaaaaaa', 'bbbbbbbb'],
              Mockery::any(),
              Mockery::on(function ($notification) {
                return is_null($notification);
              }),
              Mockery::on(function ($data) {
                $data = $data->toArray();
                return $data['title'] == 'Sing+' &&
                       $data['body'] == 'You have a new message' &&
                       $data['click_action'] == 'MainActivity' &&
                       $data['type'] == 'user_active_1st' &&
                       $data['redirectTo'] === null;
              })
           )
           ->andReturn($mockResponse);

    $this->app->singleton('fcm.sender', function($app) use($sender) {
      return $sender;
    });

      $mockIm = $this->mockImService();
      $mockIm->shouldReceive('sendGroupMsg')
          ->once()
          ->with(['aaaa1aaaa', 'bbbb1bbbb'],'user_active_1st',[],[]);
      $mockIm->shouldReceive('sendTopicMgs')
          ->never();

    $response = $this->postJson('api/notification/push-messages', [
                      'tos'   => ['aaaaaaaa', 'bbbbbbbb'], 
                      'type'  => 'user_active_1st',
                      'data'  => [],
                      'toUserIds' => ['aaaa1aaaa', 'bbbb1bbbb'],
                     ])
                     ->assertJson(['code' => 0]);
  }

  public function testPushMessagesSuccess_UserActive1st_WithCustomizeData()
  {
    \Event::fake();

    // mock FCM for sending notification
    $numberSuccess = 1;
    $mockResponse = new \LaravelFCM\Mocks\MockDownstreamResponse($numberSuccess);
    
    $sender = Mockery::mock(\LaravelFCM\Sender\FCMSender::class);
    $sender->shouldReceive('sendTo')
           ->once()
           ->with(
              ['aaaaaaaa', 'bbbbbbbb'],
              Mockery::any(),
              Mockery::on(function ($notification) {
                return is_null($notification);
              }),
              Mockery::on(function ($data) {
                $data = $data->toArray();
                return $data['title'] == 'Sing+' &&
                       $data['body'] == 'my content' &&
                       $data['icon'] == 'myicon' &&
                       $data['click_action'] == 'MainActivity' &&
                       $data['type'] == 'user_active_1st' &&
                       $data['redirectTo'] == 'singplus://a/b/c';
              })
           )
           ->andReturn($mockResponse);

    $this->app->singleton('fcm.sender', function($app) use($sender) {
      return $sender;
    });

      $mockIm = $this->mockImService();
      $mockIm->shouldReceive('sendGroupMsg')
          ->once()
          ->with(['aaaa1aaaa', 'bbbb1bbbb'],'user_active_1st',[],[
              'icon'        => 'myicon',
              'content'     => 'my content',
              'redirectTo'  => 'singplus://a/b/c',
          ]);
      $mockIm->shouldReceive('sendTopicMgs')
          ->never();

    $response = $this->postJson('api/notification/push-messages', [
                      'tos'   => ['aaaaaaaa', 'bbbbbbbb'], 
                      'type'  => 'user_active_1st',
                      'data'  => [
                        'icon'        => 'myicon', 
                        'content'     => 'my content',
                        'redirectTo'  => 'singplus://a/b/c',
                      ],
                      'toUserIds' => ['aaaa1aaaa', 'bbbb1bbbb']
                     ])
                     ->assertJson(['code' => 0]);
  }

  //==============================================
  //       pushMessages_UserActiveSing
  //==============================================
  public function testPushMessagesSuccess_UserActiveSing()
  {
    \Event::fake();

    // mock FCM for sending notification
    $numberSuccess = 1;
    $mockResponse = new \LaravelFCM\Mocks\MockDownstreamResponse($numberSuccess);
    
    $sender = Mockery::mock(\LaravelFCM\Sender\FCMSender::class);
    $sender->shouldReceive('sendTo')
           ->once()
           ->with(
              ['aaaaaaaa', 'bbbbbbbb'],
              Mockery::any(),
              Mockery::on(function ($notification) {
                return is_null($notification);
              }),
              Mockery::on(function ($data) {
                $data = $data->toArray();
                return $data['title'] == 'Sing+' &&
                       $data['body'] == 'Top covered songs of the day, which one can you sing?' &&
                       $data['click_action'] == 'MainActivity' &&
                       $data['type'] == 'user_active_sing' &&
                       $data['redirectTo'] === 'singplus://ranks';
              })
           )
           ->andReturn($mockResponse);

    $this->app->singleton('fcm.sender', function($app) use($sender) {
      return $sender;
    });

      $mockIm = $this->mockImService();
      $mockIm->shouldReceive('sendGroupMsg')
          ->never();
      $mockIm->shouldReceive('sendTopicMgs')
          ->never();

    $response = $this->postJson('api/notification/push-messages', [
                      'tos'   => ['aaaaaaaa', 'bbbbbbbb'], 
                      'type'  => 'user_active_sing',
                      'data'  => [],
                     ])
                     ->assertJson(['code' => 0]);
  }

  public function testPushMessagesSuccess_UserActiveSing_WithCustomizeData()
  {
    \Event::fake();

    // mock FCM for sending notification
    $numberSuccess = 1;
    $mockResponse = new \LaravelFCM\Mocks\MockDownstreamResponse($numberSuccess);
    
    $sender = Mockery::mock(\LaravelFCM\Sender\FCMSender::class);
    $sender->shouldReceive('sendTo')
           ->once()
           ->with(
              ['aaaaaaaa', 'bbbbbbbb'],
              Mockery::any(),
              Mockery::on(function ($notification) {
                return is_null($notification);
              }),
              Mockery::on(function ($data) {
                $data = $data->toArray();
                return $data['title'] == 'Sing+' &&
                       $data['body'] == 'my content' &&
                       $data['icon'] == 'myicon' &&
                       $data['click_action'] == 'MainActivity' &&
                       $data['type'] == 'user_active_sing' &&
                       $data['redirectTo'] == 'singplus://a/b/c';
              })
           )
           ->andReturn($mockResponse);

    $this->app->singleton('fcm.sender', function($app) use($sender) {
      return $sender;
    });

      $mockIm = $this->mockImService();
      $mockIm->shouldReceive('sendGroupMsg')
          ->never();
      $mockIm->shouldReceive('sendTopicMgs')
          ->never();

    $response = $this->postJson('api/notification/push-messages', [
                      'tos'   => ['aaaaaaaa', 'bbbbbbbb'], 
                      'type'  => 'user_active_sing',
                      'data'  => [
                        'icon'        => 'myicon', 
                        'content'     => 'my content',
                        'redirectTo'  => 'singplus://a/b/c',
                      ],
                     ])
                     ->assertJson(['code' => 0]);
  }

  //==============================================
  //       pushMessages_UserActiveListen
  //==============================================
  public function testPushMessagesSuccess_UserActiveListen()
  {
    \Event::fake();

    // mock FCM for sending notification
    $numberSuccess = 1;
    $mockResponse = new \LaravelFCM\Mocks\MockDownstreamResponse($numberSuccess);
    
    $sender = Mockery::mock(\LaravelFCM\Sender\FCMSender::class);
    $sender->shouldReceive('sendTo')
           ->once()
           ->with(
              ['aaaaaaaa', 'bbbbbbbb'],
              Mockery::any(),
              Mockery::on(function ($notification) {
                return is_null($notification);
              }),
              Mockery::on(function ($data) {
                $data = $data->toArray();
                return $data['title'] == 'Sing+' &&
                       $data['body'] == 'Editor’s Picks of the day, which one is your favorite?' &&
                       $data['click_action'] == 'MainActivity' &&
                       $data['type'] == 'user_active_listen' &&
                       $data['redirectTo'] === 'singplus://picks';
              })
           )
           ->andReturn($mockResponse);

    $this->app->singleton('fcm.sender', function($app) use($sender) {
      return $sender;
    });

      $mockIm = $this->mockImService();
      $mockIm->shouldReceive('sendGroupMsg')
          ->never();
      $mockIm->shouldReceive('sendTopicMgs')
          ->never();

    $response = $this->postJson('api/notification/push-messages', [
                      'tos'   => ['aaaaaaaa', 'bbbbbbbb'], 
                      'type'  => 'user_active_listen',
                      'data'  => [],
                     ])
                     ->assertJson(['code' => 0]);
  }

  public function testPushMessagesSuccess_UserActiveListen_WithCustomizeData()
  {
    \Event::fake();

    // mock FCM for sending notification
    $numberSuccess = 1;
    $mockResponse = new \LaravelFCM\Mocks\MockDownstreamResponse($numberSuccess);
    
    $sender = Mockery::mock(\LaravelFCM\Sender\FCMSender::class);
    $sender->shouldReceive('sendTo')
           ->once()
           ->with(
              ['aaaaaaaa', 'bbbbbbbb'],
              Mockery::any(),
              Mockery::on(function ($notification) {
                return is_null($notification);
              }),
              Mockery::on(function ($data) {
                $data = $data->toArray();
                return $data['title'] == 'Sing+' &&
                       $data['body'] == 'my content' &&
                       $data['icon'] == 'myicon' &&
                       $data['click_action'] == 'MainActivity' &&
                       $data['type'] == 'user_active_listen' &&
                       $data['redirectTo'] == 'singplus://a/b/c';
              })
           )
           ->andReturn($mockResponse);

    $this->app->singleton('fcm.sender', function($app) use($sender) {
      return $sender;
    });

      $mockIm = $this->mockImService();
      $mockIm->shouldReceive('sendGroupMsg')
          ->never();
      $mockIm->shouldReceive('sendTopicMgs')
          ->never();

    $response = $this->postJson('api/notification/push-messages', [
                      'tos'   => ['aaaaaaaa', 'bbbbbbbb'], 
                      'type'  => 'user_active_listen',
                      'data'  => [
                        'icon'        => 'myicon', 
                        'content'     => 'my content',
                        'redirectTo'  => 'singplus://a/b/c',
                      ],
                     ])
                     ->assertJson(['code' => 0]);
  }

  //==============================================
  //       pushMessages_TopicCoverOfDay
  //==============================================
  public function testPushMessagesSuccess_TopicCoverOfDay()
  {
    \Event::fake();
    // mock FCM for sending notification
    $numberSuccess = 1;
    $mockResponse = new \LaravelFCM\Mocks\MockDownstreamResponse($numberSuccess);
    
    $sender = Mockery::mock(\LaravelFCM\Sender\FCMSender::class);
    $sender->shouldReceive('sendToTopic')
           ->once()
           ->with(
              Mockery::on(function(\LaravelFCM\Message\Topics $topic) {
                return $topic->build() == '/topics/topic_country_chr';
              }),
              Mockery::any(),
              Mockery::on(function ($notification) {
                return is_null($notification);
              }),
              Mockery::on(function ($data) {
                $data = $data->toArray();
                return $data['title'] == 'Sing+' &&
                       $data['body'] == 'You have a new message' &&
                       $data['click_action'] == 'MainActivity' &&
                       $data['type'] == 'topic_cover_of_day' &&
                       $data['redirectTo'] === null;
              })
           )
           ->andReturn($mockResponse);
    $sender->shouldReceive('sendToTopic')
           ->once()
           ->with(
              Mockery::on(function(\LaravelFCM\Message\Topics $topic) {
                return $topic->build() == '/topics/topic_country_br';
              }),
              Mockery::any(),
              Mockery::on(function ($notification) {
                return is_null($notification);
              }),
              Mockery::on(function ($data) {
                $data = $data->toArray();
                return $data['title'] == 'Sing+' &&
                       $data['body'] == 'You have a new message' &&
                       $data['click_action'] == 'MainActivity' &&
                       $data['type'] == 'topic_cover_of_day' &&
                       $data['redirectTo'] === null;
              })
           )
           ->andReturn($mockResponse);

    $this->app->singleton('fcm.sender', function($app) use($sender) {
      return $sender;
    });

    $imMock = $this->mockImService();
    $imMock->shouldReceive('sendTopicMsg')
        ->once()
        ->with('topic_country_other','topic_cover_of_day',[],[]);
    $imMock->shouldReceive('sendGroupMsg')
         ->never();

    $response = $this->postJson('api/notification/push-messages', [
                      'tos'   => ['topic_country_chr', 'topic_country_br'], 
                      'type'  => 'topic_cover_of_day',
                      'data'  => [],
                     ])
                     ->assertJson(['code' => 0]);
  }

  public function testPushMessagesSuccess_TopicCoverOfDay_WithCustomizeData()
  {
    \Event::fake();

    // mock FCM for sending notification
    $numberSuccess = 1;
    $mockResponse = new \LaravelFCM\Mocks\MockDownstreamResponse($numberSuccess);
    
    $sender = Mockery::mock(\LaravelFCM\Sender\FCMSender::class);
    $sender->shouldReceive('sendToTopic')
           ->once()
           ->with(
              Mockery::on(function(\LaravelFCM\Message\Topics $topic) {
                return $topic->build() == '/topics/topic_country_chr';
              }),
              Mockery::any(),
              Mockery::on(function ($notification) {
                return is_null($notification);
              }),
              Mockery::on(function ($data) {
                $data = $data->toArray();
                return $data['title'] == 'Sing+' &&
                       $data['body'] == 'my content' &&
                       $data['icon'] == 'myicon' &&
                       $data['click_action'] == 'MainActivity' &&
                       $data['type'] == 'topic_cover_of_day' &&
                       $data['redirectTo'] == 'singplus://a/b/c';
              })
           )
           ->andReturn($mockResponse);
    $sender->shouldReceive('sendToTopic')
           ->once()
           ->with(
              Mockery::on(function(\LaravelFCM\Message\Topics $topic) {
                return $topic->build() == '/topics/topic_country_br';
              }),
              Mockery::any(),
              Mockery::on(function ($notification) {
                return is_null($notification);
              }),
              Mockery::on(function ($data) {
                $data = $data->toArray();
                return $data['title'] == 'Sing+' &&
                       $data['body'] == 'my content' &&
                       $data['icon'] == 'myicon' &&
                       $data['click_action'] == 'MainActivity' &&
                       $data['type'] == 'topic_cover_of_day' &&
                       $data['redirectTo'] == 'singplus://a/b/c';
              })
           )
           ->andReturn($mockResponse);

    $this->app->singleton('fcm.sender', function($app) use($sender) {
      return $sender;
    });

      $imMock = $this->mockImService();
      $imMock->shouldReceive('sendTopicMsg')
          ->once()
          ->with('topic_country_other','topic_cover_of_day',[],[
              'icon'        => 'myicon',
              'content'     => 'my content',
              'redirectTo'  => 'singplus://a/b/c',
          ]);
      $imMock->shouldReceive('sendGroupMsg')
          ->never();

    $response = $this->postJson('api/notification/push-messages', [
                      'tos'   => ['topic_country_chr', 'topic_country_br'], 
                      'type'  => 'topic_cover_of_day',
                      'data'  => [
                        'icon'        => 'myicon', 
                        'content'     => 'my content',
                        'redirectTo'  => 'singplus://a/b/c',
                      ],
                     ])
                     ->assertJson(['code' => 0]);
  }

  //==============================================
  //       pushMessages_TopicNewSong
  //==============================================
  public function testPushMessagesSuccess_TopicNewSong()
  {
    \Event::fake();

    // mock FCM for sending notification
    $numberSuccess = 1;
    $mockResponse = new \LaravelFCM\Mocks\MockDownstreamResponse($numberSuccess);
    
    $sender = Mockery::mock(\LaravelFCM\Sender\FCMSender::class);
    $sender->shouldReceive('sendToTopic')
           ->once()
           ->with(
              Mockery::on(function(\LaravelFCM\Message\Topics $topic) {
                return $topic->build() == '/topics/topic_country_chr';
              }),
              Mockery::any(),
              Mockery::on(function ($notification) {
                return is_null($notification);
              }),
              Mockery::on(function ($data) {
                $data = $data->toArray();
                return $data['title'] == 'Sing+' &&
                       $data['body'] == 'You have a new message' &&
                       $data['click_action'] == 'MainActivity' &&
                       $data['type'] == 'topic_new_song' &&
                       $data['redirectTo'] === null;
              })
           )
           ->andReturn($mockResponse);
    $sender->shouldReceive('sendToTopic')
           ->once()
           ->with(
              Mockery::on(function(\LaravelFCM\Message\Topics $topic) {
                return $topic->build() == '/topics/topic_country_br';
              }),
              Mockery::any(),
              Mockery::on(function ($notification) {
                return is_null($notification);
              }),
              Mockery::on(function ($data) {
                $data = $data->toArray();
                return $data['title'] == 'Sing+' &&
                       $data['body'] == 'You have a new message' &&
                       $data['click_action'] == 'MainActivity' &&
                       $data['type'] == 'topic_new_song' &&
                       $data['redirectTo'] === null;
              })
           )
           ->andReturn($mockResponse);

    $this->app->singleton('fcm.sender', function($app) use($sender) {
      return $sender;
    });

      $imMock = $this->mockImService();
      $imMock->shouldReceive('sendTopicMsg')
          ->once()
          ->with('topic_country_other','topic_new_song',[],[]);
      $imMock->shouldReceive('sendGroupMsg')
          ->never();

    $response = $this->postJson('api/notification/push-messages', [
                      'tos'   => ['topic_country_chr', 'topic_country_br'], 
                      'type'  => 'topic_new_song',
                      'data'  => [],
                     ])
                     ->assertJson(['code' => 0]);
  }

  public function testPushMessagesSuccess_TopicNewSong_WithCustomizeData()
  {
    \Event::fake();

    // mock FCM for sending notification
    $numberSuccess = 1;
    $mockResponse = new \LaravelFCM\Mocks\MockDownstreamResponse($numberSuccess);
    
    $sender = Mockery::mock(\LaravelFCM\Sender\FCMSender::class);
    $sender->shouldReceive('sendToTopic')
           ->once()
           ->with(
              Mockery::on(function(\LaravelFCM\Message\Topics $topic) {
                return $topic->build() == '/topics/topic_country_chr';
              }),
              Mockery::any(),
              Mockery::on(function ($notification) {
                return is_null($notification);
              }),
              Mockery::on(function ($data) {
                $data = $data->toArray();
                return $data['title'] == 'Sing+' &&
                       $data['body'] == 'my content' &&
                       $data['icon'] == 'myicon' &&
                       $data['click_action'] == 'MainActivity' &&
                       $data['type'] == 'topic_new_song' &&
                       $data['redirectTo'] == 'singplus://a/b/c';
              })
           )
           ->andReturn($mockResponse);
    $sender->shouldReceive('sendToTopic')
           ->once()
           ->with(
              Mockery::on(function(\LaravelFCM\Message\Topics $topic) {
                return $topic->build() == '/topics/topic_country_br';
              }),
              Mockery::any(),
              Mockery::on(function ($notification) {
                return is_null($notification);
              }),
              Mockery::on(function ($data) {
                $data = $data->toArray();
                return $data['title'] == 'Sing+' &&
                       $data['body'] == 'my content' &&
                       $data['icon'] == 'myicon' &&
                       $data['click_action'] == 'MainActivity' &&
                       $data['type'] == 'topic_new_song' &&
                       $data['redirectTo'] == 'singplus://a/b/c';
              })
           )
           ->andReturn($mockResponse);

    $this->app->singleton('fcm.sender', function($app) use($sender) {
      return $sender;
    });

      $imMock = $this->mockImService();
      $imMock->shouldReceive('sendTopicMsg')
          ->once()
          ->with('topic_country_other','topic_new_song',[],[
              'icon'        => 'myicon',
              'content'     => 'my content',
              'redirectTo'  => 'singplus://a/b/c',
          ]);
      $imMock->shouldReceive('sendGroupMsg')
          ->never();

    $response = $this->postJson('api/notification/push-messages', [
                      'taskId'  => '24ab7c5e173d4c2da234845734139b00',
                      'tos'   => ['topic_country_chr', 'topic_country_br'], 
                      'type'  => 'topic_new_song',
                      'data'  => [
                        'icon'        => 'myicon', 
                        'content'     => 'my content',
                        'redirectTo'  => 'singplus://a/b/c',
                      ],
                     ])
                     ->assertJson(['code' => 0]);
  }

  //=======================================
  //        pushPrivateMsgNotify
  //=======================================

  public function testPushPrivateMsgNotifySuccess(){
      $user = factory(\SingPlus\Domains\Users\Models\User::class)->create([
          'push_alias' => 'aaaaaaaa'
      ]);
      $userProfile = factory(\SingPlus\Domains\Users\Models\UserProfile::class)->create([
          'user_id' => $user->id,
          'nickname' => 'zhangsan'
      ]);
      $receiveUser = factory(\SingPlus\Domains\Users\Models\User::class)->create([
          'push_alias' => 'bbbbbbbb'
      ]);
      $receiveUserProfile = factory(\SingPlus\Domains\Users\Models\UserProfile::class)->create([
          'user_id' => $receiveUser->id,
          'nickname' => 'lisiguang'
      ]);

      \Event::fake();

      // mock FCM for sending notification
      $numberSuccess = 1;
      $mockResponse = new \LaravelFCM\Mocks\MockDownstreamResponse($numberSuccess);

      $sender = Mockery::mock(\LaravelFCM\Sender\FCMSender::class);
      $sender->shouldReceive('sendTo')
          ->once()
          ->with(
             'bbbbbbbb',
              Mockery::any(),
              Mockery::on(function ($notification) {
                  $notification = $notification->toArray();
                  return $notification['title'] == 'Sing+' &&
                      $notification['body'] == 'lisiguang, zhangsan sent you a private message' &&
                      $notification['click_action'] == 'MainActivity';
              }),
              Mockery::on(function ($data) {
                  $data = $data->toArray();
                  return array_get($data, 'type') == 'user_new_private_msg' &&
                      array_get($data, 'redirectTo') == 'singplus://private_msg?senderId=11111233';
              }))
          ->andReturn($mockResponse);

      $this->app->singleton('fcm.sender', function($app) use($sender) {
          return $sender;
      });

      $mockIm = $this->mockImService();
      $mockIm->shouldReceive('sendGroupMsg')
          ->never();
      $mockIm->shouldReceive('sendTopicMgs')
          ->never();

      $response = $this->postJson('api/notification/notify-private-msg',[
          'userId'   => $user->id,
          'receiveId'  => $receiveUser->id,
          'redirectTo' => 'singplus://private_msg?senderId=11111233'
      ])->assertJson(['code' => 0]);
  }


    private function mockImService()
    {
      $imService = Mockery::mock(\SingPlus\Contracts\TXIM\Services\ImService::class);
      $this->app[\SingPlus\Contracts\TXIM\Services\ImService::class ] = $imService;
      return $imService;
    }
}
