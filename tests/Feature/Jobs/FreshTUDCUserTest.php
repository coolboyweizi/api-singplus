<?php

namespace FeatureTest\SingPlus\Jobs;

use GuzzleHttp\Client;
use GuzzleHttp\Handler\MockHandler;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Psr7\Response;
use FeatureTest\SingPlus\TestCase;
use FeatureTest\SingPlus\MongodbClearTrait;
use SingPlus\Jobs\FreshTUDCUser as FreshTUDCUserJob;

class FreshTUDCUserTest extends TestCase
{
  use MongodbClearTrait;

  public function testSaveSuccess()
  {
    $this->mockHttpClient(json_encode([
      'code'    => 0,
      'openid'  => '272e14cbc32a41e7a7d38f8958f78ac9',
      'token'   => '3a1391f1acf64d819f1a993fb7e25a1f',
    ]));

    $job = new FreshTUDCUserJob(
      '2ba7c204f45e48d98c4a10e2e30cdf91',
      'df7ca63a1c244789ab4d39a26627bea3',
      'boomsing'
    );
    $res = $job->handle($this->app->make(\SingPlus\Services\Auth\TUDCService::class));

    $this->assertDatabaseHas('tudc_users', [
      'user_id'         => '2ba7c204f45e48d98c4a10e2e30cdf91',
      'channels'  => [
        'boomsing'  => [
          'openid'  => '272e14cbc32a41e7a7d38f8958f78ac9',
          'token'   => '3a1391f1acf64d819f1a993fb7e25a1f',
        ],
      ],
    ]);
  }

  public function testSaveSuccess_TicketInvalid()
  {
    $this->mockHttpClient(json_encode([
      'code'    => 110005,
      'msg'     => 'st is invalid',
    ]));

    $job = new FreshTUDCUserJob(
      '2ba7c204f45e48d98c4a10e2e30cdf91',
      'df7ca63a1c244789ab4d39a26627bea3',
      'singplus'
    );
    $job->handle($this->app->make(\SingPlus\Services\Auth\TUDCService::class));

    $this->assertDatabaseMissing('tudc_users', [
      'user_id'         => '2ba7c204f45e48d98c4a10e2e30cdf91',
    ]);
  }

  public function testSaveSuccess_TUDCUserExists()
  {
    factory(\SingPlus\Domains\Users\Models\TUDCUser::class)->create([
      'user_id'   => '2ba7c204f45e48d98c4a10e2e30cdf91',
      'channels'  => [
        'boomsing'  => [
          'openid'  => '272e14cbc32a41e7a7d38f8958f78ac9',
          'token'   => '6c239f6485e64da38e77754b42866a3a',
        ],
      ],
    ]);
    $this->mockHttpClient(json_encode([
      'code'    => 0,
      'openid'  => '272e14cbc32a41e7a7d38f8958f78ac9',
      'token'   => '3a1391f1acf64d819f1a993fb7e25a1f',
    ]));

    $job = new FreshTUDCUserJob(
      '2ba7c204f45e48d98c4a10e2e30cdf91',
      'df7ca63a1c244789ab4d39a26627bea3',
      'boomsing'
    );
    $res = $job->handle($this->app->make(\SingPlus\Services\Auth\TUDCService::class));

    $this->assertDatabaseHas('tudc_users', [
      'user_id'   => '2ba7c204f45e48d98c4a10e2e30cdf91',
      'channels'  => [
        'boomsing'  => [
          'openid'          => '272e14cbc32a41e7a7d38f8958f78ac9',
          'token'           => '3a1391f1acf64d819f1a993fb7e25a1f',  // changed
        ],
      ],
    ]);
  }

  public function testSaveSuccess_TUDCUserExists_ChannelNotExists()
  {
    factory(\SingPlus\Domains\Users\Models\TUDCUser::class)->create([
      'user_id'   => '2ba7c204f45e48d98c4a10e2e30cdf91',
      'channels'  => [
        'singplus'  => [
          'openid'  => '2eb6c701be2c47509d72277f161e48e1',
          'token'   => 'a1f944d922da4b45b23c80b3813dff1b',
        ],
      ],
    ]);
    $this->mockHttpClient(json_encode([
      'code'    => 0,
      'openid'  => '272e14cbc32a41e7a7d38f8958f78ac9',
      'token'   => '3a1391f1acf64d819f1a993fb7e25a1f',
    ]));

    $job = new FreshTUDCUserJob(
      '2ba7c204f45e48d98c4a10e2e30cdf91',
      'df7ca63a1c244789ab4d39a26627bea3',
      'boomsing'
    );
    $res = $job->handle($this->app->make(\SingPlus\Services\Auth\TUDCService::class));

    $this->assertDatabaseHas('tudc_users', [
      'user_id'   => '2ba7c204f45e48d98c4a10e2e30cdf91',
      'channels'  => [
        'singplus'  => [
          'openid'  => '2eb6c701be2c47509d72277f161e48e1',
          'token'   => 'a1f944d922da4b45b23c80b3813dff1b',
        ],
        'boomsing'  => [
          'openid'          => '272e14cbc32a41e7a7d38f8958f78ac9',
          'token'           => '3a1391f1acf64d819f1a993fb7e25a1f',
        ],
      ],
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
