<?php

namespace FeatureTest\SingPlus\Listeners\Feeds;

use Mockery;
use Cache;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use FeatureTest\SingPlus\TestCase;
use FeatureTest\SingPlus\MongodbClearTrait;
use SingPlus\Events\Friends\UserFollowed as UserFollowedEvent;

class CreateUserFollowedFeedTest extends TestCase
{
  use MongodbClearTrait; 

  public function testSuccess()
  {
    $counterMock = Mockery::mock(\Illuminate\Contracts\Cache\Store::class);
    $counterMock->shouldReceive('increment')
                ->once()
                ->with('feeds', 100)
                ->andReturn(100);
    Cache::shouldReceive('driver')
         ->once()
         ->with('counter')
         ->andReturn($counterMock);
    $userId = '4e0b58872daa4577a71a0008cf009d9d';
    $followedUserId = 'beb46bc1ca914a8daa95a4b6fa22962b';

    $event = new UserFollowedEvent($userId, $followedUserId);
    $feedId = $this->getListener()
                   ->handle($event);

    self::assertDatabaseHas('feeds', [
      '_id'               => $feedId,
      'user_id'           => $followedUserId,
      'operator_user_id'  => $userId,
      'type'              => 'user_followed',
      'detail'            => [],
      'status'            => 1,
      'is_read'           => 0,
      'display_order'     => 100,
    ]);
  }

  public function testFailed_FollowSelf()
  {
    $userId = '4e0b58872daa4577a71a0008cf009d9d';

    $event = new UserFollowedEvent($userId, $userId);
    $feedId = $this->getListener()
                   ->handle($event);
    self::assertNull($feedId);

    self::assertDatabaseMissing('feeds', [
      'user_id' => $userId,
    ]);
  }

  private function getListener()
  {
    return $this->app->make(\SingPlus\Listeners\Feeds\CreateUserFollowedFeed::class);
  }
}
