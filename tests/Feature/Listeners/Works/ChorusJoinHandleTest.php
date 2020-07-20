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

class ChorusJoinHandleTest extends TestCase
{
  use MongodbClearTrait; 

  public function testChorusJoinWorkPublishedSuccess()
  {
    $this->expectsEvents(\SingPlus\Events\FeedCreated::class);
    $music = factory(\SingPlus\Domains\Musics\Models\Music::class)->create([
      'name'  => 'my-music', 
    ]);
    $workStart = factory(\SingPlus\Domains\Works\Models\Work::class)->create([
      'user_id'     => '2af8aa3d7fe3401d9b9194dc102c9c48',
      'music_id'    => $music->id,
      'chorus_type' => 1,
    ]);
    $workJoin = factory(\SingPlus\Domains\Works\Models\Work::class)->create([
      'name'        => 'work-join',
      'user_id'     => 'e231be0180b9411aafbc5b73d1b67901',
      'music_id'    => $music->id,
      'chorus_type' => 10,
      'chorus_join_info'  => [
        'origin_work_id'  => $workStart->id,
      ],
      'description'       => 'morning!',
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
         ->with(sprintf('work:%s:listennum', $workStart->id))
         ->andReturn(100);
    Cache::shouldReceive('get')
         ->once()
         ->with(sprintf('work:%s:listennum', $workJoin->id))
         ->andReturn(200);
    Cache::shouldReceive('get')
         ->with(sprintf('music:%s:reqnum', $music->id))
         ->andReturn(1000);

    $event = new WorkPublishedEvent($workJoin->id);
    $success = $this->getListener()->handle($event);

    self::assertDatabaseHas('works', [
      '_id'               => $workStart->id,
      'chorus_start_info' => [
        'chorus_count'  => 1,
      ],
    ]);
    self::assertDatabaseHas('feeds', [
      '_id'               => $success,
      'user_id'           => $workStart->user_id,
      'operator_user_id'  => $workJoin->user_id,
      'type'              => 'work_chorus_join',
      'detail'            => [
        'work_id'                       => $workStart->id,
        'work_name'                     => 'my-music',
        'work_chorus_join_id'           => $workJoin->id,
        'work_chorus_join_name'         => 'work-join',
        'work_chorus_join_description'  => 'morning!',
      ],
      'status'            => 1,
      'is_read'           => 0,
      'display_order'     => 100,
    ]);
  }

  public function testChorusJoinWorkPublishedFailed_ChorusStartMissed()
  {
    $music = factory(\SingPlus\Domains\Musics\Models\Music::class)->create([
      'name'  => 'my-music', 
    ]);
    $workStart = factory(\SingPlus\Domains\Works\Models\Work::class)->create([
      'user_id'     => '2af8aa3d7fe3401d9b9194dc102c9c48',
      'music_id'    => $music->id,
    ]);
    $workJoin = factory(\SingPlus\Domains\Works\Models\Work::class)->create([
      'name'        => 'work-join',
      'user_id'     => 'e231be0180b9411aafbc5b73d1b67901',
      'music_id'    => $music->id,
      'chorus_type' => 10,
      'chorus_join_info'  => [
        'origin_work_id'  => $workStart->id,
      ],
      'description'       => 'morning!',
    ]);

    $event = new WorkPublishedEvent($workJoin->id);
    $success = $this->getListener()->handle($event);

    self::assertNull($success);
  }

  private function getListener()
  {
    return $this->app->make(\SingPlus\Listeners\Works\ChorusJoinHandle::class);
  }
}
