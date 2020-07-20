<?php

namespace FeatureTest\SingPlus\Controllers;

use GuzzleHttp\Client;
use GuzzleHttp\Handler\MockHandler;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Psr7\Response;
use Bus;
use Mockery;
use Cache;
use Queue;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use FeatureTest\SingPlus\TestCase;
use FeatureTest\SingPlus\MongodbClearTrait;

class UserControllerTest extends TestCase
{
  use MongodbClearTrait; 

  //=================================
  //        changeLoginPassword
  //=================================
  public function testChangeLoginPasswordSuccess()
  {
    $user = factory(\SingPlus\Domains\Users\Models\User::class)->create([
      'mobile'    => '2547200000001',
      'password'  => '$2y$10$IYcxJ287/u/hsapWmT/TEeHwpCk7CFoKYe99LgZMoL1mxTzVElQSW', // raw password is: '*******'
    ]);
    factory(\SingPlus\Domains\Users\Models\UserProfile::class)->create([
      'user_id' => $user->id, 
    ]);

    $this->actingAs($user)
         ->postJson('v3/user/password/update', [
            'oldPassword' => '*******',
            'password'    => '123456',
         ])->assertJson(['code' => 0]);

    // user login wich new password success
    $this->postJson('v3/passport/login', [
      'countryCode'   => '254',
      'mobile'        => '7200000001',
      'password'      => '123456',
    ])->assertJson(['code' => 0]);
  }

  public function testChangeLoginPasswordFailed_OldPasswordIncorrect()
  {
    $user = factory(\SingPlus\Domains\Users\Models\User::class)->create([
      'mobile'    => '2547200000001',
      'password'  => '$2y$10$IYcxJ287/u/hsapWmT/TEeHwpCk7CFoKYe99LgZMoL1mxTzVElQSW', // raw password is: '*******'
    ]);
    factory(\SingPlus\Domains\Users\Models\UserProfile::class)->create([
      'user_id' => $user->id, 
    ]);

    $this->actingAs($user)
         ->postJson('v3/user/password/update', [
            'oldPassword' => 'ccccccccc',
            'password'    => '123456',
         ])->assertJson(['code' => 10104]);
  }

  //=================================
  //        initLoginPassword
  //=================================
  public function testInitLoginPasswordSuccess()
  {
    $user = factory(\SingPlus\Domains\Users\Models\User::class)->create([
      'country_code'  => '254',
      'mobile'        => '2547200000001',
      'password'      => null,
    ]);
    factory(\SingPlus\Domains\Users\Models\UserProfile::class)->create([
      'user_id' => $user->id, 
    ]);

    $response = $this->actingAs($user)
                     ->postJson('v3/user/password/init', [
                        'password'  => '123456',
                     ]);
    $response->assertJson(['code' => 0]);

    // user login wich new password success
    $this->postJson('v3/passport/login', [
      'countryCode'   => '254',
      'mobile'        => '7200000001',
      'password'      => '123456',
    ])->assertJson(['code' => 0]);
  }

  public function testInitLoginPasswordFailed_PasswordAreadyInit()
  {
    $user = factory(\SingPlus\Domains\Users\Models\User::class)->create([
      'country_code'  => '254',
      'mobile'        => '2547200000001',
      'password'  => '$2y$10$IYcxJ287/u/hsapWmT/TEeHwpCk7CFoKYe99LgZMoL1mxTzVElQSW', // raw password is: '*******'
    ]);
    factory(\SingPlus\Domains\Users\Models\UserProfile::class)->create([
      'user_id' => $user->id, 
    ]);

    $response = $this->actingAs($user)
                     ->postJson('v3/user/password/init', [
                        'password'  => '123456',
                     ]);
    $response->assertJson([
      'code'    => 10000,
      'message' => 'password aready init',
    ]);
  }

  public function testInitLoginPasswordFailed_MobileNotBound()
  {
    $user = factory(\SingPlus\Domains\Users\Models\User::class)->create([
      'country_code'  => null,
      'mobile'        => null,
      'password'      => null,
    ]);
    factory(\SingPlus\Domains\Users\Models\UserProfile::class)->create([
      'user_id' => $user->id, 
    ]);

    $response = $this->actingAs($user)
                     ->postJson('v3/user/password/init', [
                        'password'  => '123456',
                     ]);
    $response->assertJson(['code' => 10106]);
  }

  //=================================
  //        getUserProfile
  //=================================
  public function testGetUserProfileSuccess()
  {
    $user = factory(\SingPlus\Domains\Users\Models\User::class)->create([
      'country_code'  => '254',
      'mobile'        => '2547200000001',
    ]);
    factory(\SingPlus\Domains\Users\Models\UserProfile::class)->create([
      'user_id'   => $user->id,
      'nickname'  => 'Martin',
      'avatar'    => sprintf('test-bucket/%s/images/0305b27e047a11e7ac640800276e6868', $user->id),
      'birth_date'  => '2002-02-03',
      'is_new'    => false,
      'popularity_info' => [
          'work_popularity' => 0,
          'hierarchy_name'  => "worker",
          'hierarchy_icon'  => 'icon.jpg',
          'hierarchy_gap'   => 100
      ],
      'consume_coins_info' => [
          'consume_coins' => 0,
          'hierarchy_name' => 'king',
          'hierarchy_icon' => 'icon.jpg',
          'hierarchy_gap' => 100
      ],
      'work_count' => 47,
      'statistics_info' => [
          'work_chorus_start_count' => 1
      ]
    ]);

    \Cache::shouldReceive('get')
         ->once()
         ->with(sprintf('user:%s:listennum', $user->id))
         ->andReturn(188);

    $storageService = $this->mockStorage();
    $storageService->shouldReceive('toHttpUrl')
                   ->with(sprintf('test-bucket/%s/images/0305b27e047a11e7ac640800276e6868', $user->id))
                   ->once()
                   ->andReturn('http://image.sing.plus/0305b27e047a11e7ac640800276e6868');
    $storageService->shouldReceive('toHttpUrl')
                   ->with('')
                   ->times(4)
                   ->andReturn('http://image.sing.plus/icon.jpg');

    $response = $this->actingAs($user)
                     ->getJson('v3/user/info');

    $response->assertJson([
            'code'  => 0,
            'data'  => [
              'userId'        => $user->id,
              'isPasswordSet' => true,
              'avatar'        => 'http://image.sing.plus/0305b27e047a11e7ac640800276e6868',
              'mobile'        => '7200000001',    // without country code part
              'nickname'      => 'Martin',
              'sex'           => NULL,
              'birthDate'     => '2002-02-03',
              'views'         => 188,
              'gold'          => 0,
              'friend'        => null,        // self not eval friend info
              'verified'      => [
                    'verified'  => false,
                    'names'     => [],
              ],
            ],
          ]);
  }

  public function testGetUserProfileSuccess_AvatarMissing()
  {
    $user = factory(\SingPlus\Domains\Users\Models\User::class)->create([
      'country_code'  => '254',
      'mobile'        => '2547200000001',
      'password'      => null,
    ]);
    factory(\SingPlus\Domains\Users\Models\UserProfile::class)->create([
      'user_id'   => $user->id,
      'nickname'  => 'Martin',
      'avatar'    => null,
      'is_new'    => false,
      'coins'     => [
          'balance' => 1000,
          'gift_consume' => 10
      ]
    ]);
    \Cache::shouldReceive('get')
         ->once()
         ->with(sprintf('user:%s:listennum', $user->id))
         ->andReturn(188);

    $response = $this->actingAs($user)
         ->getJson('v3/user/info');
    $response->assertJson([
      'code'  => 0,
      'data'  => [
        'isPasswordSet' => false,
        'avatar'        => null,
        'mobile'        => '7200000001',    // without country code part
        'nickname'      => 'Martin',
        'sex'           => NULL,
        'views'         => 188,
        'gold'          => 1000,
        'friend'        => null,            // self not eval friend info
      ],
    ]);
  }

  public function testGetUserProfileSuccess_OtherProfile()
  {
    $user = factory(\SingPlus\Domains\Users\Models\User::class)->create([
      'country_code'  => '254',
      'mobile'        => '2547200000001',
    ]);
    factory(\SingPlus\Domains\Users\Models\UserProfile::class)->create([
      'user_id' => $user->id, 
    ]);
    $other = factory(\SingPlus\Domains\Users\Models\User::class)->create([
      'country_code'  => '254',
      'mobile'        => '2547200000002',
    ]);
    factory(\SingPlus\Domains\Users\Models\UserProfile::class)->create([
      'user_id'   => $other->id,
      'nickname'  => 'Martin',
      'avatar'    => null,
    ]);
    \Cache::shouldReceive('get')
         ->once()
         ->with(sprintf('user:%s:listennum', $other->id))
         ->andReturn(188);

    $this->mockHttpClient([
        [
            'body'  => json_encode([
                'code'  => 0,
                'data'  => [
                    'relation'  => [
                        $other->id  => [
                            'is_following'=> false,
                            'follow_at'=> null,
                            'is_follower'=> false,
                            'followed_at'=> null,
                        ],
                    ],
                ],
            ])
        ],
    ]);

    $response = $this->actingAs($user)
         ->getJson('v3/user/info?' . http_build_query([
            'userId'  => $other->id, 
         ]));
    $response->assertJson([
      'code'  => 0,
      'data'  => [
        'avatar'    => null,
        'mobile'    => '7200000002',    // without country code part
        'nickname'  => 'Martin',
        'friend'    => [
          'isFollowing' => false,
          'isFollower' => false,
        ],
        'sex'       => NULL,
        'views'     => 188,
      ],
    ]);
    $response = json_decode($response->getContent());
    self::assertFalse(isset($response->data->isPasswordSet));
  }

  public function testGetUserProfileSuccess_OtherProfileWithoutLogin()
  {
      $other = factory(\SingPlus\Domains\Users\Models\User::class)->create([
          'country_code'  => '254',
          'mobile'        => '2547200000002',
      ]);
      factory(\SingPlus\Domains\Users\Models\UserProfile::class)->create([
          'user_id'   => $other->id,
          'nickname'  => 'Martin',
          'avatar'    => null,
          'follower_count' => 188,
          'following_count' => 200,
      ]);

      \Cache::shouldReceive('get')
          ->once()
          ->with(sprintf('user:%s:listennum', $other->id))
          ->andReturn(188);

      $response = $this
          ->getJson('v3/user/info?' . http_build_query([
                  'userId'  => $other->id,
              ]));
      $response->assertJson([
          'code'  => 0,
          'data'  => [
              'avatar'    => null,
              'mobile'    => '7200000002',    // without country code part
              'nickname'  => 'Martin',
              'friend'    => null,
              'sex'       => NULL,
              'views'     => 188,
              'followers' => 188,
              'following' => 200,
          ],
      ]);

      $response = json_decode($response->getContent());
      self::assertFalse(isset($response->data->isPasswordSet));
  }

  public function testGetUserProfileSuccess_UserNotExists()
  {
    $user = factory(\SingPlus\Domains\Users\Models\User::class)->create([
      'country_code'  => '254',
      'mobile'        => '2547200000001',
    ]);
    factory(\SingPlus\Domains\Users\Models\UserProfile::class)->create([
      'user_id' => $user->id, 
    ]);

    $response = $this->actingAs($user)
         ->getJson('v3/user/info?' . http_build_query([
            'userId'  => '7842eaa9f17d4c07a6c4d60f4f8bbd29',
         ]));
    $response->assertJson([
      'code'  => 0,
      'data'  => [
        'avatar'    => null,
        'mobile'    => null,
        'nickname'  => null,
        'sex'       => null,
      ],
    ]);
  }

    public function testGetUserProfileSuccess_UserNotExists_WithoutLogin()
    {
        $response = $this->getJson('v3/user/info?' . http_build_query([
                    'userId'  => '7842eaa9f17d4c07a6c4d60f4f8bbd29',
                ]));
        $response->assertJson([
            'code'  => 0,
            'data'  => [
                'avatar'    => null,
                'mobile'    => null,
                'nickname'  => null,
                'sex'       => null,
            ],
        ]);
    }

    public function testGetUserProfileSuccess_WithModifiedLocation()
    {
        $user = factory(\SingPlus\Domains\Users\Models\User::class)->create([
            'country_code'  => '254',
            'mobile'        => '2547200000001',
            'password'      => null,
        ]);
        factory(\SingPlus\Domains\Users\Models\UserProfile::class)->create([
            'user_id'   => $user->id,
            'nickname'  => 'Martin',
            'avatar'    => null,
            'is_new'    => false,
            'coins'     => [
                'balance' => 1000,
                'gift_consume' => 10
            ],
            'location' =>[
                'longitude'=> '104.0651457',
                'latitude' => '30.6568944',
                'country_code' => '86',
                'modified_at' => '2018-03-13 10:48:15',
                'abbreviation' => 'CN',
                'modified_by_user' => true,
                "city" => 'shanghai'
            ]
        ]);

        \Cache::shouldReceive('get')
            ->once()
            ->with(sprintf('user:%s:listennum', $user->id))
            ->andReturn(188);

        $response = $this->actingAs($user)
            ->getJson('v3/user/info');
        $response->assertJson([
            'code'  => 0,
            'data'  => [
                'isPasswordSet' => false,
                'avatar'        => null,
                'mobile'        => '7200000001',    // without country code part
                'nickname'      => 'Martin',
                'sex'           => NULL,
                'views'         => 188,
                'gold'          => 1000,
                'friend'        => null,            // self not eval friend info
                'city' => 'shanghai',
                'countryCode' => '86',
                'countryName' => 'China',
                'countryAbbr' => 'CN'
            ],
        ]);
    }

//  public function testGetUserProfileSuccess_BeOther()
//  {
//    config([
//        'auth.guards.web.godmode' => true,
//      ]);
//    $actingUser = factory(\SingPlus\Domains\Users\Models\User::class)->create([
//      'country_code'  => '86',
//      'mobile'        => '13800138000',
//    ]);
//    factory(\SingPlus\Domains\Users\Models\UserProfile::class)->create([
//      'user_id'   => $actingUser->id,
//      'nickname'  => 'Lee',
//      'avatar'    => sprintf('test-bucket/%s/images/0305b27e047a11e7ac640800276e6868', $actingUser->id),
//      'birth_date'  => '2002-08-03',
//      'is_new'    => false,
//    ]);
//
//    $user = factory(\SingPlus\Domains\Users\Models\User::class)->create([
//      'country_code'  => '254',
//      'mobile'        => '2547200000001',
//    ]);
//    factory(\SingPlus\Domains\Users\Models\UserProfile::class)->create([
//      'user_id'   => $user->id,
//      'nickname'  => 'Martin',
//      'avatar'    => sprintf('test-bucket/%s/images/0305b27e047a11e7ac640800276e6868', $user->id),
//      'birth_date'  => '2002-02-03',
//      'is_new'    => false,
//    ]);
//
//    \Cache::shouldReceive('get')
//         ->once()
//         ->with(sprintf('user:%s:listennum', $actingUser->id))
//         ->andReturn(188);
//
//    $storageService = $this->mockStorage();
//    $storageService->shouldReceive('toHttpUrl')
//                   ->with(sprintf('test-bucket/%s/images/0305b27e047a11e7ac640800276e6868', $actingUser->id))
//                   ->once()
//                   ->andReturn('http://image.sing.plus/0305b27e047a11e7ac640800276e6868');
//
//    $response = $this->actingAs($user)
//                     ->getJson('v3/user/info?' . http_build_query([
//                        'asUserForDebug'  => $actingUser->id,
//                     ]));
//
//    $response->assertJson([
//            'code'  => 0,
//            'data'  => [
//              'userId'        => $actingUser->id,
//              'isPasswordSet' => true,
//            ],
//          ]);
//  }


    //=================================
    //        getUsersProfiles
    //=================================
    public function testGetUsersProfilesSuccess()
    {
        $this->enableAuthUserLastVistInfoSaveInAuthenticateMiddleware();
        $user = factory(\SingPlus\Domains\Users\Models\User::class)->create([
            'country_code'  => '254',
            'mobile'        => '2547200000001',
        ]);
        factory(\SingPlus\Domains\Users\Models\UserProfile::class)->create([
            'user_id'   => $user->id,
            'nickname'  => 'Martin',
            'avatar'    => sprintf('test-bucket/%s/images/0305b27e047a11e7ac640800276e6868', $user->id),
            'birth_date'  => '2002-02-03',
            'is_new'    => false,
        ]);

        $userOne = factory(\SingPlus\Domains\Users\Models\User::class)->create([
            'country_code'  => '254',
            'mobile'        => '2547200000002',
        ]);

        factory(\SingPlus\Domains\Users\Models\UserProfile::class)->create([
            'user_id'   => $userOne->id,
            'nickname'  => 'Martin',
            'avatar'    => sprintf('test-bucket/%s/images/0305b27e047a11e7ac640800276e6868', $userOne->id),
            'birth_date'  => '2002-02-03',
            'is_new'    => false,
        ]);

        $userTow = factory(\SingPlus\Domains\Users\Models\User::class)->create([
            'country_code'  => '254',
            'mobile'        => '2547200000003',
        ]);

        factory(\SingPlus\Domains\Users\Models\UserProfile::class)->create([
            'user_id'   => $userTow->id,
            'nickname'  => 'Martin',
            'avatar'    => sprintf('test-bucket/%s/images/0305b27e047a11e7ac640800276e6868', $userTow->id),
            'birth_date'  => '2002-02-03',
            'is_new'    => false,
        ]);

        $userThere = factory(\SingPlus\Domains\Users\Models\User::class)->create([
            'country_code'  => '254',
            'mobile'        => '2547200000004',
        ]);

        factory(\SingPlus\Domains\Users\Models\UserProfile::class)->create([
            'user_id'   => $userThere->id,
            'nickname'  => 'Martin',
            'avatar'    => sprintf('test-bucket/%s/images/0305b27e047a11e7ac640800276e6868', $userThere->id),
            'birth_date'  => '2002-02-03',
            'is_new'    => false,
        ]);

        Queue::fake();
        Cache::shouldReceive('add')
            ->once()
            ->with(sprintf('user:lastvist:lock:%s', $user->id), $user->id, 5)
            ->andReturn(true);

        $storageService = $this->mockStorage();
        $storageService->shouldReceive('toHttpUrl')
            ->with(sprintf('test-bucket/%s/images/0305b27e047a11e7ac640800276e6868', $userOne->id))
            ->once()
            ->andReturn('http://image.sing.plus/0305b27e047a11e7ac640800276e6868');

        $storageService->shouldReceive('toHttpUrl')
            ->with(sprintf('test-bucket/%s/images/0305b27e047a11e7ac640800276e6868', $userTow->id))
            ->once()
            ->andReturn('http://image.sing.plus/0305b27e047a11e7ac640800276e6868');

        $storageService->shouldReceive('toHttpUrl')
            ->with(sprintf('test-bucket/%s/images/0305b27e047a11e7ac640800276e6868', $userThere->id))
            ->once()
            ->andReturn('http://image.sing.plus/0305b27e047a11e7ac640800276e6868');

        $responseMock = new class([$userOne, $userTow, $userThere]) {
            public function __construct($users) {
                $this->users = $users;
            }
            public function getStatusCode () {
                return 200; 
            }
            public function getBody() {
                return json_encode([
                    'code'  => 0,
                    'data'  => [
                        'relation'  => [
                            $this->users[0]->id => [
                                'is_following'=> false,
                                'follow_at'=> null,
                                'is_follower'=> false,
                                'followed_at'=> null,
                            ],
                            $this->users[1]->id => [
                                'is_following'=> false,
                                'follow_at'=> null,
                                'is_follower'=> false,
                                'followed_at'=> null,
                            ],
                            $this->users[2]->id => [
                                'is_following'=> false,
                                'follow_at'=> null,
                                'is_follower'=> false,
                                'followed_at'=> null,
                            ],
                        ],
                    ],
                ]);
            }
        };
        $httpClient = Mockery::mock(\GuzzleHttp\ClientInterface::class);
        $this->app[\GuzzleHttp\ClientInterface::class] = $httpClient;
        $httpClient->shouldReceive('request')
                   ->andReturn($responseMock);

        $response = $this->actingAs($user)
            ->postJson('v3/user/info-multi',[
                'ids' => [
                                $userOne->id,
                                $userTow->id,
                                $userThere->id,
                              ],

            ],[
                'X-Version' => 'v1.0.2',
            ]);
        $response->assertJson([
            'code'  => 0,
        ]);
    }

    public function testGetUsersProfilesFailed_InvalidIds()
    {
        $user = factory(\SingPlus\Domains\Users\Models\User::class)->create([
            'country_code'  => '254',
            'mobile'        => '2547200000001',
        ]);
        factory(\SingPlus\Domains\Users\Models\UserProfile::class)->create([
            'user_id'   => $user->id,
            'nickname'  => 'Martin',
            'avatar'    => sprintf('test-bucket/%s/images/0305b27e047a11e7ac640800276e6868', $user->id),
            'birth_date'  => '2002-02-03',
            'is_new'    => false,
        ]);

        $userOne = factory(\SingPlus\Domains\Users\Models\User::class)->create([
            'country_code'  => '254',
            'mobile'        => '2547200000002',
        ]);

        $response = $this->actingAs($user)
            ->postJson('v3/user/info-multi',[
                'ids' =>
                    $userOne->id,
            ],[
                'X-Version' => 'v1.0.2',
            ]);
        $response->assertJson([
            'code'  => 10001,
            'message' => 'The ids must be an array.',
        ]);
    }

  //=================================
  //        modifyUserProfile
  //=================================
  public function testModifyUserProfileSuccess()
  {
    $this->enableAuthUserLastVistInfoSaveInAuthenticateMiddleware();

    $user = factory(\SingPlus\Domains\Users\Models\User::class)->create();
    $profile = factory(\SingPlus\Domains\Users\Models\UserProfile::class)->create([
      'user_id'   => $user->id,
      'nickname'  => 'Martin',
      'gender'    => NULL,
      'is_new'    => false,
    ]);

    Queue::fake();
    Cache::shouldReceive('add')
         ->once()
         ->with(sprintf('user:lastvist:lock:%s', $user->id), $user->id, 5)
         ->andReturn(true);

    $response = $this->actingAs($user)
        ->postJson('v3/user/info/update', [
          'nickName'  => 'John',
          'sex'       => 'M',
          'sign'      => 'Jambo Bwana',
          'birthDate' => '2003-02-03',
        ], [
          'X-Version' => 'v1.0.2', 
        ]);

    Queue::assertPushed(
      \SingPlus\Jobs\SaveAuthUserLastVisitInfo::class,
      function ($job) use ($user) {
        return $job->userId == $user->id &&
               $job->info->version == 'v1.0.2';
      });
    Queue::assertPushedOn(
      'sing_plus_api_last_visit',
      \SingPlus\Jobs\SaveAuthUserLastVisitInfo::class
    );

    $response->assertJson(['code'  => 0]);

    $this->assertDatabaseHas('user_profiles', [
      'user_id'     => $user->id,
      'nickname'    => 'John',      // nickname aready changed
      'gender'      => 'M',
      'signature'   => 'Jambo Bwana',
      'birth_date'  => '2003-02-03',
    ], 'mongodb');
  }

  public function testModifyUserProfileSuccess_GetSaveLastVisitLockFailed()
  {
    $this->enableAuthUserLastVistInfoSaveInAuthenticateMiddleware();

    $user = factory(\SingPlus\Domains\Users\Models\User::class)->create();
    $profile = factory(\SingPlus\Domains\Users\Models\UserProfile::class)->create([
      'user_id'   => $user->id,
      'nickname'  => 'Martin',
      'gender'    => NULL,
      'is_new'    => false,
    ]);

    Queue::fake();
    Cache::shouldReceive('add')
         ->once()
         ->with(sprintf('user:lastvist:lock:%s', $user->id), $user->id, 5)
         ->andReturn(false);

    $response = $this->actingAs($user)
        ->postJson('v3/user/info/update', [
          'nickName'  => 'John',
          'sex'       => 'M',
          'sign'      => 'Jambo Bwana',
          'birthDate' => '2003-02-03',
        ], [
          'X-Version' => 'v1.0.2', 
        ])
        ->assertJson(['code'  => 0]);

    Queue::assertNotPushed(\SingPlus\Jobs\SaveAuthUserLastVisitInfo::class);

    $this->assertDatabaseHas('user_profiles', [
      'user_id'     => $user->id,
      'nickname'    => 'John',      // nickname aready changed
      'gender'      => 'M',
      'signature'   => 'Jambo Bwana',
      'birth_date'  => '2003-02-03',
    ], 'mongodb');
  }

  public function testModifyUserProfileSuccess_ModifySex()
  {
    $user = factory(\SingPlus\Domains\Users\Models\User::class)->create();
    $profile = factory(\SingPlus\Domains\Users\Models\UserProfile::class)->create([
      'user_id'   => $user->id,
      'nickname'  => 'Martin',
      'gender'    => NULL,
      'is_new'    => false,
    ]);

    $response = $this->actingAs($user)
        ->postJson('v3/user/info/update', [
          'sex'       => 'M',
          'sign'      => 'Jambo Bwana',
        ]);

    $response->assertJson(['code'  => 0]);

    $this->assertDatabaseHas('user_profiles', [
      'user_id'     => $user->id,
      'nickname'    => 'Martin',      // nickname aready changed
      'gender'      => 'M',
      'signature'   => 'Jambo Bwana',
    ], 'mongodb');
  }

  public function testModifyUserProfileFailed_GenderAreadySet()
  {
    $user = factory(\SingPlus\Domains\Users\Models\User::class)->create();
    $profile = factory(\SingPlus\Domains\Users\Models\UserProfile::class)->create([
      'user_id'   => $user->id,
      'nickname'  => 'Martin',
      'gender'    => 'F',
      'is_new'    => false,
    ]);

    $response = $this->actingAs($user)
        ->postJson('v3/user/info/update', [
          'nickName'  => 'John',
          'sex'       => 'M',
          'sign'      => 'Jambo Bwana',
        ]);

    $response->assertJson(['code' => 10000]);

    $this->assertDatabaseHas('user_profiles', [
      'user_id'   => $user->id,
      'gender'    => 'F',           // gender not change
    ], 'mongodb');
  }

  public function testModifyUserProfileFailed_WithoutLogin()
  {
      $this->enableAuthUserLastVistInfoSaveInAuthenticateMiddleware();

      $user = factory(\SingPlus\Domains\Users\Models\User::class)->create();
      $profile = factory(\SingPlus\Domains\Users\Models\UserProfile::class)->create([
          'user_id'   => $user->id,
          'nickname'  => 'Martin',
          'gender'    => NULL,
          'is_new'    => false,
      ]);

      Queue::fake();
      Cache::shouldReceive('add')
          ->never()
          ->with(sprintf('user:lastvist:lock:%s', $user->id), $user->id, 5)
          ->andReturn(true);

      $response = $this
          ->postJson('v3/user/info/update', [
              'nickName'  => 'John',
              'sex'       => 'M',
              'sign'      => 'Jambo Bwana',
              'birthDate' => '2003-02-03',
          ], [
              'X-Version' => 'v1.0.2',
          ]);
      $response->assertJson(['code'  => 10101]);
  }

  //=================================
  //        completeUserProfile
  //=================================
  public function testCompleteUserProfileSuccess()
  {
    $user = factory(\SingPlus\Domains\Users\Models\User::class)->create();
    $image = factory(\SingPlus\Domains\Users\Models\UserImage::class)->create([
      'user_id'   => $user->id,
      'is_avatar' => \SingPlus\Domains\Users\Models\UserImage::AVATAR_NO, 
      'uri'       => 'image-one',
    ]);

    $response = $this->actingAs($user)
        ->postJson('v3/user/info/complete', [
          'nickName'  => 'Martin',
          'imageId'   => $image->id,
        ]);

    $response->assertJson(['code' => 0]);
    $this->assertDatabaseHas('user_profiles', [
      'user_id'   => $user->id,
      'nickname'  => 'Martin',
      'is_new'    => false,
    ], 'mongodb');
    $this->assertDatabaseHas('user_images', [
      '_id'       => $image->id,
      'is_avatar' => \SingPlus\Domains\Users\Models\UserImage::AVATAR_YES,
    ], 'mongodb');

    $profile = \SingPlus\Domains\Users\Models\UserProfile::where('user_id', $user->id)->first();
    self::assertTrue(ends_with($profile->avatar, 'image-one'));
  }

  public function testCompleteUserProfileSuccess_FromSocialiteWithImageId()
  {
    $user = factory(\SingPlus\Domains\Users\Models\User::class)->create();
    $image = factory(\SingPlus\Domains\Users\Models\UserImage::class)->create([
      'user_id'   => $user->id,
      'is_avatar' => \SingPlus\Domains\Users\Models\UserImage::AVATAR_NO, 
      'uri'       => 'image-one',
    ]);

    $response = $this->actingAs($user)
                     ->withSession(['loginFromSocialite' => true])
                     ->postJson('v3/user/info/complete', [
                        'nickName'  => 'Martin',
                        'imageId'   => $image->id,
                     ]);

    $response->assertJson(['code' => 0]);
    $this->assertDatabaseHas('user_profiles', [
      'user_id'   => $user->id,
      'nickname'  => 'Martin',
      'is_new'    => false,
    ], 'mongodb');
    $this->assertDatabaseHas('user_images', [
      '_id'       => $image->id,
      'is_avatar' => \SingPlus\Domains\Users\Models\UserImage::AVATAR_YES,
    ], 'mongodb');

    $profile = \SingPlus\Domains\Users\Models\UserProfile::where('user_id', $user->id)->first();
    self::assertTrue(ends_with($profile->avatar, 'image-one'));
  }

  public function testCompleteUserProfileSuccess_FromSocialiteWithSocialiteAvatar()
  {
    $user = factory(\SingPlus\Domains\Users\Models\User::class)->create();

    $counterMock = Mockery::mock(\Illuminate\Contracts\Cache\Store::class);
    $counterMock->shouldReceive('increment')
                ->once()
                ->with('user_images', 100)
                ->andReturn(100);
    Cache::shouldReceive('driver')
         ->once()
         ->with('counter')
         ->andReturn($counterMock);

    $remotePath = sprintf('%s/test_datas/tesla_logo.jpg', __DIR__);
    $tmpPath = storage_path(sprintf('app/tmp/avatar/%s', $user->id));
    $storageService = $this->mockStorage();
    $storageService->shouldReceive('store')
                   ->once()
                   ->with($tmpPath, Mockery::on(function (array $options) use ($user) {
                      return isset($options['prefix']) &&
                              $options['prefix'] == sprintf('pizzas/images-origin/%s', $user->id);
                   }))
                   ->andReturn(sprintf('pizzas/images/%s/0305b27e047a11e7ac640800276e6868', $user->id));

    $response = $this->actingAs($user)
                     ->withSession([
                        'loginFromSocialite'  => true,
                        'socialiteAvatar'     => $remotePath,
                     ])
                     ->postJson('v3/user/info/complete', [
                        'nickName'  => 'Martin',
                     ]);
    $response->assertJson(['code' => 0]);
    $this->assertDatabaseHas('user_profiles', [
      'user_id'   => $user->id,
      'nickname'  => 'Martin',
      'is_new'    => false,
    ], 'mongodb');
    $this->assertDatabaseHas('user_images', [
      'user_id'   => $user->id,
      'uri'       => sprintf('pizzas/images/%s/0305b27e047a11e7ac640800276e6868', $user->id),
      'is_avatar' => \SingPlus\Domains\Users\Models\UserImage::AVATAR_YES,
      'display_order' => 100,
    ], 'mongodb');

    self::assertFalse(file_exists($tmpPath));    // check tmp path aready deleted
  }

  public function testCompleteUserProfileFailed_FromSocialiteAvatarMissed()
  {
    $user = factory(\SingPlus\Domains\Users\Models\User::class)->create();

    $response = $this->actingAs($user)
                     ->withSession(['loginFromSocialite' => true])
                     ->postJson('v3/user/info/complete', [
                        'nickName'  => 'Martin',
                     ]);
    $response->assertJson(['code' => 10001]);
  }

  public function testCompleteUserProfileFailed_FromSocialiteNicknameAreadyUsed()
  {
    $user = factory(\SingPlus\Domains\Users\Models\User::class)->create();
    factory(\SingPlus\Domains\Users\Models\UserProfile::class)->create([
      'nickname'  => 'Martin',
    ]);

    $response = $this->actingAs($user)
                     ->withSession(['loginFromSocialite' => true])
                     ->postJson('v3/user/info/complete', [
                        'nickName'  => 'Martin',
                        'imageId'   => 'a2fa8997458341d2b6d85e0ff9f2f534',
                     ]);
    $response->assertJson(['code' => 10113]);
  }

  public function testCompleteUserProfileFailed_NicknameAreadyUsed()
  {
    $user = factory(\SingPlus\Domains\Users\Models\User::class)->create();
    factory(\SingPlus\Domains\Users\Models\UserProfile::class)->create([
      'nickname'  => 'Martin',
    ]);

    $response = $this->actingAs($user)
                     ->postJson('v3/user/info/complete', [
                        'nickName'  => 'Martin',
                        'imageId'   => 'a2fa8997458341d2b6d85e0ff9f2f534',
                     ]);
    $response->assertJson(['code' => 10113]);
  }


  public function testAutoCompleteUserProfileSuccess_FromSocialiteWithSocialiteAvatar()
  {
        $user = factory(\SingPlus\Domains\Users\Models\User::class)->create();

        $counterMock = Mockery::mock(\Illuminate\Contracts\Cache\Store::class);
        $counterMock->shouldReceive('increment')
            ->once()
            ->with('user_images', 100)
            ->andReturn(100);
        Cache::shouldReceive('driver')
            ->once()
            ->with('counter')
            ->andReturn($counterMock);

        $remotePath = sprintf('%s/test_datas/tesla_logo.jpg', __DIR__);
        $tmpPath = storage_path(sprintf('app/tmp/avatar/%s', $user->id));
        $storageService = $this->mockStorage();
        $storageService->shouldReceive('store')
            ->once()
            ->with($tmpPath, Mockery::on(function (array $options) use ($user) {
                return isset($options['prefix']) &&
                    $options['prefix'] == sprintf('pizzas/images-origin/%s', $user->id);
            }))
            ->andReturn(sprintf('pizzas/images/%s/0305b27e047a11e7ac640800276e6868', $user->id));

        $storageService->shouldReceive('toHttpUrl')
            ->once()
            ->with(config('image.default_avatar'))
            ->andReturn('http://image.sing.plus/activity/images/default_avatar_round.png');

        $response = $this->actingAs($user)
            ->withSession([
                'loginFromSocialite'  => true,
                'socialiteAvatar'     => $remotePath,
            ])
            ->postJson('v3/user/info/auto-complete', [
                'nickName'  => 'Martin',
            ]);
        $response->assertJson(['code' => 0]);
        $this->assertDatabaseHas('user_profiles', [
            'user_id'   => $user->id,
            'nickname'  => 'Martin',
            'is_new'    => false,
        ], 'mongodb');
        $this->assertDatabaseHas('user_images', [
            'user_id'   => $user->id,
            'uri'       => sprintf('pizzas/images/%s/0305b27e047a11e7ac640800276e6868', $user->id),
            'is_avatar' => \SingPlus\Domains\Users\Models\UserImage::AVATAR_YES,
            'display_order' => 100,
        ], 'mongodb');

        self::assertFalse(file_exists($tmpPath));    // check tmp path aready deleted
  }

    public function testAutoCompleteUserProfileSuccess()
    {
        $user = factory(\SingPlus\Domains\Users\Models\User::class)->create();

        $counterMock = Mockery::mock(\Illuminate\Contracts\Cache\Store::class);
        $counterMock->shouldReceive('increment')
            ->once()
            ->with('user_images', 100)
            ->andReturn(100);
        Cache::shouldReceive('driver')
            ->once()
            ->with('counter')
            ->andReturn($counterMock);

        $tmpPath = storage_path(sprintf('app/tmp/avatar/%s', $user->id));
        $storageService = $this->mockStorage();
        $storageService->shouldReceive('store')
            ->once()
            ->with($tmpPath, Mockery::on(function (array $options) use ($user) {
                return isset($options['prefix']) &&
                    $options['prefix'] == sprintf('pizzas/images-origin/%s', $user->id);
            }))
            ->andReturn(sprintf('pizzas/images/%s/0305b27e047a11e7ac640800276e6868', $user->id));

        $storageService->shouldReceive('toHttpUrl')
            ->once()
            ->with(config('image.default_avatar'))
            ->andReturn(sprintf('%s/test_datas/tesla_logo.jpg', __DIR__));

        $response = $this->actingAs($user)
            ->postJson('v3/user/info/auto-complete', [
                'nickName'  => 'Martin',
            ]);

        $response->assertJson(['code' => 0]);
        $this->assertDatabaseHas('user_profiles', [
            'user_id'   => $user->id,
            'nickname'  => 'Martin',
            'is_new'    => false,
        ], 'mongodb');
        $this->assertDatabaseHas('user_images', [
            'user_id'       => $user->id,
            'is_avatar' => \SingPlus\Domains\Users\Models\UserImage::AVATAR_YES,
        ], 'mongodb');

        $profile = \SingPlus\Domains\Users\Models\UserProfile::where('user_id', $user->id)->first();
        self::assertFalse(file_exists($tmpPath));
        self::assertEquals(sprintf('pizzas/images/%s/0305b27e047a11e7ac640800276e6868', $user->id),$profile->avatar);
    }


    //=================================
  //        bindMobile
  //=================================
  public function testBindMobileSuccess()
  {
    $verification = factory(\SingPlus\Domains\Verifications\Models\Verification::class)->create([
      'mobile'      => '2547200000001',
      'code'        => '1234',
      'expired_at'  => date('Y-m-d H:i:s', strtotime('+50 seconds')),
    ]);

    $user = factory(\SingPlus\Domains\Users\Models\User::class)->create([
      'country_code'  => null,
      'mobile'        => null, 
    ]);
    factory(\SingPlus\Domains\Users\Models\UserProfile::class)->create([
      'user_id' => $user->id, 
    ]);

    $response = $this->actingAs($user)
                     ->postJson('v3/user/mobile/bind', [
                        'countryCode' => '254',
                        'mobile'      => '7200000001',
                        'code'        => '1234',
                     ]);
    $response->assertJson(['code' => 0]);

    $this->assertDatabaseHas('users', [
      '_id'            => $user->id,
      'country_code'  => 254,               // country_code & mobile aready changed
      'mobile'        => '2547200000001',
    ]);
  }

  public function testBindMobileFailed_UserAreadyBound()
  {
    $user = factory(\SingPlus\Domains\Users\Models\User::class)->create([
      'country_code'  => 254,
      'mobile'        => '2547200000001', 
    ]);
    factory(\SingPlus\Domains\Users\Models\UserProfile::class)->create([
      'user_id' => $user->id, 
    ]);

    $response = $this->actingAs($user)
                     ->postJson('v3/user/mobile/bind', [
                        'countryCode' => '254',
                        'mobile'      => '7200000002',
                        'code'        => '1234',
                     ]);
    $response->assertJson(['code' => 10105]);
  }

  public function testBindMobileFailed_MobileAreadyBound()
  {
    $user = factory(\SingPlus\Domains\Users\Models\User::class)->create([
      'country_code'  => null,
      'mobile'        => null,
    ]);
    factory(\SingPlus\Domains\Users\Models\UserProfile::class)->create([
      'user_id' => $user->id, 
    ]);

    factory(\SingPlus\Domains\Users\Models\User::class)->create([
      'country_code'  => 254,
      'mobile'        => '2547211111112',
    ]);
    $response = $this->actingAs($user)
                     ->postJson('v3/user/mobile/bind', [
                        'countryCode' => '254',
                        'mobile'      => '7211111112',
                        'code'        => '1234',
                     ]);
    $response->assertJson(['code' => 10102]);
  }

  //=================================
  //        rebindMobile
  //=================================
  public function testRebindMobileSuccess()
  {
    factory(\SingPlus\Domains\Verifications\Models\Verification::class)->create([
      'mobile'      => '2547200000001',
      'code'        => '1234',
      'expired_at'  => date('Y-m-d H:i:s', strtotime('+50 seconds')),
    ]);
    factory(\SingPlus\Domains\Verifications\Models\Verification::class)->create([
      'mobile'      => '2537200000002',
      'code'        => '2345',
      'expired_at'  => date('Y-m-d H:i:s', strtotime('+50 seconds')),
    ]);
    $user = factory(\SingPlus\Domains\Users\Models\User::class)->create([
      'country_code'  => 254,
      'mobile'        => '2547200000001',
    ]);
    factory(\SingPlus\Domains\Users\Models\UserProfile::class)->create([
      'user_id' => $user->id, 
    ]);

    $response = $this->actingAs($user)
                     ->postJson('v3/user/mobile/rebind', [
                        'countryCode'   => 253,
                        'mobile'        => '7200000002',
                        'unbindCode'    => '1234',
                        'rebindCode'    => '2345',
                     ]);
    $response->assertJson(['code' => 0]);

    $this->assertDatabaseHas('users', [
      'country_code'  => 253,                 // country_code & mobile aready changed
      'mobile'        => '2537200000002',
    ]);
  }

  public function testRebindMobileFailed_UserNotBoundMobile()
  {
    $user = factory(\SingPlus\Domains\Users\Models\User::class)->create([
      'country_code'  => null,
      'mobile'        => null,
    ]);
    factory(\SingPlus\Domains\Users\Models\UserProfile::class)->create([
      'user_id' => $user->id, 
    ]);

    $response = $this->actingAs($user)
                     ->postJson('v3/user/mobile/rebind', [
                        'countryCode'   => 254,
                        'mobile'        => '7200000002',
                        'unbindCode'    => '1234',
                        'rebindCode'    => '2345',
                     ]);
    $response->assertJson(['code' => 10106]);
  }

  public function testRebindMobileFailed_MobileAreadyBound()
  {
    factory(\SingPlus\Domains\Users\Models\User::class)->create([
      'country_code'  => 254,
      'mobile'        => '2547200000002',
    ]);
    $user = factory(\SingPlus\Domains\Users\Models\User::class)->create([
      'country_code'  => 254,
      'mobile'        => '2547200000001',
    ]);
    factory(\SingPlus\Domains\Users\Models\UserProfile::class)->create([
      'user_id' => $user->id, 
    ]);

    $response = $this->actingAs($user)
                     ->postJson('v3/user/mobile/rebind', [
                        'countryCode'   => 254,
                        'mobile'        => '7200000002',
                        'unbindCode'    => '1234',
                        'rebindCode'    => '2345',
                     ]);
    $response->assertJson(['code' => 10105]);
  }

  public function testRebindMobileFailed_ReboundExistsMobile()
  {
    $user = factory(\SingPlus\Domains\Users\Models\User::class)->create([
      'country_code'  => 254,
      'mobile'        => '2547200000001',
    ]);
    factory(\SingPlus\Domains\Users\Models\UserProfile::class)->create([
      'user_id' => $user->id, 
    ]);

    $response = $this->actingAs($user)
                     ->postJson('v3/user/mobile/rebind', [
                        'countryCode'   => 254,
                        'mobile'        => '7200000001',
                        'unbindCode'    => '1234',
                        'rebindCode'    => '2345',
                     ]);
    $response->assertJson(['code' => 10000]);
  }

  public function testRebindMobileFailed_UnbindCodeNotMatch()
  {
    factory(\SingPlus\Domains\Verifications\Models\Verification::class)->create([
      'mobile'      => '2547200000002',
      'code'        => '2345',
      'expired_at'  => date('Y-m-d H:i:s', strtotime('+50 seconds')),
    ]);
    $user = factory(\SingPlus\Domains\Users\Models\User::class)->create([
      'country_code'  => 254,
      'mobile'        => '2547200000001',
    ]);
    factory(\SingPlus\Domains\Users\Models\UserProfile::class)->create([
      'user_id' => $user->id, 
    ]);

    $response = $this->actingAs($user)
                     ->postJson('v3/user/mobile/rebind', [
                        'countryCode'   => 254,
                        'mobile'        => '7200000002',
                        'unbindCode'    => '1234',
                        'rebindCode'    => '2345',
                     ]);
    $response->assertJson(['code' => 10002]);
  }

  public function testRebindMobileFailed_RebindCodeNotMatch()
  {
    factory(\SingPlus\Domains\Verifications\Models\Verification::class)->create([
      'mobile'      => '2547200000001',
      'code'        => '1234',
      'expired_at'  => date('Y-m-d H:i:s', strtotime('+50 seconds')),
    ]);
    factory(\SingPlus\Domains\Verifications\Models\Verification::class)->create([
      'mobile'      => '2547200000002',
      'code'        => '3333',
      'expired_at'  => date('Y-m-d H:i:s', strtotime('+50 seconds')),
    ]);
    $user = factory(\SingPlus\Domains\Users\Models\User::class)->create([
      'country_code'  => 254,
      'mobile'        => '2547200000001',
    ]);
    factory(\SingPlus\Domains\Users\Models\UserProfile::class)->create([
      'user_id' => $user->id, 
    ]);

    $response = $this->actingAs($user)
                     ->postJson('v3/user/mobile/rebind', [
                        'countryCode'   => 254,
                        'mobile'        => '7200000002',
                        'unbindCode'    => '1234',
                        'rebindCode'    => '2345',
                     ]);
    $response->assertJson(['code' => 10002]);
  }

  //=================================
  //        resetPassword
  //=================================
  public function testResetPasswordSuccess()
  {
    factory(\SingPlus\Domains\Verifications\Models\Verification::class)->create([
      'mobile'      => '2547200000001',
      'code'        => '1234',
      'expired_at'  => date('Y-m-d H:i:s', strtotime('+50 seconds')),
    ]);

    $user = factory(\SingPlus\Domains\Users\Models\User::class)->create([
      'country_code'  => 254,
      'mobile'        => '2547200000001',
      'password'      => '$2y$10$IYcxJ287/u/hsapWmT/TEeHwpCk7CFoKYe99LgZMoL1mxTzVElQSW', // raw password is: '*******'
    ]);

    $response = $this->postJson('v3/user/password/reset', [
                  'countryCode' => 254,
                  'mobile'      => '7200000001',
                  'password'    => '111111',
                  'code'        => '1234',
                ]);
    $response->assertJson(['code' => 0]);

    // user login wich new password success
    $this->postJson('v3/passport/login', [
      'countryCode'   => '254',
      'mobile'        => '7200000001',
      'password'      => '111111',
    ])->assertJson(['code' => 0]);
  }

  public function testResetPasswordFailed_UserNotExists()
  {
    $response = $this->postJson('v3/user/password/reset', [
                  'countryCode' => 254,
                  'mobile'      => '7200000001',
                  'password'    => '111111',
                  'code'        => '1234',
                ]);
    $response->assertJson(['code' => 10103]);
  }

  public function testResetPasswordFailed_VerifyCodeNotMatch()
  {
    $user = factory(\SingPlus\Domains\Users\Models\User::class)->create([
      'country_code'  => 254,
      'mobile'        => '2547200000001',
      'password'      => '$2y$10$IYcxJ287/u/hsapWmT/TEeHwpCk7CFoKYe99LgZMoL1mxTzVElQSW', // raw password is: '*******'
    ]);

    $response = $this->postJson('v3/user/password/reset', [
                  'countryCode' => 254,
                  'mobile'      => '7200000001',
                  'password'    => '111111',
                  'code'        => '1234',
                ]);
    $response->assertJson(['code' => 10002]);
  }

  //=================================
  //        renewMobile
  //=================================
  public function testRenewMobileSuccess()
  {
    $user = factory(\SingPlus\Domains\Users\Models\User::class)->create([
      'mobile'  => '8613800138000',
    ]);
    factory(\SingPlus\Domains\Users\Models\User::class)->create([
      'mobile'  => null,
    ]);

    $response = $this->get('v3/mobile/renew?mobile=' . '13800138000');
    $response->assertJson(['code' => 0]);

    $this->assertDatabaseHas('users', [
      '_id'     => $user->id,
      'mobile'  => null,
    ]);
  }

  //=================================
  //        reportLocation
  //=================================
  public function testReportLocationSuccess()
  {
    $user = factory(\SingPlus\Domains\Users\Models\User::class)->create([
      'mobile'  => '8613800138000',
    ]);
    factory(\SingPlus\Domains\Users\Models\UserProfile::class)->create([
      'user_id' => $user->id, 
    ]);

    $response = $this->actingAs($user)
                     ->postJson('v3/user/location', [
                        'longitude'     => '104.06',
                        'latitude'      => '30.67',
                        'countryCode'   => '86',
                        'abbreviation'  => 'br',
                     ])
                     ->assertJson(['code' => 0]);

    $profile = \SingPlus\Domains\Users\Models\UserProfile::where('user_id', $user->id)->first();
    self::assertEquals('104.06', $profile->location['longitude']);
    self::assertEquals('30.67', $profile->location['latitude']);
    self::assertEquals('86', $profile->location['country_code']);
    self::assertEquals('br', $profile->location['abbreviation']);
  }

  public function testReportLocationSuccess_OverrideExistsLocation()
  {
    $user = factory(\SingPlus\Domains\Users\Models\User::class)->create([
      'mobile'  => '8613800138000',
    ]);
    factory(\SingPlus\Domains\Users\Models\UserProfile::class)->create([
      'user_id'   => $user->id, 
      'location'  => [
        'longitude'     => '123.12',
        'latitude'      => '22.33',
        'country_code'  => null,
      ],
    ]);

    $response = $this->actingAs($user)
                     ->postJson('v3/user/location', [
                        'longitude'   => '104.06',
                        'latitude'    => '30.67',
                        'countryCode' => '86',
                        'abbreviation'  => 'IN',
                     ])
                     ->assertJson(['code' => 0]);

    $profile = \SingPlus\Domains\Users\Models\UserProfile::where('user_id', $user->id)->first();
    self::assertEquals('104.06', $profile->location['longitude']);
    self::assertEquals('30.67', $profile->location['latitude']);
    self::assertEquals('86', $profile->location['country_code']);
    self::assertEquals('IN', $profile->location['abbreviation']); // abbreviation not changed
  }

  public function testReportLocationSuccess_OverrideExistsLocation_AbbreationIgnore()
  {
    $user = factory(\SingPlus\Domains\Users\Models\User::class)->create([
      'mobile'  => '8613800138000',
    ]);
    factory(\SingPlus\Domains\Users\Models\UserProfile::class)->create([
      'user_id'   => $user->id, 
      'location'  => [
        'longitude'     => '123.12',
        'latitude'      => '22.33',
        'country_code'  => null,
        'abbreviation'  => 'CA',
      ],
    ]);

    $response = $this->actingAs($user)
                     ->postJson('v3/user/location', [
                        'longitude'     => '104.06',
                        'latitude'      => '30.67',
                        'countryCode'   => '86',
                        'abbreviation'  => 'IN',
                     ])
                     ->assertJson(['code' => 0]);

    $profile = \SingPlus\Domains\Users\Models\UserProfile::where('user_id', $user->id)->first();
    self::assertEquals('104.06', $profile->location['longitude']);
    self::assertEquals('30.67', $profile->location['latitude']);
    self::assertEquals('86', $profile->location['country_code']);
    self::assertEquals('CA', $profile->location['abbreviation']); // abbreviation not changed
  }

  public function testReportLocationSuccess_CountryCodeMissed()
  {
    $user = factory(\SingPlus\Domains\Users\Models\User::class)->create([
      'mobile'  => '8613800138000',
    ]);
    factory(\SingPlus\Domains\Users\Models\UserProfile::class)->create([
      'user_id' => $user->id, 
    ]);

    $response = $this->actingAs($user)
                     ->postJson('v3/user/location', [
                        'longitude'   => '104.06',
                        'latitude'    => '30.67',
                     ])
                     ->assertJson(['code' => 0]);

    $profile = \SingPlus\Domains\Users\Models\UserProfile::where('user_id', $user->id)->first();
    self::assertEquals('104.06', $profile->location['longitude']);
    self::assertEquals('30.67', $profile->location['latitude']);
    self::assertNull($profile->location['country_code']);
  }

  public function testReportLoactionSuccess_LongtitudeMissed(){
      $user = factory(\SingPlus\Domains\Users\Models\User::class)->create([
          'mobile'  => '8613800138000',
      ]);
      factory(\SingPlus\Domains\Users\Models\UserProfile::class)->create([
          'user_id'   => $user->id,
          'location'  => [
              'longitude'     => '123.12',
              'latitude'      => '22.33',
              'country_code'  => null,
          ],
      ]);

      $response = $this->actingAs($user)
          ->postJson('v3/user/location', [
              'countryCode' => '86',
              'abbreviation'  => 'IN',
          ])
          ->assertJson(['code' => 0]);
      $profile = \SingPlus\Domains\Users\Models\UserProfile::where('user_id', $user->id)->first();
      self::assertEquals(null, $profile->location['longitude']);
      self::assertEquals(null, $profile->location['latitude']);
      self::assertEquals('86', $profile->location['country_code']);
      self::assertEquals('IN', $profile->location['abbreviation']);
  }

    public function testReportLoactionFailed_AutoReportWhenModifiedByUser(){
        $user = factory(\SingPlus\Domains\Users\Models\User::class)->create([
            'mobile'  => '8613800138000',
        ]);
        factory(\SingPlus\Domains\Users\Models\UserProfile::class)->create([
            'user_id'   => $user->id,
            'location'  => [
                'longitude'     => '123.12',
                'latitude'      => '22.33',
                'country_code'  => null,
                'modified_by_user' => true,
            ],
        ]);

        $response = $this->actingAs($user)
            ->postJson('v3/user/location', [
                'countryCode' => '86',
                'abbreviation'  => 'IN',
            ])
            ->assertJson(['code' => 0]);

        $this->assertDatabaseHas('user_profiles', [
            'user_id' => $user->id,
            'location' => [
                'longitude' => '123.12',
                'latitude'  => '22.33',
                'country_code'  => null,
                'modified_by_user' => true,
            ]
        ]);
    }

    public function testReportLoactionSuccess_ReportByUser(){
        $user = factory(\SingPlus\Domains\Users\Models\User::class)->create([
            'mobile'  => '8613800138000',
        ]);
        factory(\SingPlus\Domains\Users\Models\UserProfile::class)->create([
            'user_id'   => $user->id,
            'location'  => [
                'longitude'     => '123.12',
                'latitude'      => '22.33',
                'country_code'  => null,
                'modified_by_user' => true,
            ],
        ]);

        $response = $this->actingAs($user)
            ->postJson('v3/user/location', [
                'countryCode' => '86',
                'abbreviation'  => 'IN',
                'city' => 'ChengDu',
                'auto'  => 0,
            ])
            ->assertJson(['code' => 0]);

        $profile = \SingPlus\Domains\Users\Models\UserProfile::where('user_id', $user->id)->first();
        self::assertEquals(null, $profile->location['longitude']);
        self::assertEquals(null, $profile->location['latitude']);
        self::assertEquals('86', $profile->location['country_code']);
        self::assertEquals('ChengDu', $profile->location['city']);
        self::assertEquals(true, $profile->location['modified_by_user']);
        self::assertEquals('IN', $profile->location['abbreviation']);
    }

    public function testReportLoactionSuccess_ReportByUserWithCountryName(){
        $user = factory(\SingPlus\Domains\Users\Models\User::class)->create([
            'mobile'  => '8613800138000',
        ]);
        factory(\SingPlus\Domains\Users\Models\UserProfile::class)->create([
            'user_id'   => $user->id,
            'location'  => [
                'longitude'     => '123.12',
                'latitude'      => '22.33',
                'country_code'  => null,
                'modified_by_user' => true,
            ],
        ]);

        $response = $this->actingAs($user)
            ->postJson('v3/user/location', [
                'countryCode' => '86',
                'abbreviation'  => 'IN',
                'city' => 'ChengDu',
                'auto'  => 0,
                'countryName' => '中国'
            ])
            ->assertJson(['code' => 0]);
        $profile = \SingPlus\Domains\Users\Models\UserProfile::where('user_id', $user->id)->first();
        self::assertEquals(null, $profile->location['longitude']);
        self::assertEquals(null, $profile->location['latitude']);
        self::assertEquals('86', $profile->location['country_code']);
        self::assertEquals('ChengDu', $profile->location['city']);
        self::assertEquals(true, $profile->location['modified_by_user']);
        self::assertEquals('IN', $profile->location['abbreviation']);
        self::assertEquals('中国', $profile->location['country_name']);
    }

  //=================================
  //        mobileUserSource
  //=================================
  public function testMobileUserSourceSuccess()
  {
    $user = factory(\SingPlus\Domains\Users\Models\User::class)->create([
      'country_code'  => 86,
      'mobile'        => '8613800138000',
      'source'        => 'tudc',
    ]);

    $response = $this->getJson('v3/user/mobile-source?' . http_build_query([
                              'countryCode' => '86',
                              'mobile'      => '13800138000',
                      ]))
                      ->assertJson([
                        'code'  => 0,
                        'data'  => [
                          'source'  => 'tudc',
                        ],
                      ]);
  }

  public function testMobileUserSourceSuccess_UserNotExists()
  {
    $response = $this->getJson('v3/user/mobile-source?' . http_build_query([
                              'countryCode' => '86',
                              'mobile'      => '13800138000',
                      ]))
                      ->assertJson([
                        'code'  => 0,
                        'data'  => [
                          'source'  => null,
                        ],
                      ]);
  }

  public function testMobileUserSourceSuccess_UserOtherSource()
  {
    $user = factory(\SingPlus\Domains\Users\Models\User::class)->create([
      'country_code'  => 86,
      'mobile'        => '8613800138000',
      'source'        => 'synthetic',
    ]);

    $response = $this->getJson('v3/user/mobile-source?' . http_build_query([
                              'countryCode' => '86',
                              'mobile'      => '13800138000',
                      ]))
                      ->assertJson([
                        'code'  => 0,
                        'data'  => [
                          'source'  => 'mobile',
                        ],
                      ]);
  }

    //=================================
    //        updateUserPreferenceConf
    //=================================
    public function testUpdatePrefConfSuccess(){

        $user = factory(\SingPlus\Domains\Users\Models\User::class)->create();
        $userProfile = factory(\SingPlus\Domains\Users\Models\UserProfile::class)->create([
            'user_id' => $user,
            'preferences_conf'  => [
                'notify_followed' => false,
                'notify_favourite' => false,
                'notify_comment'  => true,
                'notify_gift' => false,
                'notify_im_msg' => true,
                'privacy_unfollowed_msg' => false
            ]
        ]);

        $response = $this->actingAs($user)->postJson('v3/user/update-prefconf',[
                'prefName' => 'notifyFollowed',
                'value'      => 1,
            ])->assertJson([
                'code'  => 0,
            ]);
        self::assertDatabaseHas('user_profiles',[
            'user_id' => $user->id,
            'preferences_conf'  => [
                'notify_followed' => true,
                'notify_favourite' => false,
                'notify_comment'  => true,
                'notify_gift' => false,
                'notify_im_msg' => true,
                'privacy_unfollowed_msg' => false
            ]
        ]);
    }

    public function testUpdatePrefConfSuccess_WithoutPref(){
        $user = factory(\SingPlus\Domains\Users\Models\User::class)->create();
        $userProfile = factory(\SingPlus\Domains\Users\Models\UserProfile::class)->create([
            'user_id' => $user,
        ]);

        $response = $this->actingAs($user)->postJson('v3/user/update-prefconf',[
            'prefName' => 'notifyFollowed',
            'value'      => 0,
        ])->assertJson([
            'code'  => 0,
        ]);
        self::assertDatabaseHas('user_profiles',[
            'user_id' => $user->id,
            'preferences_conf'  => [
                'notify_followed' => false,
            ]
        ]);
    }

    public function testUpdatePrefConfFailed_InvalidPrefName(){
        $user = factory(\SingPlus\Domains\Users\Models\User::class)->create();
        $userProfile = factory(\SingPlus\Domains\Users\Models\UserProfile::class)->create([
            'user_id' => $user,
        ]);

        $response = $this->actingAs($user)->postJson('v3/user/update-prefconf',[
            'prefName' => 'notify_followed',
            'value'      => 0,
        ])->assertJson([
            'code'  => 0,
        ]);
        self::assertDatabaseMissing('user_profiles',[
            'user_id' => $user->id,
            'preferences_conf'  => [
                'notify_followed_ssa' => true,
            ]
        ]);
    }

  private function mockStorage()
  {
    $storageService = Mockery::mock(\SingPlus\Contracts\Storages\Services\StorageService::class);
    $this->app[\SingPlus\Contracts\Storages\Services\StorageService::class ] = $storageService;

    return $storageService;
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
