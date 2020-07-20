<?php

namespace FeatureTest\SingPlus\Listeners\Feeds;

use Mockery;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use FeatureTest\SingPlus\TestCase;
use FeatureTest\SingPlus\MongodbClearTrait;
use SingPlus\Events\Feeds\FeedReaded as FeedReadedEvent;

class SetUsersReadTest extends TestCase
{
  use MongodbClearTrait; 

  public function testSuccess()
  {
    $user = factory(\SingPlus\Domains\Users\Models\User::class)->create();
    $feedOne = factory(\SingPlus\Domains\Feeds\Models\Feed::class)->create([
      'user_id' => $user->id,
      'type'    => 'work_transmit',
      'is_read' => 0,
    ]);
    $feedTwo = factory(\SingPlus\Domains\Feeds\Models\Feed::class)->create([
      'user_id' => $user->id,
      'type'    => 'work_transmit',
      'is_read' => 1,
    ]);
    $feedThree = factory(\SingPlus\Domains\Feeds\Models\Feed::class)->create([
      'user_id' => '3929c6ce89cf4d9da49cdc2b44c294de',
      'type'    => 'work_transmit',
      'is_read' => 0,
    ]);
    $feedFour = factory(\SingPlus\Domains\Feeds\Models\Feed::class)->create([
      'user_id' => $user->id,
      'type'    => 'work_favourite',
      'is_read' => 0,
    ]);
    $event = new FeedReadedEvent($user->id, ['work_transmit', 'work_favourite']);
    $updatedRows = $this->getListener()
                        ->handle($event);
    self::assertEquals(2, $updatedRows);

    self::assertDatabaseHas('feeds', [
      '_id'           => $feedOne->id,
      'type'          => 'work_transmit',
      'is_read'       => 1,                 // aready updated
    ]);
    self::assertDatabaseHas('feeds', [
      '_id'           => $feedTwo->id,
      'type'          => 'work_transmit',
      'is_read'       => 1,                 // nothing changed 
    ]);
    self::assertDatabaseHas('feeds', [
      '_id'           => $feedThree->id,
      'type'          => 'work_transmit',
      'is_read'       => 0,                 // nothing changed 
    ]);
    self::assertDatabaseHas('feeds', [
      '_id'           => $feedFour->id,
      'type'          => 'work_favourite',
      'is_read'       => 1,                 // aready updated
    ]);
  }

  private function getListener()
  {
    return $this->app->make(\SingPlus\Listeners\Feeds\SetUserFeedsRead::class);
  }
}
