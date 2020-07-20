<?php

namespace FeatureTest\SingPlus\Controllers\Auth;

use GuzzleHttp\Client;
use GuzzleHttp\Handler\MockHandler;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Psr7\Response;
use Cache;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use FeatureTest\SingPlus\TestCase;
use FeatureTest\SingPlus\MongodbClearTrait;
use Mockery;
use SingPlus\Contracts\DailyTask\Constants\DailyTask;
use SingPlus\Contracts\Feeds\Constants\Feed;
use SingPlus\Support\Helpers\Str;

class FeedControllerTest extends TestCase
{
  use MongodbClearTrait; 

  //=================================
  //        getNotificationList
  //=================================
  public function testGetNotificationListSuccess()
  {
    $this->expectsEvents(\SingPlus\Events\Feeds\FeedReaded::class);

    config([
      'filesystems.disks.s3.region'  => 'eu-central-1',
      'filesystems.disks.s3.bucket'  => 'sing-plus',
    ]);

    $user = factory(\SingPlus\Domains\Users\Models\User::class)->create();
    factory(\SingPlus\Domains\Users\Models\UserProfile::class)->create([
      'user_id' => $user->id, 
    ]);

    // prepare feeds
    $data = $this->prepareFeeds($user->id);

    $this->mockHttpClient([
        [
            'body'  => json_encode([
                'code'  => 0,
                'data'  => [
                    'relation'  => [
                        $data->feed->three->operator_user_id    => [
                            'is_following'  => false,
                            'follow_at'     => null,
                            'is_follower'   => false,
                            'followed_at'   => null,
                        ],
                    ],
                ],
            ]),
        ],
    ]);
                  
    $response = $this->actingAs($user)
                     ->getJson('v3/messages/feeds');

    $response->assertJson(['code' => 0]);
    
    $feeds = (json_decode($response->getContent()))->data->feeds;
    self::assertCount(3, $feeds);
    self::assertEquals($data->feed->three->id, $feeds[0]->feedId);
  }

  public function testGetNotificationListSuccess_NotFirstPage()
  {
    config([
      'filesystems.disks.s3.region'  => 'eu-central-1',
      'filesystems.disks.s3.bucket'  => 'sing-plus',
    ]);

    $user = factory(\SingPlus\Domains\Users\Models\User::class)->create();
    factory(\SingPlus\Domains\Users\Models\UserProfile::class)->create([
      'user_id' => $user->id,
    ]);

    // prepare feeds
    $data = $this->prepareFeeds($user->id);

    $this->mockHttpClient([
        [
            'body'  => json_encode([
                'code'  => 0,
                'data'  => [
                    'relation'  => [
                        $data->feed->three->operator_user_id    => [
                            'is_following'  => false,
                            'follow_at'     => null,
                            'is_follower'   => false,
                            'followed_at'   => null,
                        ],
                    ],
                ],
            ]),
        ],
    ]);
    $response = $this->actingAs($user)
                     ->getJson('v3/messages/feeds?' . http_build_query([
                        'feedId'  => $data->feed->three->id,
                     ]));

    $response->assertJson(['code' => 0]);

    $feeds = (json_decode($response->getContent()))->data->feeds;
    self::assertCount(2, $feeds);
    self::assertEquals($data->feed->two->id, $feeds[0]->feedId);
    self::assertEquals('zhangsan', $feeds[0]->operator->name);
    self::assertEquals('https://sing-plus.s3.eu-central-1.amazonaws.com/operator-avatar', $feeds[0]->operator->avatar);
    self::assertEquals($data->feed->two->detail['work_id'], $feeds[0]->detail->workId);
    self::assertTrue($feeds[0]->isRead);
    self::assertEquals('no accompaniment', $feeds[0]->detail->music->musicName);  // work name be used if exists
  }

   public function testGetNotificationListSuccess_Favourite()
   {
       $this->expectsEvents(\SingPlus\Events\Feeds\FeedReaded::class);
        config([
            'filesystems.disks.s3.region'  => 'eu-central-1',
            'filesystems.disks.s3.bucket'  => 'sing-plus',
        ]);

        $user = factory(\SingPlus\Domains\Users\Models\User::class)->create();
        factory(\SingPlus\Domains\Users\Models\UserProfile::class)->create([
            'user_id' => $user->id,
        ]);

        // prepare feeds
        $data = $this->prepareFeeds($user->id);

        $this->mockHttpClient([
            [
                'body'  => json_encode([
                    'code'  => 0,
                    'data'  => [
                        'relation'  => [
                            $data->feed->three->operator_user_id    => [
                                'is_following'  => false,
                                'follow_at'     => null,
                                'is_follower'   => false,
                                'followed_at'   => null,
                            ],
                        ],
                    ],
                ]),
            ],
        ]);

        $response = $this->actingAs($user)
            ->getJson('v3/messages/feeds?' . http_build_query([
                    'type'  => "work_favourite",
                ]));

        $response->assertJson(['code' => 0]);

        $feeds = (json_decode($response->getContent()))->data->feeds;
        self::assertCount(1, $feeds);
        self::assertEquals($data->feed->three->id, $feeds[0]->feedId);
        self::assertEquals('zhangsan', $feeds[0]->operator->name);
        self::assertEquals('https://sing-plus.s3.eu-central-1.amazonaws.com/operator-avatar', $feeds[0]->operator->avatar);
        self::assertEquals($data->feed->three->detail['work_id'], $feeds[0]->detail->workId);
        self::assertTrue($feeds[0]->isRead);
        self::assertEquals('music-two', $feeds[0]->detail->music->musicName);  // work name be used if exists
   }

   public function testGetNotificationListSuccess_Favourite_NotFirstPage()
   {
        config([
            'filesystems.disks.s3.region'  => 'eu-central-1',
            'filesystems.disks.s3.bucket'  => 'sing-plus',
        ]);

        $user = factory(\SingPlus\Domains\Users\Models\User::class)->create();
        factory(\SingPlus\Domains\Users\Models\UserProfile::class)->create([
            'user_id' => $user->id,
        ]);

        // prepare feeds
        $data = $this->prepareFeeds($user->id);

        $this->mockHttpClient([
            [
                'body'  => json_encode([
                    'code'  => 0,
                    'data'  => [
                        'relation'  => [
                            $data->feed->three->operator_user_id    => [
                                'is_following'  => false,
                                'follow_at'     => null,
                                'is_follower'   => false,
                                'followed_at'   => null,
                            ],
                        ],
                    ],
                ]),
            ],
        ]);

        $response = $this->actingAs($user)
            ->getJson('v3/messages/feeds?' . http_build_query([
                    'type'  => "work_favourite",
                    'feedId'  => $data->feed->three->id,
                ]));

        $response->assertJson(['code' => 0]);

        $feeds = (json_decode($response->getContent()))->data->feeds;
        self::assertCount(0, $feeds);
   }

    public function testGetNotificationListSuccess_Followed()
    {
        $this->expectsEvents(\SingPlus\Events\Feeds\FeedReaded::class);
        config([
            'filesystems.disks.s3.region'  => 'eu-central-1',
            'filesystems.disks.s3.bucket'  => 'sing-plus',
        ]);

        $user = factory(\SingPlus\Domains\Users\Models\User::class)->create();
        factory(\SingPlus\Domains\Users\Models\UserProfile::class)->create([
            'user_id' => $user->id,
        ]);

        $operator = factory(\SingPlus\Domains\Users\Models\User::class)->create();
        $operatorProfile = factory(\SingPlus\Domains\Users\Models\UserProfile::class)->create([
            'user_id'   => $operator->id,
            'avatar'    => 'operator-avatar',
            'nickname'  => 'lisi',
        ]);

        // prepare feeds
        $feedTwo = factory(\SingPlus\Domains\Feeds\Models\Feed::class)->create([
            'user_id'           => $user->id,
            'operator_user_id'  => $operator->id,
            'type'              => 'user_followed',
            'status'            => 1,
            'is_read'           => true,
            'display_order'     => 200,
            'created_at'        => '2016-08-01',
        ]);

        $this->mockHttpClient([
            [
                'body'  => json_encode([
                    'code'  => 0,
                    'data'  => [
                        'relation'  => [
                            $operator->id   => [
                                'is_following'  => false,
                                'follow_at'     => null,
                                'is_follower'   => false,
                                'followed_at'   => null,
                            ],
                        ],
                    ],
                ]),
            ],
        ]);

        $response = $this->actingAs($user)
            ->getJson('v3/messages/feeds?' . http_build_query([
                    'type'  => "user_followed",
                ]));

        $response->assertJson(['code' => 0]);
        $feeds = (json_decode($response->getContent()))->data->feeds;
        self::assertCount(1, $feeds);
        self::assertEquals($feedTwo->id, $feeds[0]->feedId);
    }

    public function testGetNotificationListSuccess_Followed_NotFirstPage()
    {
        config([
            'filesystems.disks.s3.region'  => 'eu-central-1',
            'filesystems.disks.s3.bucket'  => 'sing-plus',
        ]);

        $user = factory(\SingPlus\Domains\Users\Models\User::class)->create();
        factory(\SingPlus\Domains\Users\Models\UserProfile::class)->create([
            'user_id' => $user->id,
        ]);

        $operator = factory(\SingPlus\Domains\Users\Models\User::class)->create();
        $operatorProfile = factory(\SingPlus\Domains\Users\Models\UserProfile::class)->create([
            'user_id'   => $operator->id,
            'avatar'    => 'operator-avatar',
            'nickname'  => 'lisi',
        ]);

        // prepare feeds
        $feedTwo = factory(\SingPlus\Domains\Feeds\Models\Feed::class)->create([
            'user_id'           => $user->id,
            'operator_user_id'  => $operator->id,
            'type'              => 'user_followed',
            'status'            => 1,
            'is_read'           => true,
            'display_order'     => 200,
            'created_at'        => '2016-08-01',
        ]);

        $feedThree = factory(\SingPlus\Domains\Feeds\Models\Feed::class)->create([
            'user_id'           => $user->id,
            'operator_user_id'  => $operator->id,
            'type'              => 'user_followed',
            'status'            => 1,
            'is_read'           => true,
            'display_order'     => 300,
            'created_at'        => '2016-08-01',
        ]);

        $this->mockHttpClient([
            [
                'body'  => json_encode([
                    'code'  => 0,
                    'data'  => [
                        'relation'  => [
                            $operator->id   => [
                                'is_following'  => false,
                                'follow_at'     => null,
                                'is_follower'   => false,
                                'followed_at'   => null,
                            ],
                        ],
                    ],
                ]),
            ],
        ]);

        $response = $this->actingAs($user)
            ->getJson('v3/messages/feeds?' . http_build_query([
                    'type'  => "user_followed",
                    'feedId'  => $feedThree->id,
                ]));

        $response->assertJson(['code' => 0]);
        $feeds = (json_decode($response->getContent()))->data->feeds;
        self::assertCount(1, $feeds);
        self::assertEquals($feedTwo->id, $feeds[0]->feedId);
    }

  //=================================
  //        createWorkTransmitFeed
  //=================================
  public function testCreateWorkTransmitFeedSuccess()
  {
    $this->expectsEvents(\SingPlus\Events\FeedCreated::class);
    $user = factory(\SingPlus\Domains\Users\Models\User::class)->create();
    factory(\SingPlus\Domains\Users\Models\UserProfile::class)->create([
      'user_id' => $user->id,
    ]);

    $music = factory(\SingPlus\Domains\Musics\Models\Music::class)->create();
    $workAuthor = factory(\SingPlus\Domains\Users\Models\User::class)->create();
    $work = factory(\SingPlus\Domains\Works\Models\Work::class)->create([
      'user_id'   => $workAuthor->id,
      'music_id'  => $music->id,
      'transmit_count'  => 12,
    ]);
    $counterMock = Mockery::mock(\Illuminate\Contracts\Cache\Store::class);
    $counterMock->shouldReceive('increment')
                ->once()
                ->with('feeds', 100)
                ->andReturn(100);
    Cache::shouldReceive('driver')
         ->once()
         ->with('counter')
         ->andReturn($counterMock);
    Cache::shouldReceive('get')
         ->once()
         ->with(sprintf('work:%s:listennum', $work->id))
         ->andReturn(100);

    $dailyTaskService = $this->mockDailyTaskService();
    $dailyTaskService->shouldReceive('resetDailyTaskLists')
        ->once()
        ->with($user->id, null)
        ->andReturn();
    $dailyTaskService->shouldReceive('finisheDailyTask')
        ->once()
        ->with($user->id, DailyTask::TYPE_SHARE)
        ->andReturn();

    $response = $this->actingAs($user)
                     ->postJson('v3/messages/transmit', [
                        'workId'  => $work->id,
                        'channel' => 'whatsapp',
                     ])->assertJson(['code' => 0]);
    self::assertDatabaseHas('feeds', [
      'user_id'           => $workAuthor->id,
      'operator_user_id'  => $user->id,
      'type'              => 'work_transmit',
      'status'            => 1,
      'is_read'           => 0,
      'display_order'     => 100,
      'detail'            => [
                                'work_id'   => $work->id,
                                'music_id'  => $music->id,
                                'channel'   => 'whatsapp',
                              ],
    ]);
    self::assertDatabaseHas('works', [
      '_id'             => $work->id,
      'transmit_count'  => 13,          // increment by one
    ]);
  }

    public function testCreateWorkTransmitFeedSuccess_SelfTransimit()
    {
        $this->doesntExpectEvents(\SingPlus\Events\FeedCreated::class);
        $user = factory(\SingPlus\Domains\Users\Models\User::class)->create();
        factory(\SingPlus\Domains\Users\Models\UserProfile::class)->create([
            'user_id' => $user->id,
        ]);

        $music = factory(\SingPlus\Domains\Musics\Models\Music::class)->create();
        $workAuthor = $user;
        $work = factory(\SingPlus\Domains\Works\Models\Work::class)->create([
            'user_id'   => $workAuthor->id,
            'music_id'  => $music->id,
            'transmit_count'  => 12,
        ]);
        $counterMock = Mockery::mock(\Illuminate\Contracts\Cache\Store::class);
        $counterMock->shouldReceive('increment')
            ->never()
            ->with('feeds', 100)
            ->andReturn(100);
        Cache::shouldReceive('driver')
            ->never()
            ->with('counter')
            ->andReturn($counterMock);
        Cache::shouldReceive('get')
            ->once()
            ->with(sprintf('work:%s:listennum', $work->id))
            ->andReturn(100);

        $dailyTaskService = $this->mockDailyTaskService();
        $dailyTaskService->shouldReceive('resetDailyTaskLists')
            ->once()
            ->with($user->id, null)
            ->andReturn();
        $dailyTaskService->shouldReceive('finisheDailyTask')
            ->once()
            ->with($user->id, DailyTask::TYPE_SHARE)
            ->andReturn();

        $response = $this->actingAs($user)
            ->postJson('v3/messages/transmit', [
                'workId'  => $work->id,
                'channel' => 'whatsapp',
            ])->assertJson(['code' => 0]);
        self::assertDatabaseMissing('feeds', [
            'user_id'           => $workAuthor->id,
            'operator_user_id'  => $user->id,
            'type'              => 'work_transmit',
            'status'            => 1,
            'is_read'           => 0,
            'display_order'     => 100,
            'detail'            => [
                'work_id'   => $work->id,
                'music_id'  => $music->id,
                'channel'   => 'whatsapp',
            ],
        ]);
        self::assertDatabaseHas('works', [
            '_id'             => $work->id,
            'transmit_count'  => 13,          // increment by one
        ]);
    }


  public function testCreateWorkTransmitFeedFailed_ChannelInvalid()
  {
    $user = factory(\SingPlus\Domains\Users\Models\User::class)->create();
    factory(\SingPlus\Domains\Users\Models\UserProfile::class)->create([
      'user_id' => $user->id,
    ]);
    $work = factory(\SingPlus\Domains\Works\Models\Work::class)->create();

    $response = $this->actingAs($user)
                     ->postJson('v3/messages/transmit', [
                        'workId'  => $work->id,
                        'channel' => 'aaaaaaaaa',
                     ])->assertJson(['code' => 10501]);
  }

  public function testCreateWorkTransmitFeedFailed_WorkNotExists()
  {
    $user = factory(\SingPlus\Domains\Users\Models\User::class)->create();
    factory(\SingPlus\Domains\Users\Models\UserProfile::class)->create([
      'user_id' => $user->id,
    ]);

    $response = $this->actingAs($user)
                     ->postJson('v3/messages/transmit', [
                        'workId'  => 'db630c69060a402990537a526c0654cf',
                        'channel' => 'whatsapp',
                     ])->assertJson(['code' => 10402]);
  }

    public function testCreateWorkTransmitFeedSuccess_WithoutLogin()
    {
        $this->doesntExpectEvents(\SingPlus\Events\FeedCreated::class);
        $user = factory(\SingPlus\Domains\Users\Models\User::class)->create();
        factory(\SingPlus\Domains\Users\Models\UserProfile::class)->create([
            'user_id' => $user->id,
        ]);

        $music = factory(\SingPlus\Domains\Musics\Models\Music::class)->create();
        $workAuthor = factory(\SingPlus\Domains\Users\Models\User::class)->create();
        $work = factory(\SingPlus\Domains\Works\Models\Work::class)->create([
            'user_id'   => $workAuthor->id,
            'music_id'  => $music->id,
            'transmit_count'  => 12,
        ]);
        $counterMock = Mockery::mock(\Illuminate\Contracts\Cache\Store::class);
        $counterMock->shouldReceive('increment')
            ->never()
            ->with('feeds', 100)
            ->andReturn(100);
        Cache::shouldReceive('driver')
            ->never()
            ->with('counter')
            ->andReturn($counterMock);
        Cache::shouldReceive('get')
            ->once()
            ->with(sprintf('work:%s:listennum', $work->id))
            ->andReturn(100);

        $response = $this->postJson('v3/messages/transmit', [
                'workId'  => $work->id,
                'channel' => 'whatsapp',
            ])->assertJson(['code' => 0]);
        self::assertDatabaseMissing('feeds', [
            'user_id'           => $workAuthor->id,
            'operator_user_id'  => $user->id,
            'type'              => 'work_transmit',
            'status'            => 1,
            'is_read'           => 0,
            'display_order'     => 100,
            'detail'            => [
                'work_id'   => $work->id,
                'music_id'  => $music->id,
                'channel'   => 'whatsapp',
            ],
        ]);
        self::assertDatabaseHas('works', [
            '_id'             => $work->id,
            'transmit_count'  => 13,          // increment by one
        ]);
    }

    public function testCreateWorkTransmitFeedFailed_ChannelInvalid_WithoutLogin()
    {
        $user = factory(\SingPlus\Domains\Users\Models\User::class)->create();
        factory(\SingPlus\Domains\Users\Models\UserProfile::class)->create([
            'user_id' => $user->id,
        ]);
        $work = factory(\SingPlus\Domains\Works\Models\Work::class)->create();

        $response = $this->postJson('v3/messages/transmit', [
                'workId'  => $work->id,
                'channel' => 'aaaaaaaaa',
            ])->assertJson(['code' => 10501]);
    }

    public function testCreateWorkTransmitFeedFailed_WorkNotExists_WithoutLogin()
    {
        $user = factory(\SingPlus\Domains\Users\Models\User::class)->create();
        factory(\SingPlus\Domains\Users\Models\UserProfile::class)->create([
            'user_id' => $user->id,
        ]);

        $response = $this->postJson('v3/messages/transmit', [
                'workId'  => 'db630c69060a402990537a526c0654cf',
                'channel' => 'whatsapp',
            ])->assertJson(['code' => 10402]);
    }

  //=================================
  //        getUserMixed
  //=================================
  public function testGetUserMixedSuccess()
  {
    $this->expectsEvents(\SingPlus\Events\Feeds\FeedReaded::class);
    $user = factory(\SingPlus\Domains\Users\Models\User::class)->create();
    factory(\SingPlus\Domains\Users\Models\UserProfile::class)->create([
      'user_id' => $user->id,
    ]);

    $data = $this->prepareFeeds($user->id);

    $response = $this->actingAs($user)
                     ->getJson('v4/messages/feeds/comments')
                     ->assertJson(['code' => 0]);
    $response = (json_decode($response->getContent()));
    $feeds = $response->data->feeds;

    self::assertCount(4, $feeds);

    // check chorus join
    self::assertEquals($data->feed->nine->id, $feeds[0]->feedId);
    self::assertEquals($data->work->two->id, $feeds[0]->workId);
    self::assertEquals('chorus-start', $feeds[0]->workName);
    self::assertEquals('chorus-join, good', $feeds[0]->workChorusJoinInfo->workDescription);

    // check comments[1]
    self::assertEquals($data->feed->seven->id, $feeds[1]->feedId);
    self::assertEquals('no accompaniment', $feeds[1]->musicName);  // work name be used first
    self::assertEquals(false, $feeds[1]->hasRead);
    self::assertEquals(true, $feeds[1]->isNormal);
    self::assertEquals($data->work->one->id, $feeds[1]->workId);
    self::assertEquals($data->work->one->music_id, $feeds[1]->musicId);

    // check comments[2]
    self::assertEquals($data->feed->six->id, $feeds[2]->feedId);
    self::assertEquals('no accompaniment', $feeds[2]->musicName);  // work name be used first
    self::assertEquals(true, $feeds[2]->hasRead);
    self::assertEquals(false, $feeds[2]->isNormal);
    self::assertEquals($data->work->one->id, $feeds[2]->workId);
    self::assertEquals($data->work->one->music_id, $feeds[2]->musicId);

    // check comments[3]
    self::assertEquals(true, $feeds[3]->isNormal);
    self::assertEquals(true, $feeds[3]->hasRead);
  }

  //=================================
  //        getUserCommentList
  //=================================
  public function testGetUserCommentListSuccess()
  {
    $this->expectsEvents(\SingPlus\Events\Feeds\FeedReaded::class);
    $user = factory(\SingPlus\Domains\Users\Models\User::class)->create();
    factory(\SingPlus\Domains\Users\Models\UserProfile::class)->create([
      'user_id' => $user->id,
    ]);

    $data = $this->prepareFeeds($user->id);

    $response = $this->actingAs($user)
                     ->getJson('v3/messages/feeds/comments')
                     ->assertJson(['code' => 0]);
    $response = (json_decode($response->getContent()));
    $comments = $response->data->comments;

    self::assertCount(5, $comments);


    // check comments[0]
    self::assertEquals($data->feed->ten->id, $comments[0]->feedId);
    self::assertEquals(false, $comments[0]->isNormal);
    self::assertEquals(false, $comments[0]->hasRead);
    self::assertEquals(1, $comments[0]->commentType);

    // check comments[1]
    self::assertEquals($data->feed->eleven->id, $comments[1]->feedId);
    self::assertEquals(false, $comments[1]->isNormal);
    self::assertEquals(false, $comments[1]->hasRead);
    self::assertEquals(2, $comments[1]->commentType);
    // check comments[2]
    self::assertEquals($data->feed->seven->id, $comments[2]->feedId);
    self::assertEquals('no accompaniment', $comments[2]->musicName);
    self::assertEquals(false, $comments[2]->hasRead);
    self::assertEquals(true, $comments[2]->isNormal);
    self::assertEquals($data->work->one->id, $comments[2]->workId);
    self::assertEquals($data->work->one->music_id, $comments[2]->musicId);

    // check comments[3]
    self::assertEquals($data->feed->six->id, $comments[3]->feedId);
    self::assertEquals('no accompaniment', $comments[3]->musicName);  // work name be used first
    self::assertEquals(true, $comments[3]->hasRead);
    self::assertEquals(false, $comments[3]->isNormal);
    self::assertEquals($data->work->one->id, $comments[3]->workId);
    self::assertEquals($data->work->one->music_id, $comments[3]->musicId);

    // check comments[4]
    self::assertEquals(true, $comments[4]->isNormal);
    self::assertEquals(true, $comments[4]->hasRead);
  }

    //=================================
    //        getUserCommentList
    //=================================
    public function testGetUserCommentListSuccess_WithGiftFeedId()
    {
        $this->expectsEvents(\SingPlus\Events\Feeds\FeedReaded::class);
        $user = factory(\SingPlus\Domains\Users\Models\User::class)->create();
        factory(\SingPlus\Domains\Users\Models\UserProfile::class)->create([
            'user_id' => $user->id,
        ]);

        $operator = factory(\SingPlus\Domains\Users\Models\User::class)->create();
        $operatorProfile = factory(\SingPlus\Domains\Users\Models\UserProfile::class)->create([
            'user_id'   => $operator->id,
            'avatar'    => 'operator-avatar',
            'nickname'  => 'zhangsan',
        ]);

        $musicOne = factory(\SingPlus\Domains\Musics\Models\Music::class)->create([
            'name'    => 'music-one',
            'status'  => 1,
        ]);

        $workOne = factory(\SingPlus\Domains\Works\Models\Work::class)->create([
            'user_id' => $operator->id,
            'music_id' => $musicOne->id,
        ]);

        $gift = factory(\SingPlus\Domains\Gifts\Models\Gift::class)->create();

        $giftHistory = factory(\SingPlus\Domains\Gifts\Models\GiftHistory::class)->create([
            'sender_id' => $user->id,
            'work_id' => $workOne->id,
            'receiver_id' => $workOne->user_id,
            'amount' => 5,
            'display_order' => 100,
            'gift_info' => [
                "id"=> $gift->id,
                "type"=> "type a",
                "name"=> "gift A",
                "icon"=> [
                    "icon_small"=> "xxxx.png",
                    "icon_big"=> "xxxx.png"
                ],
                "coins"=> 10,
                "sold_amount"=> 0,
                "sold_coin_amount"=> 0,
                "status"=> 1,
                "popularity"=> 20,
                "animation"=> [
                    "type"=> 1,
                    "url"=> "xxxx.gift",
                    "duration"=> 1
                ]

            ]
        ]);

        $feedOne = factory(\SingPlus\Domains\Feeds\Models\Feed::class)->create([
            'user_id'           => $operator->id,
            'operator_user_id'  => $user->id,
            'work_id'           => $workOne->id,
            'type'              => 'gift_send_for_work',
            'detail'            => [
                'work_id'   => $workOne->id,
                'giftHistory_id'   => $giftHistory->id,
            ],
            'status'            => 1,
            'display_order'     => 100,
            'created_at'        => '2016-07-01',
        ]);

        $commentOne = factory(\SingPlus\Domains\Works\Models\Comment::class)->create([
            'work_id'         => $workOne->id,
            'content'         => 'reply comment gift one',
            'author_id'       => $operator->id,
            'replied_user_id' => $user->id,
            'status'          => 0,
            'type'    => 4,
            'gift_feed_id' => $feedOne->id,
        ]);

        $commentFeed = factory(\SingPlus\Domains\Feeds\Models\Feed::class)->create([
            'user_id'           => $user->id,
            'operator_user_id'  => $operator->id,
            'work_id'           => $workOne->id,
            'type'              => 'work_comment',
            'detail'            => [
                'work_id'     => $workOne->id,
                'comment_id'  => $commentOne->id,
            ],
            'status'            => 1,
            'is_read'           => true,
            'display_order'     => 500,
            'created_at'        => '2016-01-02',
        ]);


        $operatorTwo = factory(\SingPlus\Domains\Users\Models\User::class)->create();
        $operatorProfile = factory(\SingPlus\Domains\Users\Models\UserProfile::class)->create([
            'user_id'   => $operator->id,
            'avatar'    => 'operator-avatar',
            'nickname'  => 'zhangsan',
        ]);
        $musicOne = factory(\SingPlus\Domains\Musics\Models\Music::class)->create([
            'name'    => 'music-one',
            'status'  => 1,
        ]);

        $workOne = factory(\SingPlus\Domains\Works\Models\Work::class)->create([
            'user_id' => $operator->id,
            'music_id' => $musicOne->id,
        ]);

        $gift = factory(\SingPlus\Domains\Gifts\Models\Gift::class)->create();

        $giftHistory = factory(\SingPlus\Domains\Gifts\Models\GiftHistory::class)->create([
            'sender_id' => $user->id,
            'work_id' => $workOne->id,
            'receiver_id' => $workOne->user_id,
            'amount' => 5,
            'display_order' => 200,
            'gift_info' => [
                "id"=> $gift->id,
                "type"=> "type a",
                "name"=> "gift A",
                "icon"=> [
                    "icon_small"=> "xxxx.png",
                    "icon_big"=> "xxxx.png"
                ],
                "coins"=> 10,
                "sold_amount"=> 0,
                "sold_coin_amount"=> 0,
                "status"=> 1,
                "popularity"=> 20,
                "animation"=> [
                    "type"=> 1,
                    "url"=> "xxxx.gift",
                    "duration"=> 1
                ]

            ]
        ]);

        $feedOne = factory(\SingPlus\Domains\Feeds\Models\Feed::class)->create([
            'user_id'           => $operator->id,
            'operator_user_id'  => $user->id,
            'work_id'           => $workOne->id,
            'type'              => 'gift_send_for_work',
            'detail'            => [
                'work_id'   => $workOne->id,
                'giftHistory_id'   => $giftHistory->id,
            ],
            'status'            => 1,
            'display_order'     => 200,
            'created_at'        => '2016-07-01',
        ]);

        $commentOne = factory(\SingPlus\Domains\Works\Models\Comment::class)->create([
            'work_id'         => $workOne->id,
            'content'         => 'reply comment gift two',
            'author_id'       => $operator->id,
            'replied_user_id' => $user->id,
            'status'          => 0,
            'type'    => 4,
            'gift_feed_id' => $feedOne->id,
        ]);

        $commentFeed = factory(\SingPlus\Domains\Feeds\Models\Feed::class)->create([
            'user_id'           => $user->id,
            'operator_user_id'  => $operator->id,
            'work_id'           => $workOne->id,
            'type'              => 'work_comment',
            'detail'            => [
                'work_id'     => $workOne->id,
                'comment_id'  => $commentOne->id,
            ],
            'status'            => 1,
            'is_read'           => true,
            'display_order'     => 1100,
            'created_at'        => '2016-01-02',
        ]);

        $data = $this->prepareFeeds($user->id);

        $response = $this->actingAs($user)
            ->getJson('v3/messages/feeds/comments')
            ->assertJson(['code' => 0]);

        $response = (json_decode($response->getContent()));
        $comments = $response->data->comments;
        self::assertCount(7, $comments);




    }

  public function testGetUserCommentListSuccess_WithPagination()
  {
    $user = factory(\SingPlus\Domains\Users\Models\User::class)->create();
    factory(\SingPlus\Domains\Users\Models\UserProfile::class)->create([
      'user_id' => $user->id,
    ]);

    $data = $this->prepareFeeds($user->id);

    $response = $this->actingAs($user)
                     ->getJson('v3/messages/feeds/comments?' . http_build_query([
                        'feedId'  => $data->feed->eight->id,
                        'size'    => 1,
                     ]))
                     ->assertJson(['code' => 0]);
    $response = (json_decode($response->getContent()));
    $comments = $response->data->comments;

    self::assertCount(1, $comments);

    self::assertEquals($data->feed->seven->id, $comments[0]->feedId);
    self::assertEquals('no accompaniment', $comments[0]->musicName);
    self::assertEquals(false, $comments[0]->hasRead);
    self::assertEquals(true, $comments[0]->isNormal);
    self::assertEquals($data->work->one->id, $comments[0]->workId);
    self::assertEquals($data->work->one->music_id, $comments[0]->musicId);
  }

    public function testGetUserCommentListSuccess_CompatCommentTypeForOldClient()
    {
        $this->expectsEvents(\SingPlus\Events\Feeds\FeedReaded::class);
        $user = factory(\SingPlus\Domains\Users\Models\User::class)->create();
        factory(\SingPlus\Domains\Users\Models\UserProfile::class)->create([
            'user_id' => $user->id,
        ]);

        $operator = factory(\SingPlus\Domains\Users\Models\User::class)->create();
        $operatorProfile = factory(\SingPlus\Domains\Users\Models\UserProfile::class)->create([
            'user_id'   => $operator->id,
            'avatar'    => 'operator-avatar',
            'nickname'  => 'zhangsan',
        ]);
        $musicOne = factory(\SingPlus\Domains\Musics\Models\Music::class)->create([
            'name'    => 'music-one',
            'status'  => 1,
        ]);

        $workOne = factory(\SingPlus\Domains\Works\Models\Work::class)->create([
            'music_id'  => $musicOne->id,
            'name'      => 'no accompaniment',
            'status'    => 1,
        ]);

        $commentOne = factory(\SingPlus\Domains\Works\Models\Comment::class)->create([
            'work_id'         => $workOne->id,
            'content'         => 'comment one',
            'author_id'       => $operator->id,
            'replied_user_id' => $user->id,
            'status'          => 1,
            'type'    => 3
        ]);

        $feedOne = factory(\SingPlus\Domains\Feeds\Models\Feed::class)->create([
            'user_id'           => $user->id,
            'operator_user_id'  => $operator->id,
            'work_id'           => $workOne->id,
            'type'              => 'work_comment',
            'detail'            => [
                'work_id'     => $workOne->id,
                'comment_id'  => $commentOne->id,
            ],
            'status'            => 1,
            'display_order'     => 100,
            'created_at'        => '2016-07-01',
        ]);


        $response = $this->actingAs($user)
            ->getJson('v3/messages/feeds/comments',['X-Version'=> '3.0.0'])
            ->assertJson(['code' => 0]);
        $response = (json_decode($response->getContent()));
        $comments = $response->data->comments;

        self::assertCount(1, $comments);
        self::assertEquals(2, $comments[0]->commentType);


    }


  //=================================
  //        readUserFollowedFeed
  //=================================
  public function testReadUserFollowedFeedSuccess()
  {
    $user = factory(\SingPlus\Domains\Users\Models\User::class)->create();
    factory(\SingPlus\Domains\Users\Models\UserProfile::class)->create([
      'user_id' => $user->id,
    ]);
    $feedOne = factory(\SingPlus\Domains\Feeds\Models\Feed::class)->create([
      'user_id' => $user->id,
      'type'    => 'user_followed',
      'status'  => 1,
      'is_read' => 0,
    ]);
    $feedTwo = factory(\SingPlus\Domains\Feeds\Models\Feed::class)->create([
      'user_id' => $user->id,
      'type'    => 'user_followed',
      'status'  => 1,
      'is_read' => 0,
    ]);
    $feedThree = factory(\SingPlus\Domains\Feeds\Models\Feed::class)->create([
      'user_id' => $user->id,
      'type'    => 'user_followed',
      'status'  => 1,
      'is_read' => 1,
    ]);
    $feedFour = factory(\SingPlus\Domains\Feeds\Models\Feed::class)->create([
      'user_id' => $user->id,
      'type'    => 'work_transmit',
      'status'  => 1,
      'is_read' => 0,
    ]);
    $feedFive = factory(\SingPlus\Domains\Feeds\Models\Feed::class)->create([
      'user_id' => 'beb46bc1ca914a8daa95a4b6fa22962b',
      'type'    => 'user_followed',
      'status'  => 1,
      'is_read' => 0,
    ]);

    $response = $this->actingAs($user)
                     ->postJson('v3/messages/feeds/followed/read');
    $response->assertJson([
      'code'  => 0,
      'data'  => [
        'readNum' => 2,
      ],
    ]);

    self::assertDatabaseHas('feeds', [
      '_id'     => $feedOne->id,
      'is_read' => 1,
    ]);
    self::assertDatabaseHas('feeds', [
      '_id'     => $feedTwo->id,
      'is_read' => 1,
    ]);
  }

    //=================================
    //        getUserGiftForWorkList
    //=================================
    public function testGetGiftsFeedSuccess(){
        $this->expectsEvents(\SingPlus\Events\Feeds\FeedReaded::class);
        $user = factory(\SingPlus\Domains\Users\Models\User::class)->create();
        factory(\SingPlus\Domains\Users\Models\UserProfile::class)->create([
            'user_id' => $user->id,
        ]);

        $data = $this->prepareGiftsFeeds($user->id);
        $giftHistoryOne = $data->giftHistory->one;
        $giftHistoryTwo = $data->giftHistory->two;
        $giftHistoryThree = $data->giftHistory->three;
        $giftHistoryFour = $data->giftHistory->four;
        
        $giftHistoryOne->gift_info = array_merge(
            $giftHistoryOne->gift_info, ['name' => [
                'en'    => 'En history one',
                'hi'    => 'Hi history one',
            ]]);
        $giftHistoryOne->save();

        $giftHistoryTwo->gift_info = array_merge(
            $giftHistoryTwo->gift_info, ['name' => [
                'en'    => 'En history two',
                'hi'    => 'Hi history two',
            ]]);
        $giftHistoryTwo->save();

        $giftHistoryThree->gift_info = array_merge(
            $giftHistoryThree->gift_info, ['name' => [
                'en'    => 'En history three',
                'hi'    => 'Hi history three',
            ]]);
        $giftHistoryThree->save();

        $giftHistoryFour->gift_info = array_merge(
            $giftHistoryFour->gift_info, ['name' => 'compaliable string format']);
        $giftHistoryFour->save();

        // set locale
        $response = $this->actingAs($user)
            ->getJson('v3/messages/feeds/gifts', [
                'X-Language'    => 'hi',    // set locale
            ])
            ->assertJson(['code' => 0]);
        $response = (json_decode($response->getContent()));
        $gifts = $response->data->gifts;
        self::assertCount(4, $gifts);
        self::assertEquals($data->gift->one->id, $gifts[0]->gift->id);
        // locale: hi take effect
        self::assertEquals('Hi history one', $gifts[0]->gift->name);
        self::assertEquals('compaliable string format', $gifts[3]->gift->name);
    }

    public function testGetGiftsFeedSuccess_FallbackLocaleUsed(){
        $this->expectsEvents(\SingPlus\Events\Feeds\FeedReaded::class);
        $user = factory(\SingPlus\Domains\Users\Models\User::class)->create();
        factory(\SingPlus\Domains\Users\Models\UserProfile::class)->create([
            'user_id' => $user->id,
        ]);

        $data = $this->prepareGiftsFeeds($user->id);
        $giftHistoryOne = $data->giftHistory->one;
        $giftHistoryTwo = $data->giftHistory->two;
        $giftHistoryThree = $data->giftHistory->three;
        $giftHistoryFour = $data->giftHistory->four;
        
        $giftHistoryOne->gift_info = array_merge(
            $giftHistoryOne->gift_info, ['name' => [
                'en'    => 'En history one',
                'hi'    => 'Hi history one',
            ]]);
        $giftHistoryOne->save();

        $giftHistoryTwo->gift_info = array_merge(
            $giftHistoryTwo->gift_info, ['name' => [
                'en'    => 'En history two',
                'hi'    => 'Hi history two',
            ]]);
        $giftHistoryTwo->save();

        $giftHistoryThree->gift_info = array_merge(
            $giftHistoryThree->gift_info, ['name' => [
                'en'    => 'En history three',
                'hi'    => 'Hi history three',
            ]]);
        $giftHistoryThree->save();

        $giftHistoryFour->gift_info = array_merge(
            $giftHistoryFour->gift_info, ['name' => [
                'en'    => 'En history four',
                'hi'    => 'Hi history four',
            ]]);
        $giftHistoryFour->save();

        $response = $this->actingAs($user)
            ->getJson('v3/messages/feeds/gifts', [
                'X-Language'    => 'mm',    // mm invalid, fallback_locale be used
            ])
            ->assertJson(['code' => 0]);
        $response = (json_decode($response->getContent()));
        $gifts = $response->data->gifts;
        self::assertCount(4, $gifts);
        self::assertEquals($data->gift->one->id, $gifts[0]->gift->id);
        // locale: hi take effect
        self::assertEquals('En history one', $gifts[0]->gift->name);
    }

    public function testGetGiftsFeedSuccess_WithEmptyData(){
        $this->doesntExpectEvents(\SingPlus\Events\Feeds\FeedReaded::class);
        $user = factory(\SingPlus\Domains\Users\Models\User::class)->create();
        factory(\SingPlus\Domains\Users\Models\UserProfile::class)->create([
            'user_id' => $user->id,
        ]);
        $response = $this->actingAs($user)
            ->getJson('v3/messages/feeds/gifts')
            ->assertJson(['code' => 0]);
        $response = (json_decode($response->getContent()));
        $gifts = $response->data->gifts;
        self::assertCount(0,$gifts);
    }

    public function testGetGiftsFeedSuccess_WithEmptyGiftsInfo(){
        $this->expectsEvents(\SingPlus\Events\Feeds\FeedReaded::class);
        $user = factory(\SingPlus\Domains\Users\Models\User::class)->create();
        factory(\SingPlus\Domains\Users\Models\UserProfile::class)->create([
            'user_id' => $user->id,
        ]);

        $operatorOne = factory(\SingPlus\Domains\Users\Models\User::class)->create();
        $operatorProfileOne = factory(\SingPlus\Domains\Users\Models\UserProfile::class)->create([
            'user_id'   => $operatorOne->id,
            'avatar'    => 'operator-avatar',
            'nickname'  => 'zhangsan',
        ]);

        $musicOne = factory(\SingPlus\Domains\Musics\Models\Music::class)->create([
            'name'    => 'music-one',
            'status'  => 1,
        ]);

        $workOne = factory(\SingPlus\Domains\Works\Models\Work::class)->create([
            'music_id'  => $musicOne->id,
            'name'      => 'work one',
            'status'    => 1,
            'user_id'   => $user->id
        ]);

        $feedOne = factory(\SingPlus\Domains\Feeds\Models\Feed::class)->create([
            'user_id' => $user->id,
            'operator_user_id' => $operatorOne->id,
            'type' => Feed::TYPE_GIFT_SEND_FOR_WORK,
            'display_order' => 100,
        ]);

        $feedTwo = factory(\SingPlus\Domains\Feeds\Models\Feed::class)->create([
            'user_id' => $user->id,
            'operator_user_id' => $operatorOne->id,
            'type' => Feed::TYPE_GIFT_SEND_FOR_WORK,
            'display_order' => 200,
        ]);

        $response = $this->actingAs($user)
            ->getJson('v3/messages/feeds/gifts')
            ->assertJson(['code' => 0]);
        $response = (json_decode($response->getContent()));
        $gifts = $response->data->gifts;
        self::assertCount(0, $gifts);
    }


    private function prepareGiftsFeeds(string $userId){
        $operatorOne = factory(\SingPlus\Domains\Users\Models\User::class)->create();
        $operatorProfileOne = factory(\SingPlus\Domains\Users\Models\UserProfile::class)->create([
            'user_id'   => $operatorOne->id,
            'avatar'    => 'operator-avatar',
            'nickname'  => 'zhangsan',
        ]);

        $operatorTwo = factory(\SingPlus\Domains\Users\Models\User::class)->create();
        $operatorProfileTwo = factory(\SingPlus\Domains\Users\Models\UserProfile::class)->create([
            'user_id'   => $operatorTwo->id,
            'avatar'    => 'operator-avatar',
            'nickname'  => 'lisi',
        ]);

        $musicOne = factory(\SingPlus\Domains\Musics\Models\Music::class)->create([
            'name'    => 'music-one',
            'status'  => 1,
        ]);

        $musicTwo = factory(\SingPlus\Domains\Musics\Models\Music::class)->create([
            'name'    => 'music-two',
            'status'  => 1,
        ]);

        $workOne = factory(\SingPlus\Domains\Works\Models\Work::class)->create([
            'music_id'  => $musicOne->id,
            'name'      => 'work one',
            'status'    => 1,
            'user_id'   => $userId
        ]);

        $workTwo = factory(\SingPlus\Domains\Works\Models\Work::class)->create([
            'music_id'  => $musicTwo->id,
            'name'  => 'work two',
            'status'    => 0,
            'user_id'   => $userId,
        ]);

        $giftOne = factory(\SingPlus\Domains\Gifts\Models\Gift::class)->create([
              'name' => 'gift A',
              'type' => 'A',
              'icon' => [
                  'small' => 'smallIconxxx.png',
                  'big'   => 'bigIconxxx.png',
              ],
              'coins' => 10,
        ]);

        $giftTwo = factory(\SingPlus\Domains\Gifts\Models\Gift::class)->create([
            'name' => 'gift B',
            'type' => 'B',
            'icon' => [
                'icon_small' => 'smallIconxxx.png',
                'icon_big'   => 'bigIconxxx.png',
            ],
            'coins' => 10,
        ]);


        $giftHistoryOne = factory(\SingPlus\Domains\Gifts\Models\GiftHistory::class)->create([
                'gift_info' => [
                    'id' => $giftOne->id,
                    'type' => $giftOne->type,
                    'name' => $giftOne->type,
                    'icon' => [
                        'icon_small' => 'smallIconxxx.png',
                        'icon_big'   => 'bigIconxxx.png',
                    ],
                    'coins' => $giftOne->coins
                ],
                'sender_id' => $operatorOne->id,
                'receiver_id' => $userId,
                'work_id' => $workOne->id,
                'amount'  => '3',
                'display_order' => 100,

        ]);

        $giftHistoryTwo = factory(\SingPlus\Domains\Gifts\Models\GiftHistory::class)->create([
            'gift_info' => [
                'id' => $giftTwo->id,
                'type' => $giftTwo->type,
                'name' => $giftTwo->type,
                'icon' => [
                    'icon_small' => 'smallIconxxx.png',
                    'icon_big'   => 'bigIconxxx.png',
                ],
                'coins' => $giftTwo->coins
            ],
            'sender_id' => $operatorOne->id,
            'receiver_id' => $userId,
            'work_id' => $workTwo->id,
            'amount'  => '3',
            'display_order' => 200,

        ]);


        $giftHistoryThree = factory(\SingPlus\Domains\Gifts\Models\GiftHistory::class)->create([
            'gift_info' => [
                'id' => $giftTwo->id,
                'type' => $giftTwo->type,
                'name' => $giftTwo->type,
                'icon' => [
                    'icon_small' => 'smallIconxxx.png',
                    'icon_big'   => 'bigIconxxx.png',
                ],
                'coins' => $giftTwo->coins
            ],
            'sender_id' => $operatorTwo->id,
            'receiver_id' => $userId,
            'work_id' => $workOne->id,
            'amount'  => '5',
            'display_order' => 400,

        ]);

        $giftHistoryFour = factory(\SingPlus\Domains\Gifts\Models\GiftHistory::class)->create([
            'gift_info' => [
                'id' => $giftOne->id,
                'type' => $giftOne->type,
                'name' => $giftOne->type,
                'icon' => [
                    'icon_small' => 'smallIconxxx.png',
                    'icon_big'   => 'bigIconxxx.png',
                ],
                'coins' => $giftOne->coins
            ],
            'sender_id' => $operatorTwo->id,
            'receiver_id' => $userId,
            'work_id' => $workTwo->id,
            'amount'  => '6',
            'display_order' => 500,

        ]);

        $feedOne = factory(\SingPlus\Domains\Feeds\Models\Feed::class)->create([
            'user_id' => $userId,
            'operator_user_id' => $operatorOne->id,
            'type' => Feed::TYPE_GIFT_SEND_FOR_WORK,
            'detail' => [
                'work_id' => $workOne->id,
                'giftHistory_id' => $giftHistoryOne->id
            ],
            'display_order' => 100,
        ]);

        $feedTwo = factory(\SingPlus\Domains\Feeds\Models\Feed::class)->create([
            'user_id' => $userId,
            'operator_user_id' => $operatorOne->id,
            'type' => Feed::TYPE_GIFT_SEND_FOR_WORK,
            'detail' => [
                'work_id' => $workTwo->id,
                'giftHistory_id' => $giftHistoryTwo->id
            ],
            'display_order' => 100,
        ]);

        $feedThree = factory(\SingPlus\Domains\Feeds\Models\Feed::class)->create([
            'user_id' => $userId,
            'operator_user_id' => $operatorTwo->id,
            'type' => Feed::TYPE_GIFT_SEND_FOR_WORK,
            'detail' => [
                'work_id' => $workTwo->id,
                'giftHistory_id' => $giftHistoryThree->id
            ],
            'display_order' => 100,
        ]);

        $feedFour = factory(\SingPlus\Domains\Feeds\Models\Feed::class)->create([
            'user_id' => $userId,
            'operator_user_id' => $operatorOne->id,
            'type' => Feed::TYPE_GIFT_SEND_FOR_WORK,
            'detail' => [
                'work_id' => $workOne->id,
                'giftHistory_id' => $giftHistoryFour->id
            ],
            'display_order' => 100,
        ]);

        return (object)[
            'operator' => (object)[
                'one' => $operatorOne,
                'two' => $operatorTwo
            ],
            'work' => (object)[
                'one' => $workOne,
                'two' => $workTwo
            ],

            'giftHistory' => (object)[
                'one' => $giftHistoryOne,
                'two' => $giftHistoryTwo,
                'three' => $giftHistoryThree,
                'four'  => $giftHistoryFour
            ],

            'feed' => (object)[
                'one' => $feedOne,
                'two' => $feedTwo,
                'three' => $feedThree,
                'four'  => $feedFour
            ],
            'gift' => (object)[
                'one' => $giftOne,
                'two' => $giftTwo
            ]
        ];




    }

  private function prepareFeeds(string $userId)
  {
    $operator = factory(\SingPlus\Domains\Users\Models\User::class)->create();
    $operatorProfile = factory(\SingPlus\Domains\Users\Models\UserProfile::class)->create([
      'user_id'   => $operator->id,
      'avatar'    => 'operator-avatar',
      'nickname'  => 'zhangsan',
    ]);
    $musicOne = factory(\SingPlus\Domains\Musics\Models\Music::class)->create([
      'name'    => 'music-one',
      'status'  => 1,
    ]);
    $musicTwo = factory(\SingPlus\Domains\Musics\Models\Music::class)->create([
      'name'    => 'music-two',
      'status'  => 1,
    ]);
    $workOne = factory(\SingPlus\Domains\Works\Models\Work::class)->create([
      'music_id'  => $musicOne->id,
      'name'      => 'no accompaniment',
      'status'    => 1,
    ]);
    $workTwo = factory(\SingPlus\Domains\Works\Models\Work::class)->create([
      'music_id'  => $musicTwo->id,
      'status'    => 0,
    ]);
    $workThree = factory(\SingPlus\Domains\Works\Models\Work::class)->create([
      'name'      => 'God bless me',
      'music_id'  => $musicTwo->id,
      'status'    => 0,
    ]);
    $commentOne = factory(\SingPlus\Domains\Works\Models\Comment::class)->create([
      'work_id'         => $workOne->id,
      'content'         => 'comment one',
      'author_id'       => $operator->id,
      'replied_user_id' => $userId,
      'status'          => 1,
      'type'    => 0
    ]);
    $commentTwo = factory(\SingPlus\Domains\Works\Models\Comment::class)->create([
      'work_id'         => $workOne->id,
      'content'         => 'comment two',
      'author_id'       => $operator->id,
      'replied_user_id' => $userId,
      'status'          => 0,
      'type'    => 0
    ]);
    $commentThree = factory(\SingPlus\Domains\Works\Models\Comment::class)->create([
      'work_id'         => $workOne->id,
      'comment_id'      => $commentOne->id,
      'content'         => 'comment three',
      'author_id'       => $operator->id,
      'replied_user_id' => $userId,
      'status'          => 1,
    ]);
    $commentFour = factory(\SingPlus\Domains\Works\Models\Comment::class)->create([
      'work_id'         => $workThree->id,
      'comment_id'      => $commentOne->id,
      'content'         => 'comment four',
      'author_id'       => $operator->id,
      'replied_user_id' => $userId,
      'status'          => 0,
    ]);

    $commentFive = factory(\SingPlus\Domains\Works\Models\Comment::class)->create([
        'work_id'         => $workThree->id,
        'comment_id'      => $commentOne->id,
        'content'         => 'comment join collab',
        'author_id'       => $operator->id,
        'replied_user_id' => $userId,
        'status'          => 0,
        'type'            => 1,
    ]);

    $commentSix = factory(\SingPlus\Domains\Works\Models\Comment::class)->create([
        'work_id'         => $workThree->id,
        'comment_id'      => $commentOne->id,
        'content'         => 'comment transimit ',
        'author_id'       => $operator->id,
        'replied_user_id' => $userId,
        'status'          => 0,
        'type'            => 2,
    ]);

    $commentSeven = factory(\SingPlus\Domains\Works\Models\Comment::class)->create([
        'work_id'         => $workThree->id,
        'comment_id'      => $commentOne->id,
        'content'         => 'comment to self',
        'author_id'       => $userId,
        'replied_user_id' => $userId,
        'status'          => 0,
    ]);

    $feedOne = factory(\SingPlus\Domains\Feeds\Models\Feed::class)->create([
      'user_id'           => $userId,
      'operator_user_id'  => $operator->id,
      'work_id'           => $workOne->id,
      'type'              => 'work_transmit',
      'detail'            => [
                                'work_id'   => $workOne->id,
                                'channel'   => 'facebook',
                                'music_id'  => $musicOne->id,
                              ],
      'status'            => 1,
      'display_order'     => 100,
      'created_at'        => '2016-07-01',
    ]);
    $feedTwo = factory(\SingPlus\Domains\Feeds\Models\Feed::class)->create([
      'user_id'           => $userId,
      'operator_user_id'  => $operator->id,
      'work_id'           => $workOne->id,
      'type'              => 'work_transmit',
      'detail'            => [
                                'work_id'   => $workOne->id,
                                'channel'   => 'whatsapp',
                                'music_id'  => $musicOne->id,
                              ],
      'status'            => 1,
      'is_read'           => true,
      'display_order'     => 200,
      'created_at'        => '2016-08-01',
    ]);

    $feedTwo1 = factory(\SingPlus\Domains\Feeds\Models\Feed::class)->create([
        'user_id'           => $userId,
        'operator_user_id'  => $userId,
        'work_id'           => $workOne->id,
        'type'              => 'work_transmit',
        'detail'            => [
            'work_id'   => $workOne->id,
            'channel'   => 'whatsapp',
            'music_id'  => $musicOne->id,
        ],
        'status'            => 1,
        'is_read'           => true,
        'display_order'     => 200,
        'created_at'        => '2016-08-01',
    ]);


    $feedThree = factory(\SingPlus\Domains\Feeds\Models\Feed::class)->create([
      'user_id'           => $userId,
      'operator_user_id'  => $operator->id,
      'work_id'           => $workTwo->id,
      'type'              => 'work_favourite',
      'detail'            => [
                                'work_id'   => $workTwo->id,
                                'channel'   => 'facebook',
                              ],
      'status'            => 1,
      'is_read'           => true,
      'display_order'     => 300,
      'created_at'        => '2016-09-01',
    ]);
    $feedFour = factory(\SingPlus\Domains\Feeds\Models\Feed::class)->create([
      'user_id'           => $operator->id,
      'operator_user_id'  => $operator->id,
      'work_id'           => $workTwo->id,
      'type'              => 'work_favourite',
      'detail'            => [
                                'work_id'   => $workTwo->id,
                                'channel'   => 'facebook',
                              ],
      'status'            => 1,
      'is_read'           => true,
      'display_order'     => 400,
      'created_at'        => '2016-10-01',
    ]);

    $feedFour1 = factory(\SingPlus\Domains\Feeds\Models\Feed::class)->create([
        'user_id'           => $userId,
        'operator_user_id'  => $userId,
        'work_id'           => $workTwo->id,
        'type'              => 'work_favourite',
        'detail'            => [
            'work_id'   => $workTwo->id,
            'channel'   => 'facebook',
        ],
        'status'            => 1,
        'is_read'           => true,
        'display_order'     => 400,
        'created_at'        => '2016-10-01',
    ]);

    $feedFour2 = factory(\SingPlus\Domains\Feeds\Models\Feed::class)->create([
        'user_id'           => $userId,
        'operator_user_id'  => $operator->id,
        'work_id'           => $workTwo->id,
        'type'              => 'work_favourite_cancel',
        'detail'            => [
            'work_id'   => $workTwo->id,
            'channel'   => 'facebook',
        ],
        'status'            => 1,
        'is_read'           => true,
        'display_order'     => 400,
        'created_at'        => '2016-10-01',
    ]);


    $feedFive = factory(\SingPlus\Domains\Feeds\Models\Feed::class)->create([
      'user_id'           => $userId,
      'operator_user_id'  => $operator->id,
      'work_id'           => $workTwo->id,
      'type'              => 'work_comment',
      'detail'            => [
                                'work_id'     => $workTwo->id,
                                'comment_id'  => $commentOne->id,
                              ],
      'status'            => 1,
      'is_read'           => true,
      'display_order'     => 500,
      'created_at'        => '2016-01-02',
    ]);
    $feedSix = factory(\SingPlus\Domains\Feeds\Models\Feed::class)->create([
      'user_id'           => $userId,
      'operator_user_id'  => $operator->id,
      'work_id'           => $workTwo->id,
      'type'              => 'work_comment',
      'detail'            => [
                                'work_id'     => $workTwo->id,
                                'comment_id'  => $commentTwo->id,
                              ],
      'status'            => 1,
      'is_read'           => true,
      'display_order'     => 600,
      'created_at'        => '2016-02-02',
    ]);
    $feedSeven = factory(\SingPlus\Domains\Feeds\Models\Feed::class)->create([
      'user_id'           => $userId,
      'operator_user_id'  => $operator->id,
      'work_id'           => $workTwo->id,
      'type'              => 'work_comment',
      'detail'            => [
                                'work_id'     => $workTwo->id,
                                'comment_id'  => $commentThree->id,
                              ],
      'status'            => 1,
      'is_read'           => false,
      'display_order'     => 700,
      'created_at'        => '2016-03-02',
    ]);
    $feedEight = factory(\SingPlus\Domains\Feeds\Models\Feed::class)->create([
      'user_id'           => $userId,
      'operator_user_id'  => $operator->id,
      'work_id'           => $workTwo->id,
      'type'              => 'work_comment_delete',
      'detail'            => [
                                'work_id'     => $workTwo->id,
                                'comment_id'  => $commentFour->id,
                              ],
      'status'            => 1,
      'is_read'           => false,
      'display_order'     => 800,
      'created_at'        => '2016-04-02',
    ]);

    $feedEight1 = factory(\SingPlus\Domains\Feeds\Models\Feed::class)->create([
        'user_id'           => $userId,
        'operator_user_id'  => $operator->id,
        'work_id'           => $workThree->id,
        'type'              => 'work_comment',
        'detail'            => [
            'work_id'     => $workThree->id,
            'comment_id'  => $commentFive->id,
        ],
        'status'            => 1,
        'is_read'           => false,
        'display_order'     => 800,
        'created_at'        => '2016-04-02',
    ]);

    $feedEight2 = factory(\SingPlus\Domains\Feeds\Models\Feed::class)->create([
        'user_id'           => $userId,
        'operator_user_id'  => $operator->id,
        'work_id'           => $workThree->id,
        'type'              => 'work_comment',
        'detail'            => [
            'work_id'     => $workThree->id,
            'comment_id'  => $commentSix->id,
        ],
        'status'            => 1,
        'is_read'           => false,
        'display_order'     => 800,
        'created_at'        => '2016-04-02',
    ]);

    $feedEight3 = factory(\SingPlus\Domains\Feeds\Models\Feed::class)->create([
        'user_id'           => $userId,
        'operator_user_id'  => $userId,
        'work_id'           => $workThree->id,
        'type'              => 'work_comment',
        'detail'            => [
            'work_id'     => $workThree->id,
            'comment_id'  => $commentSeven->id,
        ],
        'status'            => 1,
        'is_read'           => false,
        'display_order'     => 800,
        'created_at'        => '2016-04-02',
    ]);

    $feedNine = factory(\SingPlus\Domains\Feeds\Models\Feed::class)->create([
      'user_id'           => $userId,
      'operator_user_id'  => $operator->id,
      'work_id'           => $workTwo->id,
      'type'              => 'work_chorus_join',
      'detail'            => [
                                'work_id'     => $workTwo->id,
                                'work_name'   => 'chorus-start',
                                'work_chorus_join_id' => $workThree->id,
                                'work_chorus_join_name' => 'chorus-join',
                                'work_chorus_join_description'  => 'chorus-join, good',
                              ],
      'status'            => 1,
      'is_read'           => false,
      'display_order'     => 900,
      'created_at'        => '2016-05-02',
    ]);

    return (object) [
      'feed'  => (object) [
        'one'   => $feedOne,
        'two'   => $feedTwo,
        'three' => $feedThree,
        'four'  => $feedFour,
        'five'  => $feedFive,
        'six'   => $feedSix,
        'seven' => $feedSeven,
        'eight' => $feedEight,
        'nine'  => $feedNine,
        'ten' => $feedEight1,
        'eleven' => $feedEight2,
        'twelve' => $feedEight3,
        'thirteen' => $feedTwo1,
        'fourteen' => $feedFour1,
        'fifteen' => $feedFour2,
      ],
      'work'    => (object) [
        'one'   => $workOne,
        'two'   => $workTwo,
        'three' => $workThree,
      ],
    ];
  }

  private function mockDailyTaskService(){
      $dailyTaskService = Mockery::mock(\SingPlus\Contracts\DailyTask\Services\DailyTaskService::class);
      $this->app[\SingPlus\Contracts\DailyTask\Services\DailyTaskService::class ] = $dailyTaskService;
      return $dailyTaskService;
  }

  /** 
   * mock http client and response
   * 模拟http响应
   *
   * Usage: $this->mockHttpClient([
   *            [
   *                'body'  => json_encode([
   *                        'code' => 0,
   *                        'data' => [],
   *                        'message'   => 'ok',
   *                    ]),
   *            ]])
   *
   * @param string $respBody 模拟响应body
   * @param int $respCode 模拟响应http status
   * @param array $respHeader 模拟响应http header
   */
  protected function mockHttpClient(array $respArr = [])
  {
    $mock = new MockHandler();
    foreach ($respArr as $resp) {
        $mock->append(new Response(
            array_get($resp, 'code', 200),
            array_get($resp, 'header', []),
            array_get($resp, 'body')
        ));
    }

    $handler = HandlerStack::create($mock);

    $this->app[\GuzzleHttp\ClientInterface::class] = new Client(['handler' => $handler]);
  }
}
