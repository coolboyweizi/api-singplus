<?php

namespace FeatureTest\SingPlus\Controllers\Auth;

use Mockery;
use Socialite;
use Queue;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\Event;
use FeatureTest\SingPlus\TestCase;
use FeatureTest\SingPlus\MongodbClearTrait;

class SocialiteControllerTest extends TestCase
{
  use MongodbClearTrait; 

  //=====================
  //      login
  //=====================
  public function testLoginSuccess_Register()
  {
    Queue::fake();
    $request = app(\Illuminate\Http\Request::class);
    $driver = 'facebook';
    $userAccessToken = 'EAACEdEose0cBAGUIgF4lJUz1Rw63uKfxou3XeR6xQl7u5D6pZBAFvMZADfsyWYpegRE0ob1z5Q2itq7YI1e1JJnXWzM5MZCJXTGqpFUzCugQYROPiZAWjEENmZBROetpupPk60VtYMFA3XzRcQfueLW51MQwZByJBnAAIbMx38glO6ZAQ0MSFi9Vo6bST35EBsZD';
    $mockSocialiteUser = $this->mockSocialiteUser(
      '10207665964194463', 'Martin', 'https://image.com', 'AbyDsccLpOKd2PGN'
    );
    $provider = $this->mockSocialiteProvider($driver);
    $provider->shouldReceive('userFromToken')
             ->once()
             ->with($userAccessToken)
             ->andReturn($mockSocialiteUser);

    $response = $this->postJson('v3/passport/socialite/facebook/login', [
                                  'userAccessToken' => $userAccessToken,
                                ]);
    $loginedUser = \Auth::guard()->user();

    $response->assertJson([
      'code'  => 0,
      'data'  => [
        'userId'    => $loginedUser->id,
        'isNewUser' => true,
        'nickname'  => 'Martin',
        'avatar'    => 'https://image.com',
      ],
    ]);
    $response->assertSessionHas('socialiteAvatar');

    Queue::assertNotPushed(\SingPlus\Jobs\FreshTUDCUser::class);
    Queue::assertNotPushed(\SingPlus\Jobs\SyncStaleSocialiteUserIntoChannel::class);

    self::assertNotNull($loginedUser);
    $userId = $loginedUser->id;
    $this->assertDatabaseHas('socialite_users', [
      'user_id'           => $userId,
      'provider'          => 'facebook',
      'union_token'       => 'AbyDsccLpOKd2PGN',
      'channels'          => [
        'singplus'  => [
          'openid'  => '10207665964194463',
          'token'   => $userAccessToken,
        ],
      ],
    ]);
    $this->assertDatabaseHas('users', [
      '_id'           => $userId,
      'country_code'  => '254',         // default country code applied
    ]);
  }

  public function testLoginSuccess_Register_UnionTokenExists()
  {
    Queue::fake();
    $user = factory(\SingPlus\Domains\Users\Models\User::class)->create();
    factory(\SingPlus\Domains\Users\Models\UserProfile::class)->create([
      'user_id' => $user->id, 
    ]);
    factory(\SingPlus\Domains\Users\Models\SocialiteUser::class)->create([
      'user_id'   => $user->id,  
      'provider'  => 'facebook',
      'channels'  => [
        'singplus'  => [
          'openid'  => '335796166893748',
          'token'   => 'EAAaFZBTlmuZCwBACboXZA2goAlAP4ZAlVVpE29EPMgIEVP0sZBxGnQa7mOkm5jsLIs7ToezpcqJn82JB0MYrQw6ZBM761kqYquDKZAmWwDuc5ZCVE8efYpjjUX797uZCXQxqyv1fxqG5jXfuImOkyEFZCEkZCZCgh8RW0v7g5iqZBzlieY9MkDVpaV5HGEZCvoCvrDY25qPt2982So6DiL7A7Oimif2hHkpjfuh1UxRZCMLqurTPAZDZD',
        ],
      ],
      'union_token' => 'AbyDsccLpOKd2PGN',
    ]);

    $request = app(\Illuminate\Http\Request::class);
    $driver = 'facebook_boomsing';
    $userAccessToken = 'EAACEdEose0cBAGUIgF4lJUz1Rw63uKfxou3XeR6xQl7u5D6pZBAFvMZADfsyWYpegRE0ob1z5Q2itq7YI1e1JJnXWzM5MZCJXTGqpFUzCugQYROPiZAWjEENmZBROetpupPk60VtYMFA3XzRcQfueLW51MQwZByJBnAAIbMx38glO6ZAQ0MSFi9Vo6bST35EBsZD';
    $mockSocialiteUser = $this->mockSocialiteUser(
      '10207665964194463', 'Martin', 'https://image.com', 'AbyDsccLpOKd2PGN'
    );
    $provider = $this->mockSocialiteProvider($driver);
    $provider->shouldReceive('userFromToken')
             ->once()
             ->with($userAccessToken)
             ->andReturn($mockSocialiteUser);

    config([
      'tudc.currentChannel' => 'boomsing',
    ]);
    $response = $this->postJson('v3/passport/socialite/facebook/login', [
                                  'userAccessToken' => $userAccessToken,
                                ]);
    $loginedUser = \Auth::guard()->user();

    $response->assertJson([
      'code'  => 0,
      'data'  => [
        'userId'    => $loginedUser->id,
        'isNewUser' => false,
        'nickname'  => null,
        'avatar'    => null,
      ],
    ]);

    Queue::assertNotPushed(\SingPlus\Jobs\FreshTUDCUser::class);
    Queue::assertNotPushed(\SingPlus\Jobs\SyncStaleSocialiteUserIntoChannel::class);

    self::assertNotNull($loginedUser);
    $userId = $loginedUser->id;
    self::assertEquals($user->id, $userId);       // not new user be created
    $this->assertDatabaseHas('socialite_users', [
      'user_id'           => $userId,
      'provider'          => 'facebook',
      'union_token'       => 'AbyDsccLpOKd2PGN',
      'channels'          => [
        'singplus'  => [
          'openid'  => '335796166893748',
          'token'   => 'EAAaFZBTlmuZCwBACboXZA2goAlAP4ZAlVVpE29EPMgIEVP0sZBxGnQa7mOkm5jsLIs7ToezpcqJn82JB0MYrQw6ZBM761kqYquDKZAmWwDuc5ZCVE8efYpjjUX797uZCXQxqyv1fxqG5jXfuImOkyEFZCEkZCZCgh8RW0v7g5iqZBzlieY9MkDVpaV5HGEZCvoCvrDY25qPt2982So6DiL7A7Oimif2hHkpjfuh1UxRZCMLqurTPAZDZD',
        ],
        'boomsing'  => [
          'openid'  => '10207665964194463',
          'token'   => $userAccessToken,
        ],
      ],
    ]);
  }

  public function testLoginSuccess_RegisterAndNicknameAreadyBeUsed()
  {
    Queue::fake();
    factory(\SingPlus\Domains\Users\Models\UserProfile::class)->create([
      'nickname'  => 'Martin',
    ]);

    $request = app(\Illuminate\Http\Request::class);
    $driver = 'facebook';
    $userAccessToken = 'EAACEdEose0cBAGUIgF4lJUz1Rw63uKfxou3XeR6xQl7u5D6pZBAFvMZADfsyWYpegRE0ob1z5Q2itq7YI1e1JJnXWzM5MZCJXTGqpFUzCugQYROPiZAWjEENmZBROetpupPk60VtYMFA3XzRcQfueLW51MQwZByJBnAAIbMx38glO6ZAQ0MSFi9Vo6bST35EBsZD';
    $mockSocialiteUser = $this->mockSocialiteUser(
      '10207665964194463', 'Martin', 'https://image.com', 'AbyDsccLpOKd2PGN'
    );
    $provider = $this->mockSocialiteProvider($driver);
    $provider->shouldReceive('userFromToken')
             ->once()
             ->with($userAccessToken)
             ->andReturn($mockSocialiteUser);

    $response = $this->postJson('v3/passport/socialite/facebook/login', [
                                  'userAccessToken' => $userAccessToken,
                                ]);
    $loginedUser = \Auth::guard()->user();
    $response->assertJson([
      'code'  => 0,
      'data'  => [
        'userId'    => $loginedUser->id,
        'isNewUser' => true,
        'avatar'    => 'https://image.com',
      ],
    ]);
    $response->assertSessionHas('socialiteAvatar');
    Queue::assertNotPushed(\SingPlus\Jobs\FreshTUDCUser::class);
    Queue::assertNotPushed(\SingPlus\Jobs\SyncStaleSocialiteUserIntoChannel::class);

    // Martin aready exists, so check suffix
    $resNickname = (json_decode($response->getContent()))->data->nickname;
    self::assertTrue(starts_with($resNickname, 'Martin'));
    self::assertEquals(strlen('Martin') + 3, strlen($resNickname));

    self::assertNotNull($loginedUser);
    $userId = $loginedUser->id;
    $this->assertDatabaseHas('socialite_users', [
      'user_id'           => $userId,
      'provider'          => 'facebook',
      'channels'          => [
        'singplus'  => [
          'openid'  => '10207665964194463',
          'token'   => $userAccessToken,
        ],
      ],
      'union_token'       => 'AbyDsccLpOKd2PGN',
    ]);
  }

  public function testLoginSuccess_UserExists_CompatibleForOldData()
  {
    Queue::fake();
    $user = factory(\SingPlus\Domains\Users\Models\User::class)->create([
      'country_code'  => null,
      'mobile'        => null,
    ]);
    
    $driver = 'facebook';
    $userAccessToken = 'EAACEdEose0cBAGUIgF4lJUz1Rw63uKfxou3XeR6xQl7u5D6pZBAFvMZADfsyWYpegRE0ob1z5Q2itq7YI1e1JJnXWzM5MZCJXTGqpFUzCugQYROPiZAWjEENmZBROetpupPk60VtYMFA3XzRcQfueLW51MQwZByJBnAAIbMx38glO6ZAQ0MSFi9Vo6bST35EBsZD';
    $mockSocialiteUser = $this->mockSocialiteUser(
      '10207665964194463', 'Martin', 'https://image.com', 'AbyDsccLpOKd2PGN'
    );
    $provider = $this->mockSocialiteProvider($driver);

    $provider->shouldReceive('userFromToken')
             ->once()
             ->with($userAccessToken)
             ->andReturn($mockSocialiteUser);
    
    $socialiteUser = factory(\SingPlus\Domains\Users\Models\SocialiteUser::class)->create([
      'user_id'           => $user->id,
      'socialite_user_id' => '10207665964194463',
      'provider'          => 'facebook',
    ]);

    $response = $this->postJson('v3/passport/socialite/facebook/login', [
                                  'userAccessToken' => $userAccessToken,
                                ]);
    $loginedUser = \Auth::guard()->user();
    $response->assertJson([
      'code'  => 0,
      'data'  => [
        'userId'    => $loginedUser->id,
        'isNewUser' => true,
        'nickname'  => 'Martin',
        'avatar'    => 'https://image.com',
      ]
    ]);
    Queue::assertNotPushed(\SingPlus\Jobs\FreshTUDCUser::class);
    Queue::assertPushed(
      \SingPlus\Jobs\SyncStaleSocialiteUserIntoChannel::class,
      function ($job) use ($loginedUser, $userAccessToken) {
        return $job->userId == $loginedUser->id &&
               $job->socialiteUserId == '10207665964194463' &&
               $job->userAccessToken == $userAccessToken &&
               $job->unionToken == 'AbyDsccLpOKd2PGN';
      });

    self::assertNotNull($loginedUser);
    self::assertEquals($user->id, $loginedUser->id);
  }

  public function testLoginSuccess_UserExists()
  {
    Queue::fake();
    $user = factory(\SingPlus\Domains\Users\Models\User::class)->create([
      'country_code'  => null,
      'mobile'        => null,
    ]);
    
    $driver = 'facebook';
    $userAccessToken = 'EAACEdEose0cBAGUIgF4lJUz1Rw63uKfxou3XeR6xQl7u5D6pZBAFvMZADfsyWYpegRE0ob1z5Q2itq7YI1e1JJnXWzM5MZCJXTGqpFUzCugQYROPiZAWjEENmZBROetpupPk60VtYMFA3XzRcQfueLW51MQwZByJBnAAIbMx38glO6ZAQ0MSFi9Vo6bST35EBsZD';
    $mockSocialiteUser = $this->mockSocialiteUser(
      '10207665964194463', 'Martin', 'https://image.com', 'AbyDsccLpOKd2PGN'
    );
    $provider = $this->mockSocialiteProvider($driver);

    $provider->shouldReceive('userFromToken')
             ->once()
             ->with($userAccessToken)
             ->andReturn($mockSocialiteUser);
    
    $socialiteUser = factory(\SingPlus\Domains\Users\Models\SocialiteUser::class)->create([
      'user_id'           => $user->id,
      'provider'          => 'facebook',
      'channels'          => [
        'singplus'  => [
          'openid'  => '10207665964194463',
          'token'   => $userAccessToken,
        ],
      ],
    ]);

    $response = $this->postJson('v3/passport/socialite/facebook/login', [
                                  'userAccessToken' => $userAccessToken,
                                ]);
    $loginedUser = \Auth::guard()->user();
    $response->assertJson([
      'code'  => 0,
      'data'  => [
        'userId'    => $loginedUser->id,
        'isNewUser' => true,
        'nickname'  => 'Martin',
        'avatar'    => 'https://image.com',
      ]
    ]);
    Queue::assertNotPushed(\SingPlus\Jobs\FreshTUDCUser::class);
    Queue::assertNotPushed(\SingPlus\Jobs\SyncStaleSocialiteUserIntoChannel::class);
    self::assertNotNull($loginedUser);
    self::assertEquals($user->id, $loginedUser->id);
  }

  public function testLoginSuccess_UserExistsAndProfileComplete()
  {
    Queue::fake();
    $user = factory(\SingPlus\Domains\Users\Models\User::class)->create([
      'country_code'  => null,
      'mobile'        => null,
    ]);
    factory(\SingPlus\Domains\Users\Models\UserProfile::class)->create([
      'user_id' => $user->id,
      'is_new'  => false,
    ]);
    
    $driver = 'facebook';
    $userAccessToken = 'EAACEdEose0cBAGUIgF4lJUz1Rw63uKfxou3XeR6xQl7u5D6pZBAFvMZADfsyWYpegRE0ob1z5Q2itq7YI1e1JJnXWzM5MZCJXTGqpFUzCugQYROPiZAWjEENmZBROetpupPk60VtYMFA3XzRcQfueLW51MQwZByJBnAAIbMx38glO6ZAQ0MSFi9Vo6bST35EBsZD';
    $mockSocialiteUser = $this->mockSocialiteUser(
      '10207665964194463', 'Martin', 'https://image.com', 'AbyDsccLpOKd2PGN'
    );
    $provider = $this->mockSocialiteProvider($driver);

    $provider->shouldReceive('userFromToken')
             ->once()
             ->with($userAccessToken)
             ->andReturn($mockSocialiteUser);
    
    $socialiteUser = factory(\SingPlus\Domains\Users\Models\SocialiteUser::class)->create([
      'user_id'           => $user->id,
      'socialite_user_id' => '10207665964194463',
      'provider'          => 'facebook',
      'channels'          => null,
    ]);

    $response = $this->postJson('v3/passport/socialite/facebook/login', [
                                  'userAccessToken' => $userAccessToken,
                                ]);
    $response->assertJson([
      'code'  => 0,
      'data'  => [
        'isNewUser' => false,
        'nickname'  => null,
        'avatar'    => null,
      ]
    ]);
    $loginedUser = \Auth::guard()->user();
    Queue::assertPushed(
      \SingPlus\Jobs\SyncStaleSocialiteUserIntoChannel::class,
      function ($job) use ($loginedUser, $userAccessToken) {
        return $job->userId == $loginedUser->id &&
               $job->socialiteUserId == '10207665964194463' &&
               $job->userAccessToken == $userAccessToken &&
               $job->unionToken == 'AbyDsccLpOKd2PGN';
      });
    self::assertNotNull($loginedUser);
    self::assertEquals($user->id, $loginedUser->id);
  }

  public function testLoginSuccess_SocialiteUserNotExists()
  {
    $this->doesntExpectEvents(\SingPlus\Events\UserRegistered::class);
    $driver = 'facebook';
    $userAccessToken = 'EAACEdEose0cBAGUIgF4lJUz1Rw63uKfxou3XeR6xQl7u5D6pZBAFvMZADfsyWYpegRE0ob1z5Q2itq7YI1e1JJnXWzM5MZCJXTGqpFUzCugQYROPiZAWjEENmZBROetpupPk60VtYMFA3XzRcQfueLW51MQwZByJBnAAIbMx38glO6ZAQ0MSFi9Vo6bST35EBsZD';
    $mockSocialiteUser = $this->mockSocialiteUser(null, null, null, null);
    $provider = $this->mockSocialiteProvider($driver);
    $provider->shouldReceive('userFromToken')
             ->once()
             ->with($userAccessToken)
             ->andReturn($mockSocialiteUser);
    $response = $this->postJson('v3/passport/socialite/facebook/login', [
                                  'userAccessToken' => $userAccessToken,
                                ]);
    $response->assertJson(['code' => '10120']);
    self::assertTrue(\Auth::guest());   // not login
  }

  public function testLoginSuccess_WithTUDCTicket()
  {
    Queue::fake();
    $request = app(\Illuminate\Http\Request::class);
    $driver = 'facebook';
    $userAccessToken = 'EAACEdEose0cBAGUIgF4lJUz1Rw63uKfxou3XeR6xQl7u5D6pZBAFvMZADfsyWYpegRE0ob1z5Q2itq7YI1e1JJnXWzM5MZCJXTGqpFUzCugQYROPiZAWjEENmZBROetpupPk60VtYMFA3XzRcQfueLW51MQwZByJBnAAIbMx38glO6ZAQ0MSFi9Vo6bST35EBsZD';
    $mockSocialiteUser = $this->mockSocialiteUser(
      '10207665964194463', 'Martin', 'https://image.com', 'AbyDsccLpOKd2PGN'
    );
    $provider = $this->mockSocialiteProvider($driver);
    $provider->shouldReceive('userFromToken')
             ->once()
             ->with($userAccessToken)
             ->andReturn($mockSocialiteUser);

    $response = $this->postJson('v3/passport/socialite/facebook/login', [
                                  'userAccessToken' => $userAccessToken,
                                  'tudcTicket'      => 'bdba74c6249549d7afa03fbdae99ddc1',
                                ]);
    $loginedUser = \Auth::guard()->user();

    $response->assertJson([
      'code'  => 0,
      'data'  => [
        'userId'    => $loginedUser->id,
        'isNewUser' => true,
        'nickname'  => 'Martin',
        'avatar'    => 'https://image.com',
      ],
    ]);
    $response->assertSessionHas('socialiteAvatar');

    Queue::assertPushed(
      \SingPlus\Jobs\FreshTUDCUser::class,
      function ($job) use ($loginedUser) {
        return $job->userId == $loginedUser->id &&
               $job->tudcTicket == 'bdba74c6249549d7afa03fbdae99ddc1' &&
               $job->appChannel == 'singplus';
      });

    self::assertNotNull($loginedUser);
    $userId = $loginedUser->id;
    $this->assertDatabaseHas('socialite_users', [
      'user_id'           => $userId,
      'provider'          => 'facebook',
      'channels'          => [
        'singplus'  => [
          'openid'  => '10207665964194463',
          'token'   => $userAccessToken,
        ],
      ],
      'union_token' => 'AbyDsccLpOKd2PGN',
    ]);
    $this->assertDatabaseHas('users', [
      '_id'           => $userId,
      'country_code'  => '254',         // default country code applied
    ]);
  }

  private function mockSocialiteProvider(string $driver)
  {
    $socialiteProviders = [
      'facebook'          => Mockery::mock(\SingPlus\Support\Socialite\Two\FacebookProvider::class),
      'facebook_boomsing' => Mockery::mock(\SingPlus\Support\Socialite\Two\FacebookProvider::class),
    ];
    Socialite::shouldReceive('driver')
             ->once()
             ->with($driver)
             ->andReturn($socialiteProviders[$driver]);
    return $socialiteProviders[$driver];
  }

  private function mockSocialiteUser(
    ?string $socialiteUserId,
    ?string $nickname,
    ?string $avatar,
    ?string $unionToken
  ) {
    $socialiteUser = Mockery::mock();
    $socialiteUser->shouldReceive('getId')
                  ->andReturn($socialiteUserId);
    $socialiteUser->shouldReceive('getName')
                  ->andReturn($nickname);
    $socialiteUser->shouldReceive('getAvatar')
                  ->andReturn($avatar);
    if ($unionToken) {
      $socialiteUser->unionToken = $unionToken;
    }
    return $socialiteUser;
  }
}
