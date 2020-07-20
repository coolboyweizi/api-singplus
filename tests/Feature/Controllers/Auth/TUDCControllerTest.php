<?php 

namespace FeatureTest\SingPlus\Controllers\Auth;

use Auth;
use Mockery;
use Cache;
use GuzzleHttp\Client;
use GuzzleHttp\Handler\MockHandler;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Psr7\Response;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use FeatureTest\SingPlus\TestCase;
use FeatureTest\SingPlus\MongodbClearTrait;

class TUDCControllerTest extends TestCase
{
  use MongodbClearTrait; 

  //=====================
  //      login
  //=====================
  public function testLoginSuccess_Register()
  {
    $this->mockHttpClient(json_encode([
      'code'    => 0,
      'openid'  => '272e14cbc32a41e7a7d38f8958f78ac9',
      'token'   => '3a1391f1acf64d819f1a993fb7e25a1f',
    ]));
    config([
      'tudc.currentChannel' => 'boomsing',
    ]);
    $response = $this->postJson('v3/passport/tudc/login', [
                                  'countryCode' => '254',
                                  'mobile'      => '123456789',
                                  'password'    => '123456',
                                  'tudcTicket'  => '61d4121f8b2e48d7aa1095dec811b15b',
                                ]);
    $loginedUser = Auth::guard()->user();
    self::assertNotNull($loginedUser);
    $response->assertJson([
      'code'  => 0,
      'data'  => [
        'userId'      => $loginedUser->id,
        'isNewUser'   => true,
        'tudcOpenid'  => '272e14cbc32a41e7a7d38f8958f78ac9',
      ],
    ]);

    self::assertDatabaseHas('users', [
      '_id'           => $loginedUser->id,
      'country_code'  => '254',
      'mobile'        => '254123456789',
      'source'        => 'tudc',
    ]);

    self::assertDatabaseHas('tudc_users', [
      'user_id'       => $loginedUser->id,
      'channels'      => [
        'boomsing'  => [
          'openid'        => '272e14cbc32a41e7a7d38f8958f78ac9',
          'token'         => '3a1391f1acf64d819f1a993fb7e25a1f',
        ]
      ],
    ]);
  }

  public function testLoginSuccess_Register_SingPlusUserExists()
  {
    $user = factory(\SingPlus\Domains\Users\Models\User::class)->create([
      'country_code'  => '254',
      'mobile'        => '254123456789',
      'source'        => 'mobile',
    ]);
    factory(\SingPlus\Domains\Users\Models\UserProfile::class)->create([
      'user_id' => $user->id, 
    ]);
    factory(\SingPlus\Domains\Users\Models\TUDCUser::class)->create([
      'user_id'   => $user->id,
      'channels'  => [
        'boomsing'  => [
          'openid'  => '714263c5119e48018d47ed21aa3e6b1d',
          'token'   => '9a2e47596038446b8586f4d092da2cd2',
        ],
      ],
    ]);
    $this->mockHttpClient(json_encode([
      'code'    => 0,
      'openid'  => '272e14cbc32a41e7a7d38f8958f78ac9',
      'token'   => '3a1391f1acf64d819f1a993fb7e25a1f',
    ]));
    $response = $this->postJson('v3/passport/tudc/login', [
                                  'countryCode' => '254',
                                  'mobile'      => '123456789',
                                  'password'    => '123456',
                                  'tudcTicket'  => '61d4121f8b2e48d7aa1095dec811b15b',
                                ]);
    $loginedUser = Auth::guard()->user();
    self::assertEquals($user->id, $loginedUser->id);
    $response->assertJson([
      'code'  => 0,
      'data'  => [
        'userId'      => $user->id,
        'isNewUser'   => false,
        'tudcOpenid'  => '272e14cbc32a41e7a7d38f8958f78ac9',
      ],
    ]);

    self::assertDatabaseHas('users', [
      '_id'           => $loginedUser->id,
      'country_code'  => '254',
      'mobile'        => '254123456789',
      'source'        => 'mobile',          // source not changed
    ]);

    self::assertDatabaseHas('tudc_users', [
      'user_id'   => $loginedUser->id,
      'channels'  => [
        'boomsing'  => [
          'openid'  => '714263c5119e48018d47ed21aa3e6b1d',
          'token'   => '9a2e47596038446b8586f4d092da2cd2',
        ],
        'singplus'  => [
          'openid'  => '272e14cbc32a41e7a7d38f8958f78ac9',
          'token'   => '3a1391f1acf64d819f1a993fb7e25a1f',
        ],
      ],
    ]);
  }

  public function testLoginSuccess()
  {
    $user = factory(\SingPlus\Domains\Users\Models\User::class)->create([
      'country_code'  => '254',
      'mobile'        => '254123456789',
      'source'        => 'mobile',
    ]);
    factory(\SingPlus\Domains\Users\Models\UserProfile::class)->create([
      'user_id' => $user->id, 
    ]);
    factory(\SingPlus\Domains\Users\Models\TUDCUser::class)->create([
      'user_id' => $user->id,
      'channels'  => [
        'singplus'  => [
          'openid'  => '272e14cbc32a41e7a7d38f8958f78ac9',
          'token'   => '059d1fec7d7e4ec396cffeaf00e1b85c',
        ],
        'boomsing'  => [
          'openid'  => '714263c5119e48018d47ed21aa3e6b1d',
          'token'   => '9a2e47596038446b8586f4d092da2cd2',
        ],
      ],
    ]);
    $this->mockHttpClient(json_encode([
      'code'    => 0,
      'openid'  => '272e14cbc32a41e7a7d38f8958f78ac9',
      'token'   => '3a1391f1acf64d819f1a993fb7e25a1f',
    ]));
    $response = $this->postJson('v3/passport/tudc/login', [
                                  'countryCode' => '254',
                                  'mobile'      => '123456789',
                                  'password'    => '123456',
                                  'tudcTicket'  => '61d4121f8b2e48d7aa1095dec811b15b',
                                ]);
    $loginedUser = Auth::guard()->user();
    self::assertEquals($user->id, $loginedUser->id);
    $response->assertJson([
      'code'  => 0,
      'data'  => [
        'userId'      => $user->id,
        'isNewUser'   => false,
        'tudcOpenid'  => '272e14cbc32a41e7a7d38f8958f78ac9',
      ],
    ]);

    self::assertDatabaseHas('tudc_users', [
      'user_id'   => $loginedUser->id,
      'channels'  => [
        'singplus'  => [
          'openid'        => '272e14cbc32a41e7a7d38f8958f78ac9',
          'token'         => '3a1391f1acf64d819f1a993fb7e25a1f',    // refreshed
        ],
        'boomsing'  => [
          'openid'  => '714263c5119e48018d47ed21aa3e6b1d',
          'token'   => '9a2e47596038446b8586f4d092da2cd2',
        ],
      ],
    ]);
  }

  public function testLoginFailed_TUDCUserNotExists()
  {
    $this->mockHttpClient('error', 401);
    $response = $this->postJson('v3/passport/tudc/login', [
                                  'countryCode' => '254',
                                  'mobile'      => '123456789',
                                  'password'    => '123456',
                                  'tudcTicket'  => '61d4121f8b2e48d7aa1095dec811b15b',
                                ]);
    $loginedUser = Auth::guard()->user();
    self::assertNull($loginedUser);
  }

  public function testLoginFailed_Register_TUDCGatewayError()
  {
    $this->mockHttpClient(json_encode([
      'code'    => 110005,
      'msg'     => 'st is invalid',
    ]));
    $response = $this->postJson('v3/passport/tudc/login', [
                                  'countryCode' => '254',
                                  'mobile'      => '123456789',
                                  'password'    => '123456',
                                  'tudcTicket'  => '61d4121f8b2e48d7aa1095dec811b15b',
                                ]);
    $loginedUser = Auth::guard()->user();
    self::assertNull($loginedUser);
    $response->assertJson([
      'code'  => 10130,
      'message' => 'tudc user login failed',
    ]);
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
