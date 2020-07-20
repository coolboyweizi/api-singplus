<?php

namespace FeatureTest\SingPlus\Controllers;

use GuzzleHttp\Client;
use GuzzleHttp\Handler\MockHandler;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Psr7\Response;
use Cache;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use FeatureTest\SingPlus\TestCase;
use FeatureTest\SingPlus\MongodbClearTrait;
use Mockery;

class FriendControllerTest extends TestCase
{
  use MongodbClearTrait; 

  //=================================
  //        follow 
  //=================================
  public function testFollowSuccess()
  {
    $this->expectsEvents(\SingPlus\Events\Friends\UserFollowed::class);
    $this->doesntExpectEvents(\SingPlus\Events\Friends\UserTriggerFollowed::class);
    $user = factory(\SingPlus\Domains\Users\Models\User::class)->create();
    factory(\SingPlus\Domains\Users\Models\UserProfile::class)->create([
      'user_id' => $user->id, 
    ]);
    $targetUser = factory(\SingPlus\Domains\Users\Models\User::class)->create();
    $counterMock = Mockery::mock(\Illuminate\Contracts\Cache\Store::class);
    $counterMock->shouldReceive('increment')
                ->once()
                ->with('user_followings', 100)
                ->andReturn(100);
    Cache::shouldReceive('driver')
         ->once()
         ->with('counter')
         ->andReturn($counterMock);

    $response = $this->actingAs($user)
         ->postJson('v3/friends/follow', [
            'user_id' => $targetUser->id,
         ]);
    $response->assertJson(['code' => 0]);

    self::assertDatabaseHas('user_followings', [
      'user_id'       => $user->id,
      'followings'    => [$targetUser->id],
      'display_order' => 100,
    ]);

    $followInfo = \SingPlus\Domains\Friends\Models\UserFollowing::where('user_id', $user->id)->first();
    self::assertNotNull($followInfo);
    self::assertEquals($targetUser->id, $followInfo->following_details[0]['user_id']);
    self::assertTrue(is_int($followInfo->following_details[0]['follow_at']));
  }

  public function testFollowSuccess_UserFollowExists()
  {
    $this->expectsEvents(\SingPlus\Events\Friends\UserFollowed::class);
    $this->doesntExpectEvents(\SingPlus\Events\Friends\UserTriggerFollowed::class);
    $user = factory(\SingPlus\Domains\Users\Models\User::class)->create();
    factory(\SingPlus\Domains\Users\Models\UserProfile::class)->create([
      'user_id' => $user->id, 
    ]);
    $userOne = factory(\SingPlus\Domains\Users\Models\User::class)->create();
    $targetUser = factory(\SingPlus\Domains\Users\Models\User::class)->create();

    $followInfo = factory(\SingPlus\Domains\Friends\Models\UserFollowing::class)->create([
      'user_id' => $user->id,
      'followings'  => [$userOne->id],
      'following_details' => [
        [
          'user_id'   => $userOne->id,
          'follow_at' => time(),
        ]
      ]
    ]);

    $response = $this->actingAs($user)
         ->postJson('v3/friends/follow', [
            'user_id' => $targetUser->id,
         ]);
    $response->assertJson(['code' => 0]);

    self::assertDatabaseHas('user_followings', [
      'user_id' => $user->id,
      'followings'  => [$userOne->id, $targetUser->id],
    ]);
  }

  public function testFollowSuccess_AreadyFollowed()
  {
    $user = factory(\SingPlus\Domains\Users\Models\User::class)->create();
    factory(\SingPlus\Domains\Users\Models\UserProfile::class)->create([
      'user_id' => $user->id, 
    ]);
    $targetUser = factory(\SingPlus\Domains\Users\Models\User::class)->create();

    $followInfo = factory(\SingPlus\Domains\Friends\Models\UserFollowing::class)->create([
      'user_id' => $user->id,
      'followings'  => [$targetUser->id],
      'following_details' => [
        [
          'user_id'   => $targetUser->id,
          'follow_at' => 1499235560,
        ]
      ]
    ]);

    $response = $this->actingAs($user)
         ->postJson('v3/friends/follow', [
            'user_id' => $targetUser->id,
         ]);
    $response->assertJson(['code' => 0]);

    self::assertDatabaseHas('user_followings', [
      'user_id' => $user->id,
      'followings'  => [$targetUser->id],
      'following_details' => [
                        [
                          'user_id'   => $targetUser->id,
                          'follow_at' => 1499235560,
                        ]
      ]
    ]);
  }

  public function testFollowFailed_FollowedUserNotExists()
  {
    $user = factory(\SingPlus\Domains\Users\Models\User::class)->create();
    factory(\SingPlus\Domains\Users\Models\UserProfile::class)->create([
      'user_id' => $user->id, 
    ]);

    $response = $this->actingAs($user)
         ->postJson('v3/friends/follow', [
            'user_id' => '78547d02f5494f94af4c9e5097c92925',
         ]);
    $response->assertJson(['code' => 10103]);
  }

  public function testFollowFailed_FollowSelf()
  {
    $user = factory(\SingPlus\Domains\Users\Models\User::class)->create();
    factory(\SingPlus\Domains\Users\Models\UserProfile::class)->create([
      'user_id' => $user->id, 
    ]);

    $response = $this->actingAs($user)
         ->postJson('v3/friends/follow', [
            'user_id' => $user->id,
         ]);
    $response->assertJson(['code' => 0]);
  }

  //=================================
  //        unfollow 
  //=================================
  public function testUnfollowSuccess()
  {
    $this->expectsEvents(\SingPlus\Events\Friends\UserUnfollowed::class);
    $user = factory(\SingPlus\Domains\Users\Models\User::class)->create();
    factory(\SingPlus\Domains\Users\Models\UserProfile::class)->create([
      'user_id' => $user->id, 
    ]);
    $targetUser = factory(\SingPlus\Domains\Users\Models\User::class)->create();

    $followInfo = factory(\SingPlus\Domains\Friends\Models\UserFollowing::class)->create([
      'user_id' => $user->id,
      'followings'  => [$targetUser->id],
      'following_details' => [
        [
          'user_id'   => $targetUser->id,
          'follow_at' => 1499235560,
        ]
      ]
    ]);
    $counterMock = Mockery::mock(\Illuminate\Contracts\Cache\Store::class);
    $counterMock->shouldReceive('increment')
          ->once()
          ->with('user_unfollowed', 100)
          ->andReturn(100);
    Cache::shouldReceive('driver')
          ->once()
          ->with('counter')
          ->andReturn($counterMock);

    $response = $this->actingAs($user)
         ->postJson('v3/friends/unfollow', [
            'user_id' => $targetUser->id,
         ]);
    $response->assertJson(['code' => 0]);

    self::assertDatabaseHas('user_followings', [
      'user_id'     => $user->id,
      'followings'  => [],
      'following_details' => [],
    ]);
  }

  public function testUnfollowSuccess_ThenFollow()
  {
        $this->expectsEvents(\SingPlus\Events\Friends\UserTriggerFollowed::class);
        $this->doesntExpectEvents(\SingPlus\Events\Friends\UserFollowed::class);
        $user = factory(\SingPlus\Domains\Users\Models\User::class)->create();
        factory(\SingPlus\Domains\Users\Models\UserProfile::class)->create([
            'user_id' => $user->id,
        ]);
        $targetUser = factory(\SingPlus\Domains\Users\Models\User::class)->create();

        $unfollowInfo = factory(\SingPlus\Domains\Friends\Models\UserUnfollowed::class)->create([
            'user_id' => $user->id,
            'unfollowed'  => [$targetUser->id],
            'unfollowed_details' => [
                [
                    'user_id'   => $targetUser->id,
                    'unfollow_at' => 1499235560,
                ]
            ]
        ]);

      $counterMock = Mockery::mock(\Illuminate\Contracts\Cache\Store::class);
      $counterMock->shouldReceive('increment')
                  ->once()
                  ->with('user_followings', 100)
                  ->andReturn(100);
      Cache::shouldReceive('driver')
          ->once()
          ->with('counter')
          ->andReturn($counterMock);

        $response = $this->actingAs($user)
            ->postJson('v3/friends/follow', [
                'user_id' => $targetUser->id,
            ]);
        $response->assertJson(['code' => 0]);

        self::assertDatabaseHas('user_followings', [
            'user_id' => $user->id,
            'followings'  => [$targetUser->id],
        ]);

      self::assertDatabaseHas('user_unfollowed',[
          'user_id'      => $user->id,
          'unfollowed'   => [$targetUser->id],
      ]);

  }

  public function testUnfollowSuccess_FollowingNotExist()
  {
    $user = factory(\SingPlus\Domains\Users\Models\User::class)->create();
    factory(\SingPlus\Domains\Users\Models\UserProfile::class)->create([
      'user_id' => $user->id, 
    ]);
    $targetUser = factory(\SingPlus\Domains\Users\Models\User::class)->create();

    $followInfo = factory(\SingPlus\Domains\Friends\Models\UserFollowing::class)->create([
      'user_id' => $user->id,
      'followings'  => [],
      'following_details' => [],
    ]);

    $response = $this->actingAs($user)
         ->postJson('v3/friends/unfollow', [
            'user_id' => $targetUser->id,
         ]);
    $response->assertJson(['code' => 0]);

    self::assertDatabaseHas('user_followings', [
      'user_id'     => $user->id,
      'followings'  => [],
      'following_details' => [],
    ]);
  }

  public function testUnfollowFailed_UnfollowSelf()
  {
    $user = factory(\SingPlus\Domains\Users\Models\User::class)->create();
    factory(\SingPlus\Domains\Users\Models\UserProfile::class)->create([
      'user_id' => $user->id, 
    ]);

    $response = $this->actingAs($user)
         ->postJson('v3/friends/unfollow', [
            'user_id' => $user->id,
         ]);
    $response->assertJson(['code' => 0]);
  }

  //=================================
  //        getFollowers
  //=================================
  public function testGetFollowersSuccess()
  {
    config([
      'filesystems.disks.s3.region'  => 'eu-central-1',
      'filesystems.disks.s3.bucket'  => 'sing-plus',
    ]);
    $user = factory(\SingPlus\Domains\Users\Models\User::class)->create();
    factory(\SingPlus\Domains\Users\Models\UserProfile::class)->create([
      'user_id' => $user->id, 
    ]);
    $data = $this->prepareFollowData($user);

    $response = $this->actingAs($user)
                     ->getJson('v3/friends/followers');
    $response->assertJson(['code' => 0]);
    $followers = (json_decode($response->getContent()))->data->users;
    self::assertCount(2, $followers);
    self::assertEquals($data->user->two->id, $followers[0]->userId);  // order by display_order
    self::assertEquals('user-two', $followers[0]->nickname);
    self::assertTrue(ends_with($followers[0]->avatar, 'avatar-two'));
    self::assertTrue($followers[0]->isFollower);
    self::assertTrue($followers[0]->isFollowing);
    self::assertEquals($data->user->one->id, $followers[1]->userId);
    self::assertTrue($followers[1]->isFollower);
    self::assertFalse($followers[1]->isFollowing);
  }

  public function testGetFollowersSuccess_WithPagination()
  {
    config([
      'filesystems.disks.s3.region'  => 'eu-central-1',
      'filesystems.disks.s3.bucket'  => 'sing-plus',
    ]);
    $user = factory(\SingPlus\Domains\Users\Models\User::class)->create();
    factory(\SingPlus\Domains\Users\Models\UserProfile::class)->create([
      'user_id' => $user->id, 
    ]);
    $data = $this->prepareFollowData($user);

    $response = $this->actingAs($user)
                     ->getJson('v3/friends/followers?' . http_build_query([
                        'id'  => $data->following->two->id, 
                     ]));
    $response->assertJson(['code' => 0]);
    $followers = (json_decode($response->getContent()))->data->users;
    self::assertCount(1, $followers);
    self::assertEquals($data->following->one->id, $followers[0]->id);
    self::assertEquals($data->user->one->id, $followers[0]->userId);
    self::assertFalse($followers[0]->isFollowing);
  }

  public function testGetFollowersSuccess_OthersFollower()
  {
    config([
      'filesystems.disks.s3.region'  => 'eu-central-1',
      'filesystems.disks.s3.bucket'  => 'sing-plus',
    ]);
    $user = factory(\SingPlus\Domains\Users\Models\User::class)->create();
    factory(\SingPlus\Domains\Users\Models\UserProfile::class)->create([
      'user_id' => $user->id, 
    ]);
    $data = $this->prepareFollowData($user);
    $this->mockHttpClient(json_encode([
        'code'  => 0,
        'data'  => [
            'relation'  => [
                $data->user->three->id    => [
                    'is_following'=> true,
                    'follow_at'=> '2015-01-01 00:00:00',
                    'is_follower'=> false,
                    'followed_at'=> null,
                ],
            ],
        ]
    ]));

    $response = $this->actingAs($user)
                     ->getJson('v3/friends/followers?' . http_build_query([
                        'userId'  => $data->user->one->id,
                     ]));
    $response->assertJson(['code' => 0]);
    $followers = (json_decode($response->getContent()))->data->users;
    self::assertCount(1, $followers);
    self::assertEquals($data->following->three->id, $followers[0]->id);
    self::assertEquals($data->user->three->id, $followers[0]->userId);
    self::assertTrue($followers[0]->isFollowing);
    self::assertFalse($followers[0]->isFollower);
  }

  public function testGetFollowersSuccess_OthersFollowerWithoutLogin()
  {
      config([
          'filesystems.disks.s3.region'  => 'eu-central-1',
          'filesystems.disks.s3.bucket'  => 'sing-plus',
      ]);
      $user = factory(\SingPlus\Domains\Users\Models\User::class)->create();
      factory(\SingPlus\Domains\Users\Models\UserProfile::class)->create([
          'user_id' => $user->id,
      ]);
      $data = $this->prepareFollowData($user);

      $response = $this
          ->getJson('v3/friends/followers?' . http_build_query([
                  'userId'  => $data->user->one->id,
              ]));
      $response->assertJson(['code' => 0]);
      $followers = (json_decode($response->getContent()))->data->users;
      self::assertCount(1, $followers);
      self::assertEquals($data->following->three->id, $followers[0]->id);
      self::assertEquals($data->user->three->id, $followers[0]->userId);
      self::assertFalse($followers[0]->isFollowing);
      self::assertFalse($followers[0]->isFollower);
  }

  //=================================
  //        getFollowers_v4
  //=================================
  public function testGetFollowersSuccess_v4()
  {
    config([
      'filesystems.disks.s3.region'  => 'eu-central-1',
      'filesystems.disks.s3.bucket'  => 'sing-plus',
    ]);
    $user = factory(\SingPlus\Domains\Users\Models\User::class)->create();
    factory(\SingPlus\Domains\Users\Models\UserProfile::class)->create([
      'user_id' => $user->id, 
    ]);
    $data = $this->prepareFollowData($user);
    $this->mockHttpClient(json_encode([
        'code'  => 0,
        'data'  => [
            'follower_count'    => 2,
            'followers' => [
                [
                    'user_id'           => $data->user->two->id,
                    'follow_at'         => '2017-09-01 00:00:12',
                    'is_following'      => true,
                    'reverse_follow_at' => '2017-05-01 00:00:12',
                ],
                [
                    'user_id'           => $data->user->one->id,
                    'follow_at'         => '2017-07-01 00:00:12',
                    'is_following'      => false,
                    'reverse_follow_at' => null,
                ],
            ]
        ],
    ]));

    $response = $this->actingAs($user)
                     ->getJson('v4/friends/followers');
    $response->assertJson(['code' => 0]);
    $followers = (json_decode($response->getContent()))->data->users;
    self::assertCount(2, $followers);
    self::assertEquals($data->user->two->id, $followers[0]->userId);  // order by display_order
    self::assertEquals('user-two', $followers[0]->nickname);
    self::assertTrue(ends_with($followers[0]->avatar, 'avatar-two'));
    self::assertTrue($followers[0]->isFollower);
    self::assertTrue($followers[0]->isFollowing);
    self::assertEquals($followers[0]->followedAt, '01 Sep 2017 at 00:00:12');
    self::assertEquals($followers[0]->followAt, '01 May 2017 at 00:00:12');
    self::assertEquals($data->user->one->id, $followers[1]->userId);
    self::assertTrue($followers[1]->isFollower);
    self::assertFalse($followers[1]->isFollowing);
    self::assertEquals($followers[1]->followedAt, '01 Jul 2017 at 00:00:12');
  }

  //=================================
  //        getFollowings
  //=================================
  public function testGetFollowingsSuccess()
  {
    config([
      'filesystems.disks.s3.region'  => 'eu-central-1',
      'filesystems.disks.s3.bucket'  => 'sing-plus',
    ]);
    $user = factory(\SingPlus\Domains\Users\Models\User::class)->create();
    factory(\SingPlus\Domains\Users\Models\UserProfile::class)->create([
      'user_id' => $user->id, 
    ]);
    $data = $this->prepareFollowData($user);

    $response = $this->actingAs($user)
                     ->getJson('v3/friends/followings');
    $response->assertJson(['code' => 0]);
    $followings = (json_decode($response->getContent()))->data->users;

    self::assertCount(2, $followings);
    $followUserThree = $followings[0];      // reverse list of user_followings.following_details
    $followUserTwo = $followings[1];
    self::assertEquals($data->user->two->id, $followUserTwo->userId);
    self::assertTrue($followUserTwo->isFollowing);
    self::assertTrue($followUserTwo->isFollower);
    self::assertTrue(ends_with($followUserTwo->avatar, 'avatar-two'));
    self::assertEquals($data->user->three->id, $followUserThree->userId);
    self::assertTrue($followUserThree->isFollowing);
    self::assertFalse($followUserThree->isFollower);
  }

  public function testGetFollowingsSuccess_OthersFollowing()
  {
    config([
      'filesystems.disks.s3.region'  => 'eu-central-1',
      'filesystems.disks.s3.bucket'  => 'sing-plus',
    ]);
    $user = factory(\SingPlus\Domains\Users\Models\User::class)->create();
    factory(\SingPlus\Domains\Users\Models\UserProfile::class)->create([
      'user_id' => $user->id, 
    ]);
    $data = $this->prepareFollowData($user);

    $this->mockHttpClient(json_encode([
        'code'  => 0,
        'data'  => [
            'relation'  => [
                $data->user->two->id    => [
                    'is_following'=> true,
                    'follow_at'=> '2015-01-01 00:00:00',
                    'is_follower'=> true,
                    'followed_at'=> '2015-02-02 00:00:11',
                ],
                $user->id    => [
                    'is_following'=> false,
                    'follow_at'=> null,
                    'is_follower'=> false,
                    'followed_at'=> null,
                ],
            ],
        ]
    ]));

    $response = $this->actingAs($user)
                     ->getJson('v3/friends/followings?' . http_build_query([
                        'userId'  => $data->user->one->id, 
                     ]));
    $response->assertJson(['code' => 0]);
    $followings = (json_decode($response->getContent()))->data->users;
    self::assertCount(2, $followings);
    self::assertEquals($data->user->two->id, $followings[0]->userId);
    self::assertTrue($followings[0]->isFollower);
    self::assertTrue($followings[0]->isFollowing);
    self::assertEquals($user->id, $followings[1]->userId);
    self::assertFalse($followings[1]->isFollower);
    self::assertFalse($followings[1]->isFollowing);
  }

  public function testGetFollowingSuccess_OtherFollowingWithoutLogin()
  {
      config([
          'filesystems.disks.s3.region'  => 'eu-central-1',
          'filesystems.disks.s3.bucket'  => 'sing-plus',
      ]);
      $user = factory(\SingPlus\Domains\Users\Models\User::class)->create();
      factory(\SingPlus\Domains\Users\Models\UserProfile::class)->create([
          'user_id' => $user->id,
      ]);
      $data = $this->prepareFollowData($user);

      $response = $this
          ->getJson('v3/friends/followings?' . http_build_query([
                  'userId'  => $data->user->one->id,
              ]));
      $response->assertJson(['code' => 0]);
      $followings = (json_decode($response->getContent()))->data->users;
      self::assertCount(2, $followings);
      self::assertEquals($data->user->two->id, $followings[0]->userId);
      self::assertFalse($followings[0]->isFollower);
      self::assertFalse($followings[0]->isFollowing);
      self::assertEquals($user->id, $followings[1]->userId);
      self::assertFalse($followings[1]->isFollower);
      self::assertFalse($followings[1]->isFollowing);
  }


  //=================================
  //        getFollowings_v4
  //=================================
  public function testGetFollowingsSuccess_v4()
  {
    config([
      'filesystems.disks.s3.region'  => 'eu-central-1',
      'filesystems.disks.s3.bucket'  => 'sing-plus',
    ]);
    $user = factory(\SingPlus\Domains\Users\Models\User::class)->create();
    factory(\SingPlus\Domains\Users\Models\UserProfile::class)->create([
      'user_id' => $user->id, 
    ]);
    $data = $this->prepareFollowData($user);

    $this->mockHttpClient(json_encode([
        'code'  => 0,
        'data'  => [
            'following_count'    => 2,
            'followings' => [
                [
                    'user_id'           => $data->user->three->id,
                    'follow_at'         => '2017-09-01 00:00:32',
                    'is_follower'       => false,
                    'reverse_follow_at' => null,
                ],
                [
                    'user_id'           => $data->user->two->id,
                    'follow_at'         => '2017-07-01 00:00:32',
                    'is_follower'       => true,
                    'reverse_follow_at' => '2017-03-01 00:00:32',
                ],
            ]
        ],
    ]));

    $response = $this->actingAs($user)
                     ->getJson('v4/friends/followings');
    $response->assertJson(['code' => 0]);
    $followings = (json_decode($response->getContent()))->data->users;

    self::assertCount(2, $followings);
    $followUserThree = $followings[0];      // reverse list of user_followings.following_details
    $followUserTwo = $followings[1];
    self::assertEquals($data->user->two->id, $followUserTwo->userId);
    self::assertTrue($followUserTwo->isFollowing);
    self::assertTrue($followUserTwo->isFollower);
    self::assertEquals($followUserTwo->followAt, '01 Jul 2017 at 00:00:32');
    self::assertEquals($followUserTwo->followedAt, '01 Mar 2017 at 00:00:32');
    self::assertTrue(ends_with($followUserTwo->avatar, 'avatar-two'));
    self::assertEquals($data->user->three->id, $followUserThree->userId);
    self::assertTrue($followUserThree->isFollowing);
    self::assertFalse($followUserThree->isFollower);
    self::assertEquals($followUserThree->followAt, '01 Sep 2017 at 00:00:32');
  }

  //=================================
  //        searchUsers
  //=================================
  public function testSearchUsersSuccess()
  {
    config([
      'filesystems.disks.s3.region'  => 'eu-central-1',
      'filesystems.disks.s3.bucket'  => 'sing-plus',
    ]);
    $user = factory(\SingPlus\Domains\Users\Models\User::class)->create();
    factory(\SingPlus\Domains\Users\Models\UserProfile::class)->create([
      'user_id'   => $user->id, 
      'nickname'  => 'user-self',
    ]);
    $userFour = factory(\SingPlus\Domains\Users\Models\User::class)->create();
    factory(\SingPlus\Domains\Users\Models\UserProfile::class)->create([
      'user_id'   => $userFour->id, 
      'nickname'  => 'user-four',
    ]);
    $data = $this->prepareFollowData($user);

    $this->mockHttpClient(json_encode([
        'code'  => 0,
        'data'  => [
            'relation'  => [
                $data->user->one->id    => [
                    'is_following'=> false,
                    'follow_at'=> null,
                    'is_follower'=> true,
                    'followed_at'=> '2015-01-01 00:00:00',
                ],
                $data->user->two->id    => [
                    'is_following'=> true,
                    'follow_at'=> '2016-01-01 11:11:11',
                    'is_follower'=> true,
                    'followed_at'=> '2015-02-01 00:00:00',
                ],
                $data->user->three->id    => [
                    'is_following'=> true,
                    'follow_at'=> '2016-02-01 11:11:11',
                    'is_follower'=> false,
                    'followed_at'=> null,
                ],
                $userFour->id    => [
                    'is_following'=> false,
                    'follow_at'=> null,
                    'is_follower'=> false,
                    'followed_at'=> null,
                ],
            ],
        ],
    ]));

    $response = $this->actingAs($user)
                     ->getJson('v3/friends/users?' . http_build_query([
                        'nickname'  => 'user',
                     ]));
    $response->assertJson(['code' => 0]);

    $users = (json_decode($response->getContent()))->data->users;
    $users = collect($users);
    self::assertEquals(4, $users->count());
    $userOne = $users->where('nickname', 'user-one')->first();
    $userTwo = $users->where('nickname', 'user-two')->first();
    $userThree = $users->where('nickname', 'user-three')->first();
    $userFour = $users->where('nickname', 'user-four')->first();
    self::assertTrue($userOne->isFollower);
    self::assertFalse($userOne->isFollowing);
    self::assertEquals('01 Jan 2015 at 00:00:00', $userOne->followedAt);
    self::assertNull($userOne->followAt);
    self::assertTrue($userTwo->isFollower);
    self::assertTrue($userTwo->isFollowing);
    self::assertFalse($userThree->isFollower);
    self::assertTrue($userThree->isFollowing);
    self::assertFalse($userFour->isFollower);
    self::assertFalse($userFour->isFollowing);
  }

  public function testSearchUsersSuccess_SearchUserOne()
  {
    config([
      'filesystems.disks.s3.region'  => 'eu-central-1',
      'filesystems.disks.s3.bucket'  => 'sing-plus',
    ]);
    $user = factory(\SingPlus\Domains\Users\Models\User::class)->create();
    factory(\SingPlus\Domains\Users\Models\UserProfile::class)->create([
      'user_id'   => $user->id, 
      'nickname'  => 'user-self',
    ]);
    $userFour = factory(\SingPlus\Domains\Users\Models\User::class)->create();
    factory(\SingPlus\Domains\Users\Models\UserProfile::class)->create([
      'user_id'   => $userFour->id, 
      'nickname'  => 'user-four',
    ]);
    $data = $this->prepareFollowData($user);

    $this->mockHttpClient(json_encode([
        'code'  => 0,
        'data'  => [
            'relation'  => [
                $data->user->one->id    => [
                    'is_following'=> false,
                    'follow_at'=> null,
                    'is_follower'=> true,
                    'followed_at'=> '2015-01-01 00:00:00',
                ],
            ],
        ],
    ]));

    $response = $this->actingAs($user)
                     ->getJson('v3/friends/users?' . http_build_query([
                        'nickname'  => 'one',
                     ]));
    $response->assertJson(['code' => 0]);

    $users = (json_decode($response->getContent()))->data->users;
    self::assertCount(1, $users);
    self::assertEquals('user-one', $users[0]->nickname);      // only user-one was fetched
    self::assertTrue($users[0]->isFollower);
    self::assertFalse($users[0]->isFollowing);
  }

  //=================================
  //        getFollowingLatestWorks 
  //=================================
  public function testGetFollowingLatestWorksSuccess()
  {
    config([
      'business-logic.fakemusic'  => [
        'id'    => '99a8ab1b34764481bd642534b615d7b0',
        'name'  => 'without accompaniment',
      ],
    ]);
    $user = factory(\SingPlus\Domains\Users\Models\User::class)->create();
    factory(\SingPlus\Domains\Users\Models\UserProfile::class)->create([
      'user_id' => $user->id, 
    ]);

    $followData = $this->prepareFollowData($user);
    
    $artistOne = factory(\SingPlus\Domains\Musics\Models\Artist::class)->create([
      'name'  => 'Simon',
    ]);
    $artistTwo = factory(\SingPlus\Domains\Musics\Models\Artist::class)->create([
      'name'  => 'Plum',
    ]);

    $musicOne = factory(\SingPlus\Domains\Musics\Models\Music::class)->create([
      'name'    => 'musicOne "hell"',
      'lyrics'  => 'music-lyric-one',
      'artists' => [$artistOne->id, $artistTwo->id],
      'covers'  => ['music-one-cover-one', 'music-one-cover-two'],
    ]);
    $musicTwo = factory(\SingPlus\Domains\Musics\Models\Music::class)->create([
      'name'    => 'musicTwo',
      'lyrics'  => 'music-lyric-two',
      'artists' => [$artistOne->id],
      'status'  => -1,
    ]);
    $workOne = factory(\SingPlus\Domains\Works\Models\Work::class)->create([
      'user_id'   => $followData->user->one->id,
      'music_id'  => $musicOne->id,
      'cover'     => 'work-cover-one',
      'slides'    => [
        'work-one-one', 'work-one-two',
      ],
      'display_order' => 100,
      'comment_count' => 0,
      'favourite_count' => 1,
      'resource'      => 'work-one',
      'duration'      => 128,
      'status'        => 1,
    ]);
    $workTwo = factory(\SingPlus\Domains\Works\Models\Work::class)->create([
      'user_id'   => $followData->user->two->id,
      'music_id'  => $musicTwo->id,
      'cover'     => 'work-cover-two',
      'name'      => 'work three',
      'slides'    => [
        'work-two-one', 'work-two-two',
      ],
      'display_order' => 200,
      'comment_count' => 0,
      'favourite_count' => 1,
      'resource'      => 'work-two',
      'status'        => 2,
      'chorus_type'   => 1,
      'chorus_start_info' => [
        'chorus_count'  => 100,
      ],
    ]);
    $workThree = factory(\SingPlus\Domains\Works\Models\Work::class)->create([
      'user_id'   => $followData->user->three->id,
      'music_id'  => $musicTwo->id,
      'cover'     => 'work-cover-three',
      'slides'    => [
        'work-three-one', 'work-three-two',
      ],
      'display_order' => 300,
      'comment_count' => 0,
      'favourite_count' => 1,
      'resource'      => 'work-three',
      'status'        => 1,
      'is_private'    => 0,
      'chorus_type'   => 10,
      'chorus_join_info'  => [
        'origin_work_id'  => $workTwo->id,
      ],
    ]);
    $workFour = factory(\SingPlus\Domains\Works\Models\Work::class)->create([
      'user_id'   => $followData->user->three->id,
      'music_id'  => $musicTwo->id,
      'cover'     => 'work-cover-four',
      'slides'    => [
        'work-four-one', 'work-four-two',
      ],
      'display_order' => 400,
      'comment_count' => 0,
      'favourite_count' => 1,
      'resource'      => 'work-four',
      'status'        => 1,
      'is_private'    => 1,
    ]);

    $response = $this->actingAs($user)
                     ->getJson('v3/friends/followings/works/latest?' . http_build_query([
                        'size'  => 2,
                     ]));
    $response->assertJson(['code' => 0]);
    $works = (json_decode($response->getContent()))->data->latests;
    self::assertCount(2, $works);     // work three is private work, not output
    self::assertEquals($workThree->id, $works[0]->id);
    self::assertEquals('musicTwo', $works[0]->musicName);     // use work name first
    self::assertEquals(10, $works[0]->chorusType);
    self::assertEquals(0, $works[0]->chorusCount);
    self::assertEquals($followData->user->two->id, $works[0]->originWorkUser->userId);
  }

  //====================================
  //        getFollowingLatestWorks_v4
  //====================================
  public function testGetFollowingLatestWorksSuccess_v4()
  {
    config([
      'business-logic.fakemusic'  => [
        'id'    => '99a8ab1b34764481bd642534b615d7b0',
        'name'  => 'without accompaniment',
      ],
    ]);
    $user = factory(\SingPlus\Domains\Users\Models\User::class)->create();
    factory(\SingPlus\Domains\Users\Models\UserProfile::class)->create([
      'user_id' => $user->id, 
    ]);

    $followData = $this->prepareFollowData($user);
    
    $artistOne = factory(\SingPlus\Domains\Musics\Models\Artist::class)->create([
      'name'  => 'Simon',
    ]);
    $artistTwo = factory(\SingPlus\Domains\Musics\Models\Artist::class)->create([
      'name'  => 'Plum',
    ]);

    $musicOne = factory(\SingPlus\Domains\Musics\Models\Music::class)->create([
      'name'    => 'musicOne "hell"',
      'lyrics'  => 'music-lyric-one',
      'artists' => [$artistOne->id, $artistTwo->id],
      'covers'  => ['music-one-cover-one', 'music-one-cover-two'],
    ]);
    $musicTwo = factory(\SingPlus\Domains\Musics\Models\Music::class)->create([
      'name'    => 'musicTwo',
      'lyrics'  => 'music-lyric-two',
      'artists' => [$artistOne->id],
      'status'  => -1,
    ]);
    $workOne = factory(\SingPlus\Domains\Works\Models\Work::class)->create([
      'user_id'   => $followData->user->one->id,
      'music_id'  => $musicOne->id,
      'cover'     => 'work-cover-one',
      'slides'    => [
        'work-one-one', 'work-one-two',
      ],
      'display_order' => 100,
      'comment_count' => 0,
      'favourite_count' => 1,
      'resource'      => 'work-one',
      'duration'      => 128,
      'status'        => 1,
    ]);
    $workTwo = factory(\SingPlus\Domains\Works\Models\Work::class)->create([
      'user_id'   => $followData->user->two->id,
      'music_id'  => $musicTwo->id,
      'cover'     => 'work-cover-two',
      'name'      => 'work three',
      'slides'    => [
        'work-two-one', 'work-two-two',
      ],
      'display_order' => 200,
      'comment_count' => 0,
      'favourite_count' => 1,
      'resource'      => 'work-two',
      'status'        => 2,
      'chorus_type'   => 1,
      'chorus_start_info' => [
        'chorus_count'  => 100,
      ],
    ]);
    $workThree = factory(\SingPlus\Domains\Works\Models\Work::class)->create([
      'user_id'   => $followData->user->three->id,
      'music_id'  => $musicTwo->id,
      'cover'     => 'work-cover-three',
      'slides'    => [
        'work-three-one', 'work-three-two',
      ],
      'display_order' => 300,
      'comment_count' => 0,
      'favourite_count' => 1,
      'resource'      => 'work-three',
      'status'        => 1,
      'is_private'    => 0,
      'chorus_type'   => 10,
      'chorus_join_info'  => [
        'origin_work_id'  => $workTwo->id,
      ],
    ]);
    $workFour = factory(\SingPlus\Domains\Works\Models\Work::class)->create([
      'user_id'   => $followData->user->three->id,
      'music_id'  => $musicTwo->id,
      'cover'     => 'work-cover-four',
      'slides'    => [
        'work-four-one', 'work-four-two',
      ],
      'display_order' => 400,
      'comment_count' => 0,
      'favourite_count' => 1,
      'resource'      => 'work-four',
      'status'        => 1,
      'is_private'    => 1,
    ]);

    $this->mockHttpClient(json_encode([
        'code'  => 0,
        'data'  => [
            'works' => [
                $workThree->id,
                $workTwo->id,
            ],
        ],
    ]));

    $response = $this->actingAs($user)
                     ->getJson('v4/friends/followings/works/latest?' . http_build_query([
                        'size'  => 2,
                     ]));
    $response->assertJson(['code' => 0]);
    $works = (json_decode($response->getContent()))->data->latests;
    self::assertCount(2, $works);     // work three is private work, not output
    self::assertEquals($workThree->id, $works[0]->id);
    self::assertEquals('musicTwo', $works[0]->musicName);     // use work name first
    self::assertEquals(10, $works[0]->chorusType);
    self::assertEquals(0, $works[0]->chorusCount);
    self::assertEquals($followData->user->two->id, $works[0]->originWorkUser->userId);
  }

  //=================================
  //      getSocialiteUsersFriends
  //=================================
  public function testGetSocialiteUsersFriendsSuccess()
  {
    config([
      'business-logic.fakemusic'  => [
        'id'    => '99a8ab1b34764481bd642534b615d7b0',
        'name'  => 'without accompaniment',
      ],
    ]);
    $user = factory(\SingPlus\Domains\Users\Models\User::class)->create();
    factory(\SingPlus\Domains\Users\Models\UserProfile::class)->create([
      'user_id' => $user->id, 
    ]);

    $data = $this->prepareFollowData($user);

    $socialiteOne = factory(\SingPlus\Domains\Users\Models\SocialiteUser::class)->create([
      'user_id'           => $data->user->one->id,
      'socialite_user_id' => 'aaaaaaaaaaaa',      // compatiable for old data structure
      'provider'          => 'facebook',
    ]);
    $socialiteTwo = factory(\SingPlus\Domains\Users\Models\SocialiteUser::class)->create([
      'user_id'           => $data->user->two->id,
      'provider'          => 'facebook',
      'channels'          => [
        'singplus'  => [
          'openid'  => 'bbbbbbbbbbbb',
          'token'   => '036fd79798f44c05b582bad1266be4ed',
        ],
      ],
    ]);
    $socialiteThree = factory(\SingPlus\Domains\Users\Models\SocialiteUser::class)->create([
      'user_id'           => $data->user->three->id,
      'socialite_user_id' => 'cccccccccccc',
      'provider'          => 'whatsapp',
    ]);

    $this->mockHttpClient(json_encode([
        'code'  => 0,
        'data'  => [
            'relation'  => [
                $data->user->one->id    => [
                    'is_following'=> false,
                    'follow_at'=> null,
                    'is_follower'=> true,
                    'followed_at'=> '2015-01-01 00:00:00',
                ],
                $data->user->two->id    => [
                    'is_following'=> true,
                    'follow_at'=> '2016-01-01 11:11:11',
                    'is_follower'=> true,
                    'followed_at'=> '2015-02-01 00:00:00',
                ],
            ],
        ]
    ]));

    $response = $this->actingAs($user)
                     ->getJson('v3/friends/socialite/facebook/users?' . http_build_query([
                        'socialiteUserIds'  => [
                          'aaaaaaaaaaaa',
                          'bbbbbbbbbbbb',
                          'cccccccccccc',
                        ],
                     ]));
    $response->assertJson(['code' => 0]);

    $users = (json_decode($response->getContent()))->data->users;
    self::assertCount(2, $users);
    if ($users[0]->socialiteUserId == 'aaaaaaaaaaaa') {
      $userOne = $users[0]; 
      $userTwo = $users[1]; 
    } else {
      $userOne = $users[1]; 
      $userTwo = $users[0]; 
    }

    self::assertTrue($userOne->isFollower);
    self::assertFalse($userOne->isFollowing);
    self::assertTrue($userTwo->isFollower);
    self::assertTrue($userTwo->isFollowing);
  }

  //====================================
  //      getRecommendUserFollowings
  //====================================
  public function testGetRecommendUserFollowingsSuccess()
  {
    $this->expectsEvents(\SingPlus\Events\Friends\GetRecommendUserFollowingAction::class);
    $user = factory(\SingPlus\Domains\Users\Models\User::class)->create();
    factory(\SingPlus\Domains\Users\Models\UserProfile::class)->create([
      'user_id' => $user->id, 
    ]);

    $data = $this->prepareFollowData($user);
    $recommendOne = factory(\SingPlus\Domains\Friends\Models\UserFollowingRecommend::class)->create([
      'user_id'           => $user->id,
      'following_user_id' => $data->following->one->user_id,
      'display_order'     => 100,
      'is_auto_recommend' => 0,
    ]);
    $recommendTwo = factory(\SingPlus\Domains\Friends\Models\UserFollowingRecommend::class)->create([
      'user_id'           => $user->id,
      'following_user_id' => $data->following->two->user_id,
      'display_order'     => 200,
      'is_auto_recommend' => 1,
    ]);
    $recommendThree = factory(\SingPlus\Domains\Friends\Models\UserFollowingRecommend::class)->create([
      'user_id'           => $user->id,
      'following_user_id' => $data->following->three->user_id,
      'display_order'     => 300,
      'is_auto_recommend' => 1,
    ]);

    \Cache::shouldReceive('add')
          ->once()
          ->with(sprintf('notify:user:%s:recommendfollowing', $user->id), Mockery::any(), 10)
          ->andReturn(true);

    $this->mockHttpClient(json_encode([
        'code'  => 0,
        'data'  => [
            'relation'  => [
                $data->user->one->id    => [
                    'is_following'=> false,
                    'follow_at'=> null,
                    'is_follower'=> true,
                    'followed_at'=> '2015-01-01 00:00:00',
                ],
                $data->user->two->id    => [
                    'is_following'=> true,
                    'follow_at'=> '2016-01-01 11:11:11',
                    'is_follower'=> true,
                    'followed_at'=> '2015-02-01 00:00:00',
                ],
            ],
        ]
    ]));

    $response = $this->actingAs($user)
                     ->getJson('v3/user/recommend?' . http_build_query([
                        'id'      => $recommendThree->id,
                        'isNext'  => true,
                        'size'    => 2,
                     ]))
                     ->assertJson(['code' => 0]);
    $recommends = (json_decode($response->getContent()))->data->recommends;
    self::assertCount(2, $recommends);
    self::assertEquals($recommendTwo->id, $recommends[0]->id);
    self::assertEquals($data->profile->two->user_id, $recommends[0]->userId);
    self::assertTrue(ends_with($recommends[0]->avatar, 'avatar-two'));
    self::assertEquals('user-two', $recommends[0]->nickname);
    self::assertTrue($recommends[0]->isFollowing);
    self::assertTrue($recommends[0]->isFollower);
    self::assertEquals(1, $recommends[0]->recCategroy);
    self::assertEquals($recommendOne->id, $recommends[1]->id);
    self::assertEquals($data->profile->one->user_id, $recommends[1]->userId);
    self::assertFalse($recommends[1]->isFollowing);
    self::assertTrue($recommends[1]->isFollower);
    self::assertEquals(2, $recommends[1]->recCategroy);
  }

  //====================================
  //      getRecommendWorksByCountry
  //====================================
  public function testGetRecommendWorksByCountrySuccess()
  {
    $user = factory(\SingPlus\Domains\Users\Models\User::class)->create();
    factory(\SingPlus\Domains\Users\Models\UserProfile::class)->create([
      'user_id' => $user->id, 
    ]);

    $data = $this->prepareFollowData($user);
    $recommendOne = factory(\SingPlus\Domains\Friends\Models\UserRecommend::class)->create([
      'user_id'           => $data->user->one->id,
      'country_abbr'      => 'TZ',
      'works_ids'         => [$data->work->two->id, $data->work->one->id],
      'is_auto_recommend' => 0,
      'display_order'     => 100,
      'term'              => \Carbon\Carbon::now('+03:00')->format('Ymd'),
    ]);
    $recommendTwo = factory(\SingPlus\Domains\Friends\Models\UserRecommend::class)->create([
      'user_id'           => $data->user->two->id,
      'country_abbr'      => 'TZ',
      'works_ids'         => [$data->work->three->id, $data->work->four->id],
      'is_auto_recommend' => 1,
      'display_order'     => 200,
      'term'              => \Carbon\Carbon::now('+03:00')->format('Ymd'),
    ]);
    $recommendThree = factory(\SingPlus\Domains\Friends\Models\UserRecommend::class)->create([
      'user_id'           => $data->user->three->id,
      'country_abbr'      => 'KE',
      'works_ids'         => [$data->work->five->id, $data->work->six->id],
      'is_auto_recommend' => 1,
      'display_order'     => 300,
      'term'              => \Carbon\Carbon::now('+03:00')->format('Ymd'),
    ]);

    $this->mockHttpClient(json_encode([
        'code'  => 0,
        'data'  => [
            'relation'  => [
                $data->user->one->id    => [
                    'is_following'=> false,
                    'follow_at'=> null,
                    'is_follower'=> true,
                    'followed_at'=> '2015-01-01 00:00:00',
                ],
                $data->user->two->id    => [
                    'is_following'=> true,
                    'follow_at'=> '2016-01-01 11:11:11',
                    'is_follower'=> true,
                    'followed_at'=> '2015-02-01 00:00:00',
                ],
            ],
        ]
    ]));

    $response = $this->actingAs($user)
                     ->getJson('v3/user/recommend/no-followed', [
                      'X-CountryAbbr' => 'TZ',
                     ])
                     ->assertJson(['code' => 0]);
    $recommends = (json_decode($response->getContent()))->data->recommends;
    self::assertCount(2, $recommends);
    self::assertEquals($recommendTwo->id, $recommends[0]->id);
    self::assertEquals($data->user->two->id, $recommends[0]->userId);
    self::assertEquals('user-two', $recommends[0]->nickname);
    self::assertTrue(ends_with($recommends[0]->avatar, 'avatar-two'));
    self::assertTrue($recommends[0]->isFollowing);
    self::assertTrue($recommends[0]->isFollower);
    self::assertEquals(1, $recommends[0]->recCategroy);
    self::assertCount(2, $recommends[0]->works);
    self::assertEquals($recommendOne->id, $recommends[1]->id);
  }

    public function testGetRecommendWorksByCountrySuccess_WithoutLogin()
    {
        $user = factory(\SingPlus\Domains\Users\Models\User::class)->create();
        factory(\SingPlus\Domains\Users\Models\UserProfile::class)->create([
            'user_id' => $user->id,
        ]);

        $data = $this->prepareFollowData($user);
        $recommendOne = factory(\SingPlus\Domains\Friends\Models\UserRecommend::class)->create([
            'user_id'           => $data->user->one->id,
            'country_abbr'      => 'TZ',
            'works_ids'         => [$data->work->two->id, $data->work->one->id],
            'is_auto_recommend' => 0,
            'display_order'     => 100,
            'term'              => \Carbon\Carbon::now('+03:00')->format('Ymd'),
        ]);
        $recommendTwo = factory(\SingPlus\Domains\Friends\Models\UserRecommend::class)->create([
            'user_id'           => $data->user->two->id,
            'country_abbr'      => 'TZ',
            'works_ids'         => [$data->work->three->id, $data->work->four->id],
            'is_auto_recommend' => 1,
            'display_order'     => 200,
            'term'              => \Carbon\Carbon::now('+03:00')->format('Ymd'),
        ]);
        $recommendThree = factory(\SingPlus\Domains\Friends\Models\UserRecommend::class)->create([
            'user_id'           => $data->user->three->id,
            'country_abbr'      => 'KE',
            'works_ids'         => [$data->work->five->id, $data->work->six->id],
            'is_auto_recommend' => 1,
            'display_order'     => 300,
            'term'              => \Carbon\Carbon::now('+03:00')->format('Ymd'),
        ]);

        $response = $this->getJson('v3/user/recommend/no-followed', [
                'X-CountryAbbr' => 'TZ',
            ])
            ->assertJson(['code' => 0]);
        $recommends = (json_decode($response->getContent()))->data->recommends;
        self::assertCount(2, $recommends);
        self::assertEquals($recommendTwo->id, $recommends[0]->id);
        self::assertEquals($data->user->two->id, $recommends[0]->userId);
        self::assertEquals('user-two', $recommends[0]->nickname);
        self::assertTrue(ends_with($recommends[0]->avatar, 'avatar-two'));
        self::assertFalse($recommends[0]->isFollowing);
        self::assertFalse($recommends[0]->isFollower);
        self::assertEquals(1, $recommends[0]->recCategroy);
        self::assertCount(2, $recommends[0]->works);
        self::assertEquals($recommendOne->id, $recommends[1]->id);
    }

  private function prepareFollowData($user)
  {
    $userOne = factory(\SingPlus\Domains\Users\Models\User::class)->create();
    $userTwo = factory(\SingPlus\Domains\Users\Models\User::class)->create();
    $userThree = factory(\SingPlus\Domains\Users\Models\User::class)->create();
    $profileOne = factory(\SingPlus\Domains\Users\Models\UserProfile::class)->create([
      'user_id'   => $userOne->id, 
      'nickname'  => 'user-one',
      'avatar'    => 'avatar-one',
    ]);
    $profileTwo = factory(\SingPlus\Domains\Users\Models\UserProfile::class)->create([
      'user_id'   => $userTwo->id, 
      'nickname'  => 'user-two',
      'avatar'    => 'avatar-two',
    ]);
    $profileThree = factory(\SingPlus\Domains\Users\Models\UserProfile::class)->create([
      'user_id'   => $userThree->id, 
      'nickname'  => 'user-three',
      'avatar'    => 'avatar-three',
    ]);
    $userFollowing = factory(\SingPlus\Domains\Friends\Models\UserFollowing::class)->create([
      'user_id'     => $user->id,
      'followings'  => [
        $userTwo->id, $userThree->id,
      ],
      'following_details' => [
        [
          'user_id'   => $userTwo->id,
          'follow_at' => \Carbon\Carbon::parse('2016-05-01')->getTimestamp()
        ],
        [
          'user_id'   => $userThree->id,
          'follow_at' => \Carbon\Carbon::parse('2016-06-01')->getTimestamp()
        ],
      ],
      'display_order' => 10,
    ]);
    $userOneFollowing = factory(\SingPlus\Domains\Friends\Models\UserFollowing::class)->create([
      'user_id'     => $userOne->id,
      'followings'  => [
        $user->id, $userTwo->id,
      ],
      'following_details' => [
        [
          'user_id'   => $user->id,
          'follow_at' => \Carbon\Carbon::parse('2016-07-01')->getTimestamp()
        ],
        [
          'user_id'   => $userTwo->id,
          'follow_at' => \Carbon\Carbon::parse('2016-08-01')->getTimestamp()
        ],
      ],
      'display_order' => 100,
    ]);
    $userTwoFollowing = factory(\SingPlus\Domains\Friends\Models\UserFollowing::class)->create([
      'user_id'     => $userTwo->id,
      'followings'  => [
        $user->id,
      ],
      'following_details' => [
        [
          'user_id'   => $user->id,
          'follow_at' => \Carbon\Carbon::parse('2016-09-01')->getTimestamp()
        ],
      ],
      'display_order' => 200,
    ]);
    $userThreeFollowing = factory(\SingPlus\Domains\Friends\Models\UserFollowing::class)->create([
      'user_id'     => $userThree->id,
      'followings'  => [
        $userOne->id,
      ],
      'following_details' => [
        [
          'user_id'   => $userOne->id,
          'follow_at' => \Carbon\Carbon::parse('2016-10-01')->getTimestamp()
        ],
      ],
      'display_order' => 300,
    ]);

    $musicOne = factory(\SingPlus\Domains\Musics\Models\Music::class)->create([
      'name'  => 'music-one',
    ]);
    $musicTwo = factory(\SingPlus\Domains\Musics\Models\Music::class)->create([
      'name'  => 'music-two',
    ]);
    $musicThree = factory(\SingPlus\Domains\Musics\Models\Music::class)->create([
      'name'  => 'music-three',
    ]);
    $musicFour = factory(\SingPlus\Domains\Musics\Models\Music::class)->create([
      'name'  => 'music-four',
    ]);

    $workOne = factory(\SingPlus\Domains\Works\Models\Work::class)->create([
      'user_id'       => $userOne->id,
      'music_id'      => $musicOne->id,
      'cover'         => 'work-one-cover',
      'listen_count'  => 101,
      'chorus_type'   => 1,
    ]);
    $workTwo = factory(\SingPlus\Domains\Works\Models\Work::class)->create([
      'user_id'       => $userOne->id,
      'music_id'      => $musicOne->id,
      'cover'         => 'work-two-cover',
      'listen_count'  => 102,
      'chorus_type'   => 1,
    ]);
    $workThree = factory(\SingPlus\Domains\Works\Models\Work::class)->create([
      'user_id'       => $userTwo->id,
      'music_id'      => $musicTwo->id,
      'cover'         => 'work-three-cover',
      'listen_count'  => 103,
      'chorus_type'   => 10,
    ]);
    $workFour = factory(\SingPlus\Domains\Works\Models\Work::class)->create([
      'user_id'       => $userTwo->id,
      'music_id'      => $musicThree->id,
      'cover'         => 'work-four-cover',
      'listen_count'  => 104,
      'chorus_type'   => 10,
    ]);
    $workFive = factory(\SingPlus\Domains\Works\Models\Work::class)->create([
      'user_id'       => $userThree->id,
      'music_id'      => $musicFour->id,
      'cover'         => 'work-five-cover',
      'listen_count'  => 105,
    ]);
    $workSix = factory(\SingPlus\Domains\Works\Models\Work::class)->create([
      'user_id'       => $userThree->id,
      'music_id'      => $musicFour->id,
      'cover'         => 'work-six-cover',
      'listen_count'  => 106,
    ]);

    return (object) [
      'user'  => (object) [
        'one' => $userOne,
        'two' => $userTwo,
        'three' => $userThree,
      ],
      'profile' => (object) [
        'one' => $profileOne,
        'two' => $profileTwo,
        'three' => $profileThree,
      ],
      'following' => (object) [
        'one' => $userOneFollowing,
        'two' => $userTwoFollowing,
        'three' => $userThreeFollowing,
      ],
      'music'   => (object) [
        'one'   => $musicOne,
        'two'   => $musicTwo,
        'three' => $musicThree,
        'four'  => $musicFour,
      ],
      'work'    => (object) [
        'one'   => $workOne,
        'two'   => $workTwo,
        'three' => $workThree,
        'four'  => $workFour,
        'five'  => $workFive,
        'six'   => $workSix,
      ],
    ];
  }

  /** 
   * mock http client and response
   * 模拟http响应
   *
   * Usage: $this->mockHttpClient(json_encode([
   *          'code' => 0,
   *          'data' => [],
   *          'message'   => 'ok',
   *          ]))
   *
   * @param string $respBody 模拟响应body
   * @param int $respCode 模拟响应http status
   * @param array $respHeader 模拟响应http header
   */
  protected function mockHttpClient(
    $respBody,
    $respCode = 200,
    array $respHeader = []
  ) {
    $mock = new MockHandler();
    $mock->append(new Response($respCode, $respHeader, $respBody));

    $handler = HandlerStack::create($mock);

    $this->app[\GuzzleHttp\ClientInterface::class] = new Client(['handler' => $handler]);
  }
}
