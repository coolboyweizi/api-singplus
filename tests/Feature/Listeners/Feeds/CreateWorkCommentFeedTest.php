<?php

namespace FeatureTest\SingPlus\Listeners\Feeds;

use Mockery;
use Cache;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use FeatureTest\SingPlus\TestCase;
use FeatureTest\SingPlus\MongodbClearTrait;
use SingPlus\Events\UserCommentWork as UserCommentWorkEvent;

class CreateWorkCommentFeedTest extends TestCase
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

    $comment = factory(\SingPlus\Domains\Works\Models\Comment::class)->create([
      'work_id'         => 'fc1d4efa317c4ce680d8d7374979c30e',
      'author_id'       => '06e99e9078f7410b898b9819dabb94cc',
      'replied_user_id' => '59dc46c4d8e64c8489bc5a108d1f2c6a',
      'status'          => 1,
    ]);

    $event = new UserCommentWorkEvent($comment->id, 'new');
    $feedId = $this->getListener()
                   ->handle($event);

    self::assertDatabaseHas('feeds', [
      '_id'               => $feedId,
      'user_id'           => $comment->replied_user_id,
      'operator_user_id'  => $comment->author_id,
      'type'              => 'work_comment',
      'detail'            => [
                              'work_id'     => $comment->work_id,
                              'comment_id'  => $comment->id,
                            ],
      'status'            => 1,
      'is_read'           => 0,
      'display_order'     => 100,
    ]);
  }

  public function testSuccess_DeleteFeeds()
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

    $comment = factory(\SingPlus\Domains\Works\Models\Comment::class)->create([
      'work_id'         => 'fc1d4efa317c4ce680d8d7374979c30e',
      'author_id'       => '06e99e9078f7410b898b9819dabb94cc',
      'replied_user_id' => '59dc46c4d8e64c8489bc5a108d1f2c6a',
      'status'          => 1,
    ]);

    $event = new UserCommentWorkEvent($comment->id, 'del');
    $feedId = $this->getListener()
                   ->handle($event);

    self::assertDatabaseHas('feeds', [
      '_id'               => $feedId,
      'user_id'           => $comment->replied_user_id,
      'operator_user_id'  => $comment->author_id,
      'type'              => 'work_comment_delete',
      'detail'            => [
                              'work_id'     => $comment->work_id,
                              'comment_id'  => $comment->id,
                            ],
      'status'            => 1,
      'is_read'           => 0,
      'display_order'     => 100,
    ]);
  }

  private function getListener()
  {
    return $this->app->make(\SingPlus\Listeners\Feeds\CreateWorkCommentFeed::class);
  }
}
