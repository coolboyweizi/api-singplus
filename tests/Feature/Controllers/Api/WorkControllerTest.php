<?php

namespace FeatureTest\SingPlus\Controllers\Api;

use Mockery;
use Cache;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use FeatureTest\SingPlus\TestCase;
use FeatureTest\SingPlus\MongodbClearTrait;
use SingPlus\Events\UserTriggerFavouriteWork as UserTriggerFavouriteWorkEvent;

class WorkControllerTest extends TestCase
{
  use MongodbClearTrait; 

  //=================================
  //    syntheticUserCommentWork
  //=================================
  public function testSyntheticUserCommentWorkSuccess()
  {
    $this->expectsEvents(\SingPlus\Events\UserCommentWork::class);

    $work = factory(\SingPlus\Domains\Works\Models\Work::class)->create();
    $user = factory(\SingPlus\Domains\Users\Models\User::class)->create([
      'source'  => 'synthetic',
    ]);
    $counterMock = Mockery::mock(\Illuminate\Contracts\Cache\Store::class);
    $counterMock->shouldReceive('increment')
                ->once()
                ->with('comments', 100)
                ->andReturn(100);
    Cache::shouldReceive('driver')
         ->once()
         ->with('counter')
         ->andReturn($counterMock);

      $popularityService = $this->mockPopularityHierarchyService();
      $popularityService->shouldReceive('updatePopularity')
          ->once()
          ->with($work->id)
          ->andReturn();

    $response = $this->postJson(sprintf('api/works/%s/comment', $work->id), [
      'syntheticUserId' => $user->id,
      'comment'         => 'hello world',
    ]);
    $response->assertJson(['code' => 0]);

    $commentId = (json_decode($response->getContent()))->data->commentId;

    self::assertDatabaseHas('works', [
      '_id'           => $work->id,
      'comment_count' => 1,
    ]);
    self::assertDatabaseHas('comments', [
      '_id'           => $commentId,
      'work_id'       => $work->id,
      'content'       => 'hello world',
      'author_id'     => $user->id,
      'display_order' => 100,
    ]);
  }

  public function testSyntheticUserCommentWorkFailed_UserNotExist()
  {
    $work = factory(\SingPlus\Domains\Works\Models\Work::class)->create();
    $response = $this->postJson(sprintf('api/works/%s/comment', $work->id), [
      'syntheticUserId' => 'bb9207c7d5c14d579b547532e69ab725',
      'comment'         => 'hello world',
    ]);
    $response->assertJson(['code' => 10103]);
  }

  public function testSyntheticUserCommentWorkFailed_UserNotSynthetic()
  {
    $work = factory(\SingPlus\Domains\Works\Models\Work::class)->create();
    $user = factory(\SingPlus\Domains\Users\Models\User::class)->create();
    $response = $this->postJson(sprintf('api/works/%s/comment', $work->id), [
      'syntheticUserId' => $user->id,
      'comment'         => 'hello world',
    ]);
    $response->assertJson([
      'code'    => 10103,
      'message' => 'synthetic user not exists',
    ]);
  }

  //=================================
  //    syntheticUserFavouriteWork
  //=================================
  public function testSyntheticUserFavouriteWorkSuccess()
  {
    $this->expectsEvents(UserTriggerFavouriteWorkEvent::class);

    $user = factory(\SingPlus\Domains\Users\Models\User::class)->create([
      'source'  => 'synthetic',
    ]);

    $work = factory(\SingPlus\Domains\Works\Models\Work::class)->create();

    $popularityService = $this->mockPopularityHierarchyService();
    $popularityService->shouldReceive('updatePopularity')
        ->once()
        ->with($work->id)
        ->andReturn();

    $response = $this->postJson(sprintf('api/works/%s/favourite', $work->id), [
                        'syntheticUserId' => $user->id,
                     ]);
    $response->assertJson(['code' => 0]);

    $this->assertDatabaseHas('work_favourites', [
      'user_id'     => $user->id,
      'work_id'     => $work->id,
      'deleted_at'  => null,
    ]);
    $this->assertDatabaseHas('works', [
      '_id'             => $work->id,
      'favourite_count' => 1,             // from empty add to 1
    ]);
  }

  public function testSyntheticUserFavouriteWorkSuccess_EnableSignCheck()
  {
    $this->expectsEvents(UserTriggerFavouriteWorkEvent::class);
    config([
      'admin' => [
        'signature' => 'ABCDEFGHIJKLMN',
      ],
    ]);

    $user = factory(\SingPlus\Domains\Users\Models\User::class)->create([
      'source'  => 'synthetic',
    ]);

    $work = factory(\SingPlus\Domains\Works\Models\Work::class)->create();
    $plain = 'nonce=mmnop&syntheticUserId=' . $user->id . '&ABCDEFGHIJKLMN';
    $sign = sha1($plain, false);

    $this->enableRequestSignCheckMiddleware();

      $popularityService = $this->mockPopularityHierarchyService();
      $popularityService->shouldReceive('updatePopularity')
          ->once()
          ->with($work->id)
          ->andReturn();

    $response = $this->postJson(sprintf('api/works/%s/favourite', $work->id), [
                        'syntheticUserId' => $user->id,
                        'nonce'           => 'mmnop',
                        'sign'            => $sign,
                     ]);
    $response->assertJson(['code' => 0]);

    $this->assertDatabaseHas('work_favourites', [
      'user_id'     => $user->id,
      'work_id'     => $work->id,
      'deleted_at'  => null,
    ]);
    $this->assertDatabaseHas('works', [
      '_id'             => $work->id,
      'favourite_count' => 1,             // from empty add to 1
    ]);
  }

  public function testSyntheticUserFavouriteWorkSuccess_AreadyFavourited()
  {
    $user = factory(\SingPlus\Domains\Users\Models\User::class)->create([
      'source'  => 'synthetic',
    ]);

    $work = factory(\SingPlus\Domains\Works\Models\Work::class)->create([
      'favourite_count' => 1, 
    ]);
    $favourite = factory(\SingPlus\Domains\Works\Models\WorkFavourite::class)->create([
      'user_id'     => $user->id,
      'work_id'     => $work->id,
      'deleted_at'  => null,
    ]);

      $popularityService = $this->mockPopularityHierarchyService();
      $popularityService->shouldReceive('updatePopularity')
          ->never()
          ->with($work->id)
          ->andReturn();

    $response = $this->postJson(sprintf('api/works/%s/favourite', $work->id), [
                        'syntheticUserId' => $user->id,
                     ]);
    $response->assertJson(['code' => 0]);

    $this->assertDatabaseHas('work_favourites', [
      'user_id'     => $user->id,
      'work_id'     => $work->id,
      'deleted_at'  => null,              // not changed
    ]);
    $this->assertDatabaseHas('works', [
      '_id'             => $work->id,
      'favourite_count' => 1,             // not changed
    ]);
  }

    private function mockPopularityHierarchyService(){
        $popularityHierarchyService = Mockery::mock(\SingPlus\Contracts\Hierarchy\Services\PopularityHierarchyService::class);
        $this->app[\SingPlus\Contracts\Hierarchy\Services\PopularityHierarchyService::class ] = $popularityHierarchyService;
        return $popularityHierarchyService;
    }
}
