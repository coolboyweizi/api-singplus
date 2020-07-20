<?php

namespace FeatureTest\SingPlus\Services;

use Cache;
use Mockery;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use FeatureTest\SingPlus\TestCase;
use FeatureTest\SingPlus\MongodbClearTrait;

class WorkServiceTest extends TestCase
{
  use MongodbClearTrait; 

  //=================================
  //      getH5Selections
  //=================================
  public function testGetH5SelectionsSuccess()
  {
    $data = $this->prepareWorks();
    Cache::shouldReceive('get')
         ->once()
         ->with(sprintf('music:%s:reqnum', $data->work->one->music_id))
         ->andReturn(123);
    Cache::shouldReceive('get')
         ->once()
         ->with(sprintf('music:%s:reqnum', $data->work->two->music_id))
         ->andReturn(124);
    Cache::shouldReceive('get')
         ->once()
         ->with(sprintf('work:%s:listennum', $data->work->one->id))
         ->andReturn(25);
    Cache::shouldReceive('get')
         ->once()
         ->with(sprintf('work:%s:listennum', $data->work->two->id))
         ->andReturn(1888);

    $selections = $this->getWorkService()->getH5Selections('TZ');
    self::assertCount(2, $selections);
    self::assertEquals($data->work->two->id, $selections[0]->workId);
    self::assertEquals(1888, $selections[0]->listenCount);
  }

  private function prepareWorks($user = null)
  {
    $userOne = $user ?: factory(\SingPlus\Domains\Users\Models\User::class)->create();
    $userTwo = factory(\SingPlus\Domains\Users\Models\User::class)->create();

    $artistOne = factory(\SingPlus\Domains\Musics\Models\Artist::class)->create([
      'name'  => 'Simon',
    ]);
    $artistTwo = factory(\SingPlus\Domains\Musics\Models\Artist::class)->create([
      'name'  => 'Plum',
    ]);

    $profileOne = factory(\SingPlus\Domains\Users\Models\UserProfile::class)->create([
      'user_id'   => $userOne->id,
      'nickname'  => 'zhangsan', 
      'gender'    => 'M',
      'avatar'    => 'avatar-one',
    ]);
    $profileTwo = factory(\SingPlus\Domains\Users\Models\UserProfile::class)->create([
      'user_id'   => $userTwo->id,
      'nickname'  => 'lisi', 
      'avatar'    => 'avatar-two',
    ]);
    $musicOne = factory(\SingPlus\Domains\Musics\Models\Music::class)->create([
      'name'    => 'musicOne "hell"',
      'lyrics'  => 'music-lyric-one',
      'artists' => [$artistOne->id, $artistTwo->id],
      'covers'  => ['music-one-cover-one', 'music-one-cover-two'],
    ]);
    $musicTwo = factory(\SingPlus\Domains\Musics\Models\Music::class)->create([
      'name'    => 'musicTwo',
      'lyrics'  => 'music-lyric-two',
      'artists' => [$artistOne->id],
    ]);
    $workOne = factory(\SingPlus\Domains\Works\Models\Work::class)->create([
      'user_id'   => $userOne->id,
      'music_id'  => $musicOne->id,
      'cover'     => 'work-cover-one',
      'slides'    => [
        'work-one-one', 'work-one-two',
      ],
      'display_order' => 100,
      'comment_count' => 0,
      'favourite_count' => 1,
      'resource'      => 'work-one',
      'duration'      => 128,
    ]);
    $workTwo = factory(\SingPlus\Domains\Works\Models\Work::class)->create([
      'user_id'   => $userTwo->id,
      'music_id'  => $musicTwo->id,
      'cover'     => 'work-cover-two',
      'slides'    => [
        'work-two-one', 'work-two-two',
      ],
      'display_order' => 200,
      'comment_count' => 0,
      'favourite_count' => 1,
      'resource'      => 'work-two',
    ]);
    $selectOne = factory(\SingPlus\Domains\Works\Models\H5WorkSelection::class)->create([
      'work_id'       => $workOne->id,
      'display_order' => 100,
      'country_abbr'  => 'TZ',
    ]);
    $selectTwo = factory(\SingPlus\Domains\Works\Models\H5WorkSelection::class)->create([
      'work_id' => $workTwo->id,
      'display_order' => 200,
      'country_abbr'  => 'TZ',
    ]);

    return (object) [
      'user'   => (object) [
        'one' => $userOne,
        'two' => $userTwo,
      ],
      'profile' => (object) [
        'one' => $profileOne,
        'two' => $profileTwo,
      ],
      'music' => (object) [
        'one' => $musicOne,
        'two' => $musicTwo,
      ],
      'work'  => (object) [
        'one' => $workOne,
        'two' => $workTwo,
      ],
      'selections'  => (object) [
        'one' => $selectOne,
        'two' => $selectTwo,
      ],
    ];
  }

  private function getWorkService()
  {
    return $this->app[\SingPlus\Services\WorkService::class];
  }
}
