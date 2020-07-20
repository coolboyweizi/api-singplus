<?php

namespace FeatureTest\SingPlus\Listeners\Feeds;

use Mockery;
use Cache;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use FeatureTest\SingPlus\TestCase;
use FeatureTest\SingPlus\MongodbClearTrait;
use SingPlus\Events\UserTriggerFavouriteWork as UserTriggerFavouriteWorkEvent;

class CreateWorkFavouriteFeedTest extends TestCase
{
  use MongodbClearTrait; 

  public function testSuccess()
  {
    $this->expectsEvents(\SingPlus\Events\FeedCreated::class);
    $counterMock = Mockery::mock(\Illuminate\Contracts\Cache\Store::class);
    $counterMock->shouldReceive('increment')
                ->once()
                ->with('feeds', 100)
                ->andReturn(100);
    Cache::shouldReceive('driver')
         ->once()
         ->with('counter')
         ->andReturn($counterMock);

    $work = factory(\SingPlus\Domains\Works\Models\Work::class)->create([
      'user_id' => '3a39bd85c1844fc9b165f474f0e4b2c8',
      'status'  => 1,
    ]);
    $favourite = factory(\SingPlus\Domains\Works\Models\WorkFavourite::class)->create([
      'user_id'         => '06e99e9078f7410b898b9819dabb94cc',
      'work_id'         => $work->id,
    ]);
    Cache::shouldReceive('get')
         ->once()
         ->with(sprintf('work:%s:listennum', $work->id))
         ->andReturn(25);

    $event = new UserTriggerFavouriteWorkEvent($favourite->id, 'add');
    $feedId = $this->getListener()
                   ->handle($event);

    self::assertDatabaseHas('feeds', [
      '_id'               => $feedId,
      'user_id'           => $work->user_id,
      'operator_user_id'  => $favourite->user_id,
      'type'              => 'work_favourite',
      'detail'            => [
                              'work_id'     => $work->id,
                              'favourit_id' => $favourite->id,
                            ],
      'status'            => 1,
      'is_read'           => 0,
      'display_order'     => 100,
    ]);
  }

  public function testSuccess_Cancel()
  {
    $this->expectsEvents(\SingPlus\Events\FeedCreated::class);
    $counterMock = Mockery::mock(\Illuminate\Contracts\Cache\Store::class);
    $counterMock->shouldReceive('increment')
                ->once()
                ->with('feeds', 100)
                ->andReturn(100);
    Cache::shouldReceive('driver')
         ->once()
         ->with('counter')
         ->andReturn($counterMock);

    $work = factory(\SingPlus\Domains\Works\Models\Work::class)->create([
      'user_id' => '3a39bd85c1844fc9b165f474f0e4b2c8',
      'status'  => 1,
    ]);
    $favourite = factory(\SingPlus\Domains\Works\Models\WorkFavourite::class)->create([
      'user_id'         => '06e99e9078f7410b898b9819dabb94cc',
      'work_id'         => $work->id,
      'deleted_at'      => \Carbon\Carbon::yesterday(),
    ]);
    Cache::shouldReceive('get')
         ->once()
         ->with(sprintf('work:%s:listennum', $work->id))
         ->andReturn(25);

    $event = new UserTriggerFavouriteWorkEvent($favourite->id, 'cancel');
    $feedId = $this->getListener()
                   ->handle($event);

    self::assertDatabaseHas('feeds', [
      '_id'               => $feedId,
      'user_id'           => $work->user_id,
      'operator_user_id'  => $favourite->user_id,
      'type'              => 'work_favourite_cancel',
      'detail'            => [
                              'work_id'     => $work->id,
                              'favourit_id' => $favourite->id,
                            ],
      'status'            => 1,
      'is_read'           => 0,
      'display_order'     => 100,
    ]);
  }

  private function getListener()
  {
    return $this->app->make(\SingPlus\Listeners\Feeds\CreateWorkFavouriteFeed::class);
  }
}
