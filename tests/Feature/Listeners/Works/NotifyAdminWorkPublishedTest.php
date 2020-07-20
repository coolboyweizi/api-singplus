<?php

namespace FeatureTest\SingPlus\Listeners\Works;

use Mockery;
use Cache;
use GuzzleHttp\Client;
use GuzzleHttp\Handler\MockHandler;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Psr7\Response;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use FeatureTest\SingPlus\TestCase;
use FeatureTest\SingPlus\MongodbClearTrait;
use SingPlus\Events\Works\WorkPublished as WorkPublishedEvent;

class NotifyAdminWorkPublishedTest extends TestCase
{
  use MongodbClearTrait; 

  public function testNotifySuccess()
  {
    $work = factory(\SingPlus\Domains\Works\Models\Work::class)->create([
      'user_id' => '23cc19ce97e148d99f069798e1e5d8c5',
      'status'  => 1,
    ]);

    $this->mockHttpClient(json_encode([
      'code'    => 0,
      'message' => '',
      'task_id' => 'da1531476cea412aac1c7a06c6a79965',
    ]));

    $event = new WorkPublishedEvent($work->id);
    $success = $this->getListener()->handle($event);

    self::assertTrue($success);
  }

  public function testNotifyFailed()
  {
    $work = factory(\SingPlus\Domains\Works\Models\Work::class)->create([
      'user_id' => '23cc19ce97e148d99f069798e1e5d8c5',
      'status'  => 1,
    ]);

    $this->mockHttpClient(json_encode([
      'code'    => 1000,
      'message' => 'work aready notification',
    ]));

    $event = new WorkPublishedEvent($work->id);
    $success = $this->getListener()->handle($event);

    self::assertFalse($success);
  }

  private function getListener()
  {
    return $this->app->make(\SingPlus\Listeners\Works\NotifyAdminWorkPublished::class);
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
  protected final function mockHttpClient(
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
