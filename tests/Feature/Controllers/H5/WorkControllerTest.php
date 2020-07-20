<?php

namespace FeatureTest\SingPlus\Controllers\H5;

use Cache;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use FeatureTest\SingPlus\TestCase;
use FeatureTest\SingPlus\MongodbClearTrait;
use Mockery;

class WorkControllerTest extends TestCase
{
  use MongodbClearTrait; 

  //=================================
  //        getDetail
  //=================================
  public function testGetDetailSuccuss()
  {
    $this->enableNationOperationMiddleware();
    config([
      'nationality.operation_country_abbr'  => ['TZ'],
    ]);
    $user = factory(\SingPlus\Domains\Users\Models\User::class)->create();
    factory(\SingPlus\Domains\Users\Models\UserProfile::class)->create([
      'user_id' => $user->id, 
    ]);
    $data = $this->prepareWorks();

    $urlShortener = \Mockery::mock(\SingPlus\Contracts\Supports\UrlShortener::class);
    $this->app[\SingPlus\Contracts\Supports\UrlShortener::class ] = $urlShortener;
    $urlShortener->shouldReceive('shorten')
                 ->never()
                 ->with(secure_url(sprintf('c/page/works/%s', $data->work->one->id)))
                 ->andReturn('http://goo.gl/abcde');

    Cache::shouldReceive('increment')
         ->once()
         ->with(sprintf('work:%s:listennum', $data->work->one->id));
    Cache::shouldReceive('increment')
         ->once()
         ->with(sprintf('user:%s:listennum', $data->work->one->user_id));
    Cache::shouldReceive('get')
         ->once()
         ->with(sprintf('work:%s:listennum', $data->work->one->id))
         ->andReturn(180);
    Cache::shouldReceive('get')
         ->once()
         ->with(sprintf('music:%s:reqnum', $data->work->one->music_id))
         ->andReturn(230);
    Cache::shouldReceive('get')
         ->never()
         ->with(sprintf('work:%s:surl', $data->work->one->id))
         ->andReturn(null);
    Cache::shouldReceive('forever')
         ->never()
         ->with(sprintf('work:%s:surl', $data->work->one->id), secure_url(sprintf('c/page/works/%s', $data->work->one->id)));
    Cache::shouldReceive('get')
         ->once()
         ->with(sprintf('work:%s:listennum', $data->work->one->id))
         ->andReturn(100);
    Cache::shouldReceive('get')
         ->once()
         ->with(sprintf('music:%s:reqnum', $data->music->one->id))
         ->andReturn(100);

    //$response = $this->getJson('c/api/works/' . $data->work->one->id);
    $response = $this->getJson('c/api/works/' . $data->work->one->id . '?' . http_build_query([
      'countryAbbr'  => 'TZ',
    ]));
    $response->assertJson(['code' => 0]);
    $work = (json_decode($response->getContent()))->data->work;
    self::assertEquals($data->work->one->id, $work->workId);
    self::assertCount(2, $work->slides);
    self::assertTrue(ends_with($work->slides[0], 'work-one-one'));
    self::assertEquals('zhangsan', $work->nickname);
    self::assertTrue(ends_with($work->avatar, 'avatar-one'));
    self::assertTrue(ends_with($work->resource, 'work-one'));
    self::assertTrue(ends_with($work->lyric, 'music-lyric-one'));
    self::assertEquals(secure_url(sprintf('c/page/works/%s', $data->work->one->id)), $work->shareLink);
  }

  public function testGetDetailFailed_NotExists()
  {
    $user = factory(\SingPlus\Domains\Users\Models\User::class)->create();
    factory(\SingPlus\Domains\Users\Models\UserProfile::class)->create([
      'user_id' => $user->id, 
    ]);
    $response = $this->actingAs($user)
                     ->getJson('c/api/works/1fe1cf9cf3854e539b947e725b266baa')
                     ->assertJson(['code' => 10402]);
  }


    public function testGetUsersWorks_H5Page()
    {
        $user = factory(\SingPlus\Domains\Users\Models\User::class)->create();
        factory(\SingPlus\Domains\Users\Models\UserProfile::class)->create([
            'user_id' => $user->id,
        ]);
        $data = $this->prepareWorks($user);

        Cache::shouldReceive('increment')
            ->once()
            ->with(sprintf('work:%s:listennum', $data->work->one->id));
        Cache::shouldReceive('increment')
            ->once()
            ->with(sprintf('user:%s:listennum', $data->work->one->user_id));
        Cache::shouldReceive('get')
            ->never()
            ->with(sprintf('work:%s:listennum', $data->work->one->id))
            ->andReturn(180);
        Cache::shouldReceive('get')
            ->never()
            ->with(sprintf('music:%s:reqnum', $data->work->one->music_id))
            ->andReturn(230);
        Cache::shouldReceive('get')
            ->never()
            ->with(sprintf('work:%s:surl', $data->work->one->id))
            ->andReturn(null);
        Cache::shouldReceive('forever')
            ->never()
            ->with(sprintf('work:%s:surl', $data->work->one->id), secure_url(sprintf('c/page/works/%s', $data->work->one->id)));
        Cache::shouldReceive('get')
            ->once()
            ->with(sprintf('work:%s:listennum', $data->work->one->id))
            ->andReturn(100);
        Cache::shouldReceive('get')
            ->once()
            ->with(sprintf('music:%s:reqnum', $data->music->one->id))
            ->andReturn(100);

        $response = $this->actingAs($user)
            ->get('c/page/works/' . $data->work->one->id, [
                'X-CountryAbbr' => 'CN'
            ]);
//        print $response->getContent();
    }

  //=================================
  //        getComments
  //=================================
  public function testGetCommentsSuccess()
  {
    $user = factory(\SingPlus\Domains\Users\Models\User::class)->create();
    factory(\SingPlus\Domains\Users\Models\UserProfile::class)->create([
      'user_id' => $user->id, 
    ]);
    $data = $this->prepareWorks();
    $commentOne = factory(\SingPlus\Domains\Works\Models\Comment::class)->create([
      'work_id'         => $data->work->one->id,
      'comment_id'      => null,
      'content'         => 'comment one',
      'author_id'       => $data->user->two->id,
      'replied_user_id' => $data->work->one->user_id,
      'status'          => 1,
      'display_order'   => 100,
    ]);
    $commentTwo = factory(\SingPlus\Domains\Works\Models\Comment::class)->create([
      'work_id'         => $data->work->one->id,
      'comment_id'      => $commentOne->id,
      'content'         => 'comment two',
      'author_id'       => $data->user->two->id,
      'replied_user_id' => $commentOne->author_id,
      'status'          => 1,
      'display_order'   => 200,
    ]);

    $response = $this->actingAs($user)
                     ->getJson('c/api/works/' . $data->work->one->id . '/comments');

    $response->assertJson(['code' => 0]);
    $comments = (json_decode($response->getContent()))->data->comments;
    self::assertCount(2, $comments);
    self::assertEquals($commentTwo->id, $comments[0]->commentId);   // order by display_order desc
    self::assertEquals($data->user->two->id, $comments[0]->authorId);
    self::assertEquals('lisi', $comments[0]->nickname);
    self::assertEquals('lisi', $comments[0]->repliedUserNickname);
    self::assertEquals('comment two', $comments[0]->content);
  }

  public function testGetSelectionsSuccess_CommentNotFound()
  {
    $user = factory(\SingPlus\Domains\Users\Models\User::class)->create();
    factory(\SingPlus\Domains\Users\Models\UserProfile::class)->create([
      'user_id' => $user->id, 
    ]);
    $response = $this->actingAs($user)
                     ->getJson('c/api/works/' . '690a69eac2c14180a2cf75ddbcfaa89e' . '/comments')
                     ->assertJson([
                        'code'  => 0,
                        'message' => '',
                        'data' => [
                          'comments'  => [],
                        ],
                     ]);
  }

  private function prepareWorks($user = null)
  {
    $userOne = $user ?: factory(\SingPlus\Domains\Users\Models\User::class)->create();
    $userTwo = factory(\SingPlus\Domains\Users\Models\User::class)->create();

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
      'name'    => 'musicOne',
      'lyrics'  => 'music-lyric-one',
    ]);
    $musicTwo = factory(\SingPlus\Domains\Musics\Models\Music::class)->create([
      'name'    => 'musicTwo',
      'lyrics'  => 'music-lyric-two',
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
      'resource'      => 'work-one',
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
      'resource'      => 'work-two',
    ]);
    $selectOne = factory(\SingPlus\Domains\Works\Models\WorkSelection::class)->create([
      'work_id'       => $workOne->id,
      'display_order' => 100,
    ]);
    $selectTwo = factory(\SingPlus\Domains\Works\Models\WorkSelection::class)->create([
      'work_id' => $workTwo->id,
      'display_order' => 200,
    ]);
    $h5SelectOne = factory(\SingPlus\Domains\Works\Models\H5WorkSelection::class)->create([
      'work_id'       => $workOne->id,
      'display_order' => 100,
      'country_abbr'  => 'TZ',
    ]);
    $h5SelectTwo = factory(\SingPlus\Domains\Works\Models\H5WorkSelection::class)->create([
      'work_id' => $workTwo->id,
      'display_order' => 200,
      'country_abbr'  => '-*',
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
}
