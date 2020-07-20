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
use SingPlus\Events\Works\RankExpired as RankExpiredEvent;

class NotifyAdminUpdateWorkRankingListTest extends TestCase
{
  use MongodbClearTrait; 

  public function testNotifySuccess()
  {
    $music = factory(\SingPlus\Domains\Musics\Models\Music::class)->create();
    config([
      'admin.endpoints.work_ranking_update_notification' => 'http://cms-sing/v1/api/refershMusicWorkRankList',
    ]);

    $this->mockHttpClient(json_encode([
      'code'    => 0,
      'task_id' => 'da1531476cea412aac1c7a06c6a79965',
    ]));

    $event = new RankExpiredEvent($music->id);
    $success = $this->getListener()->handle($event);

    self::assertTrue($success);
  }

  private function getListener()
  {
    return $this->app->make(\SingPlus\Listeners\Works\NotifyAdminUpdateWorkRankingList::class);
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
