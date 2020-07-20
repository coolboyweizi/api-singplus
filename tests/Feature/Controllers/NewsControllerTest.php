<?php
/**
 * Created by PhpStorm.
 * User: karl
 * Date: 2018/1/19
 * Time: 下午1:55
 */

namespace FeatureTest\SingPlus\Controllers;

use GuzzleHttp\Client;
use GuzzleHttp\Handler\MockHandler;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Psr7\Response;
use Cache;
use Mockery;
use FeatureTest\SingPlus\MongodbClearTrait;
use FeatureTest\SingPlus\TestCase;

class NewsControllerTest extends TestCase
{
    use MongodbClearTrait;

    //=================================
    //        deleteNews
    //=================================

    public function testDeleteNewsSuccess()
    {
        $user = factory(\SingPlus\Domains\Users\Models\User::class)->create();
        $work = factory(\SingPlus\Domains\Works\Models\Work::class)->create([
            'user_id' => $user->id,
            'status'  => 1
        ]);
        $news = factory(\SingPlus\Domains\News\Models\News::class)->create([
            'user_id' => $user->id,
            'detail' => [
                'work_id' => $work->id
            ],
            'status' => 1
        ]);

        $response = $this->actingAs($user)
            ->postJson('v3/news/delete', [
                'newsId'  => $news->id,
            ])
            ->assertJson(['code' => 0]);

        $this->assertDatabaseHas('news', [
            '_id'     => $news->id,
            'status'  => 0,             // deleted
        ]);
    }

    public function testDeleteNewsFailed_DeleteOthersNews(){
        $actionUser = factory(\SingPlus\Domains\Users\Models\User::class)->create();
        $user = factory(\SingPlus\Domains\Users\Models\User::class)->create();
        $work = factory(\SingPlus\Domains\Works\Models\Work::class)->create([
            'user_id' => $user->id,
            'status'  => 1
        ]);
        $news = factory(\SingPlus\Domains\News\Models\News::class)->create([
            'user_id' => $user->id,
            'detail' => [
                'work_id' => $work->id
            ],
            'status' => 1
        ]);
        $response = $this->actingAs($actionUser)
            ->postJson('v3/news/delete', [
                'newsId'  => $news->id,
            ])
            ->assertJson(['code' => 10620]);
        $this->assertDatabaseHas('news', [
            '_id'     => $news->id,
            'status'  => 1,             // normal
        ]);
    }

    public function testDeleteNewsFailed_NewsNotExsits(){
        $user = factory(\SingPlus\Domains\Users\Models\User::class)->create();
        $work = factory(\SingPlus\Domains\Works\Models\Work::class)->create([
            'user_id' => $user->id,
            'status'  => 1
        ]);
        $response = $this->actingAs($user)
            ->postJson('v3/news/delete', [
                'newsId'  => '7a3dc9810cb348079d4256dcd53b33eb',
            ])
            ->assertJson(['code' => 10601]);
        $this->assertDatabaseMissing('news', [
            '_id'     => '7a3dc9810cb348079d4256dcd53b33eb',
            'status'  => 1,             // normal
        ]);
    }

    //=================================
    //       getNewsList
    //=================================
    public function testGetNewsListsSuccess_OnlyForOneself(){

        $data = $this->prepareNews();
        $user = $data->user->four;

        $response = $this->actingAs($user)
            ->getJson('v3/news/latest?' . http_build_query([
                    'self'  => true,
            ]))
            ->assertJson(['code' => 0]);
        $response = json_decode($response->getContent());
        $news = $response->data->latests;
        self::assertCount(2, $news);
        self::assertEquals('news six desc', $news[0]->desc);
        self::assertEquals('news four desc', $news[1]->desc);
        self::assertEquals($user->id, $news[0]->author->userId);
        self::assertEquals($user->id, $news[1]->author->userId);
        self::assertEquals($data->work->six->id, $news[0]->detail->work->workId);
        self::assertEquals($data->work->four->id, $news[1]->detail->work->workId);

    }

    public function testGetNewsListsSuccess_SelfAndFollowings(){
        $data = $this->prepareNews();
        $user = $data->user->four;
        $response = $this->actingAs($user)
            ->getJson('v3/news/latest?' . http_build_query([
                    'self'  => false,
                ]))
            ->assertJson(['code' => 0]);
        $response = json_decode($response->getContent());
        $news = $response->data->latests;
        self::assertCount(6, $news);
        self::assertEquals('news seven desc', $news[0]->desc);
        self::assertEquals($data->user->four->id, $news[0]->detail->work->originWorkUser->userId);
        self::assertEquals($data->user_profile->one->nickname, $news[0]->author->nickname);
        self::assertEquals(false, $news[0]->detail->friends->isFollowing);
        self::assertEquals(false, $news[0]->detail->friends->isFollower);
        self::assertEquals('news six desc', $news[1]->desc);
        self::assertEquals(null, $news[1]->detail->friends);
        self::assertEquals('news four desc',  $news[2]->desc);
        self::assertEquals(null, $news[2]->detail->friends);
        self::assertEquals('news three desc', $news[3]->desc);
        self::assertEquals(true, $news[3]->detail->friends->isFollowing);
        self::assertEquals('news two desc', $news[4]->desc);
        self::assertEquals(true, $news[4]->detail->friends->isFollowing);
        self::assertEquals('news one desc', $news[5]->desc);
        self::assertEquals(true, $news[5]->detail->friends->isFollowing);
    }

    public function testGetNewsListsSuccess_WithoutLoginAndOtherId(){
        $data = $this->prepareNews();
        $user = $data->user->one;
        $response = $this->getJson('v3/news/latest?' . http_build_query([
                    'self'  => true,
                    'userId' => $user->id,
                ]))
            ->assertJson(['code' => 0]);
        $response = json_decode($response->getContent());
        $news = $response->data->latests;
        self::assertCount(2, $news);
        self::assertEquals('news seven desc', $news[0]->desc);
        self::assertEquals('news one desc', $news[1]->desc);
    }

    public function testGetNewsListsSuccess_OtherId(){
        $data = $this->prepareNews();
        $user = $data->user->one;
        $response = $this->actingAs($data->user->four)
            ->getJson('v3/news/latest?' . http_build_query([
                'self'  => true,
                'userId' => $user->id,
            ]))
            ->assertJson(['code' => 0]);
        $response = json_decode($response->getContent());
        $news = $response->data->latests;
        self::assertCount(2, $news);
        self::assertEquals('news seven desc', $news[0]->desc);
        self::assertEquals('news one desc', $news[1]->desc);
        self::assertEquals(true, $news[1]->detail->friends->isFollowing);
        self::assertEquals(false, $news[1]->detail->friends->isFollower);

    }

    public function testGetNewsListsSuccess_Pagination(){
        $data = $this->prepareNews();
        $user = $data->user->four;
        $response = $this->actingAs($user)
            ->getJson('v3/news/latest?' . http_build_query([
                    'self'  => false,
                    'isNext' => true,
                    'newsId' => $data->news->four->id,
                ]))
            ->assertJson(['code' => 0]);
        $response = json_decode($response->getContent());
        $news = $response->data->latests;
        self::assertCount(3, $news);
        self::assertEquals('news three desc', $news[0]->desc);
        self::assertEquals('news two desc', $news[1]->desc);
        self::assertEquals('news one desc', $news[2]->desc);
    }

    public function testGetNewsListsSuccess_PaginationWithSize(){
        $data = $this->prepareNews();
        $user = $data->user->four;
        $response = $this->actingAs($user)
            ->getJson('v3/news/latest?' . http_build_query([
                    'self'  => false,
                    'isNext' => true,
                    'size'   => 1,
                    'newsId' => $data->news->four->id,
                ]))
            ->assertJson(['code' => 0]);
        $response = json_decode($response->getContent());
        $news = $response->data->latests;
        self::assertCount(1, $news);
        self::assertEquals('news three desc', $news[0]->desc);
    }

    //=================================
    //       getNewsList_v4
    //=================================
    public function testGetNewsListsSuccess_OnlyForOneself_v4(){

        $data = $this->prepareNews();
        $user = $data->user->four;
        factory(\SingPlus\Domains\Users\Models\UserVerification::class)->create([
            'user_id'       => $data->user_profile->four->user_id,
            'profile_id'    => $data->user_profile->four->id,
            'verified_as'   => ['A', 'B'],
            'status'        => 1,
        ]);

        $this->mockHttpClient([
            [
                'body'  => json_encode([
                            'code'  => 0,
                            'data'  => [
                                'news'  => [
                                    $data->news->six->id,
                                    $data->news->four->id,
                                ],
                            ]]),
            ],
            [
                'body'  => json_encode([
                            'code'  => 0,
                            'data'  => [
                                'relation'  => [
                                    $data->news->six->user_id  => [
                                        'is_following'=> false,
                                        'follow_at'=> null,
                                        'is_follower'=> false,
                                        'followed_at'=> null,
                                    ],
                                    $data->news->four->user_id  => [
                                        'is_following'=> false,
                                        'follow_at'=> null,
                                        'is_follower'=> false,
                                        'followed_at'=> null,
                                    ],
                                ]
                            ]])
            ],
        ]);

        $response = $this->actingAs($user)
            ->getJson('v4/news/latest?' . http_build_query([
                    'self'  => true,
            ]))
            ->assertJson(['code' => 0]);
        $response = json_decode($response->getContent());
        $news = $response->data->latests;
        self::assertCount(2, $news);
        self::assertEquals('news six desc', $news[0]->desc);
        self::assertEquals('news four desc', $news[1]->desc);
        self::assertEquals($user->id, $news[0]->author->userId);
        self::assertEquals($user->id, $news[1]->author->userId);
        self::assertTrue($news[1]->author->verified->verified);
        self::assertEquals($data->work->six->id, $news[0]->detail->work->workId);
        self::assertEquals($data->work->four->id, $news[1]->detail->work->workId);
        self::assertTrue($news[1]->detail->work->verified->verified);
        self::assertEquals(['A', 'B'], $news[1]->detail->work->verified->names);

    }

    public function testGetNewsListsSuccess_SelfAndFollowings_v4()
    {
        $data = $this->prepareNews();
        $user = $data->user->four;

        $this->mockHttpClient([
            [
                'body'  => json_encode([
                            'code'  => 0,
                            'data'  => [
                                'news'  => [
                                    $data->news->seven->id,
                                    $data->news->six->id,
                                    $data->news->four->id,
                                    $data->news->three->id,
                                    $data->news->two->id,
                                    $data->news->one->id,
                                ],
                            ]]),
            ],
            [
                'body'  => json_encode([
                            'code'  => 0,
                            'data'  => [
                                'relation'  => [
                                    $data->user->one->id  => [
                                        'is_following'=> true,
                                        'follow_at'=> '2016-10-01 00:00:00',
                                        'is_follower'=> false,
                                        'followed_at'=> null,
                                    ],
                                    $data->user->two->id  => [
                                        'is_following'=> true,
                                        'follow_at'=> '2017-10-01 00:00:00',
                                        'is_follower'=> false,
                                        'followed_at'=> null,
                                    ],
                                    $data->user->three->id  => [
                                        'is_following'=> true,
                                        'follow_at'=> '2018-01-01 00:00:00',
                                        'is_follower'=> false,
                                        'followed_at'=> null,
                                    ],
                                    $data->user->five->id   => [
                                        'is_following'=> false,
                                        'follow_at'=> null,
                                        'is_follower'=> false,
                                        'followed_at'=> null,
                                    ],
                                ]
                            ]])
            ],
        ]);
        $response = $this->actingAs($user)
            ->getJson('v4/news/latest?' . http_build_query([
                    'self'  => false,
                ]))
            ->assertJson(['code' => 0]);
        $response = json_decode($response->getContent());
        $news = $response->data->latests;
        self::assertCount(6, $news);
        self::assertEquals('news seven desc', $news[0]->desc);
        self::assertEquals($data->user->four->id, $news[0]->detail->work->originWorkUser->userId);
        self::assertEquals($data->user_profile->one->nickname, $news[0]->author->nickname);
        self::assertEquals(false, $news[0]->detail->friends->isFollowing);
        self::assertEquals(false, $news[0]->detail->friends->isFollower);
        self::assertEquals('news six desc', $news[1]->desc);
        self::assertEquals(null, $news[1]->detail->friends);
        self::assertEquals('news four desc',  $news[2]->desc);
        self::assertEquals(null, $news[2]->detail->friends);
        self::assertEquals('news three desc', $news[3]->desc);
        self::assertEquals(true, $news[3]->detail->friends->isFollowing);
        self::assertEquals('news two desc', $news[4]->desc);
        self::assertEquals(true, $news[4]->detail->friends->isFollowing);
        self::assertEquals('news one desc', $news[5]->desc);
        self::assertEquals(true, $news[5]->detail->friends->isFollowing);
    }

    public function testGetNewsListsSuccess_WithoutLoginAndOtherId_v4(){
        $data = $this->prepareNews();
        $user = $data->user->one;

        $this->mockHttpClient([
            [
                'body'  => json_encode([
                            'code'  => 0,
                            'data'  => [
                                'news'  => [
                                    $data->news->seven->id,
                                    $data->news->one->id,
                                ],
                            ]]),
            ],
        ]);
        $response = $this->getJson('v4/news/latest?' . http_build_query([
                    'self'  => true,
                    'userId' => $user->id,
                ]))
            ->assertJson(['code' => 0]);
        $response = json_decode($response->getContent());
        $news = $response->data->latests;
        self::assertCount(2, $news);
        self::assertEquals('news seven desc', $news[0]->desc);
        self::assertEquals('news one desc', $news[1]->desc);
    }

    public function testGetNewsListsSuccess_OtherId_v4(){
        $data = $this->prepareNews();
        $user = $data->user->one;
        $this->mockHttpClient([
            [
                'body'  => json_encode([
                            'code'  => 0,
                            'data'  => [
                                'news'  => [
                                    $data->news->seven->id,
                                    $data->news->one->id,
                                ],
                            ]]),
            ],
            [
                'body'  => json_encode([
                            'code'  => 0,
                            'data'  => [
                                'relation'  => [
                                    $data->user->one->id  => [
                                        'is_following'=> true,
                                        'follow_at'=> '2016-10-01 00:00:00',
                                        'is_follower'=> false,
                                        'followed_at'=> null,
                                    ],
                                    $data->user->five->id   => [
                                        'is_following'=> false,
                                        'follow_at'=> null,
                                        'is_follower'=> false,
                                        'followed_at'=> null,
                                    ],
                                ]
                            ]])
            ],
        ]);
        $response = $this->actingAs($data->user->four)
            ->getJson('v4/news/latest?' . http_build_query([
                'self'  => true,
                'userId' => $user->id,
            ]))
            ->assertJson(['code' => 0]);
        $response = json_decode($response->getContent());
        $news = $response->data->latests;
        self::assertCount(2, $news);
        self::assertEquals('news seven desc', $news[0]->desc);
        self::assertEquals('news one desc', $news[1]->desc);
        self::assertEquals(true, $news[1]->detail->friends->isFollowing);
        self::assertEquals(false, $news[1]->detail->friends->isFollower);

    }

    //=================================
    //       createNews
    //=================================

    public function testCreateNewsSuccess(){

        $counterMock = Mockery::mock(\Illuminate\Contracts\Cache\Store::class);
        $counterMock->shouldReceive('increment')
            ->once()
            ->with('news', 100)
            ->andReturn(100);
        Cache::shouldReceive('driver')
            ->once()
            ->with('counter')
            ->andReturn($counterMock);

        $userOne = factory(\SingPlus\Domains\Users\Models\User::class)->create();
        $profileOne = factory(\SingPlus\Domains\Users\Models\UserProfile::class)->create([
            'user_id'   => $userOne->id,
            'nickname'  => 'zhangsan',
            'gender'    => 'M',
            'avatar'    => 'avatar-one',
        ]);
        $artistOne = factory(\SingPlus\Domains\Musics\Models\Artist::class)->create([
            'name'  => 'SingerOne',
        ]);
        $musicOne = factory(\SingPlus\Domains\Musics\Models\Music::class)->create([
            'name'    => 'musicOne "hell"',
            'lyrics'  => 'music-lyric-one',
            'artists' => [$artistOne->id],
            'covers'  => ['music-one-cover-one', 'music-one-cover-two'],
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
            'status'        => 2,
        ]);

        $response = $this->actingAs($userOne)
            ->postJson('v3/news/create',[
                'workId' => $workOne->id,
                'desc' => 'publish work one news',
                'type' => 'news_publish',
            ])
            ->assertJson(['code' => 0]);

        $this->assertDatabaseHas('news',[
            'type' => 'news_publish',
            'user_id' => $userOne->id,
            'detail' => [
                'work_id' => $workOne->id
            ],
            'desc'   => 'publish work one news',
            'status'  => 1,             // normal
        ]);
    }

    public function testCreateNewsFailed_InvalidaType(){
        $userOne = factory(\SingPlus\Domains\Users\Models\User::class)->create();
        $profileOne = factory(\SingPlus\Domains\Users\Models\UserProfile::class)->create([
            'user_id'   => $userOne->id,
            'nickname'  => 'zhangsan',
            'gender'    => 'M',
            'avatar'    => 'avatar-one',
        ]);
        $artistOne = factory(\SingPlus\Domains\Musics\Models\Artist::class)->create([
            'name'  => 'SingerOne',
        ]);
        $musicOne = factory(\SingPlus\Domains\Musics\Models\Music::class)->create([
            'name'    => 'musicOne "hell"',
            'lyrics'  => 'music-lyric-one',
            'artists' => [$artistOne->id],
            'covers'  => ['music-one-cover-one', 'music-one-cover-two'],
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
            'status'        => 2,
        ]);

        $response = $this->actingAs($userOne)
            ->postJson('v3/news/create',[
                'workId' => $workOne->id,
                'desc' => 'publish work one news',
                'type' => 'news_publ',
            ])
            ->assertJson(['code' => 10602]);
    }

    public function testCreateNewsFailed_WorkNotExist(){
        $userOne = factory(\SingPlus\Domains\Users\Models\User::class)->create();
        $profileOne = factory(\SingPlus\Domains\Users\Models\UserProfile::class)->create([
            'user_id'   => $userOne->id,
            'nickname'  => 'zhangsan',
            'gender'    => 'M',
            'avatar'    => 'avatar-one',
        ]);

        $response = $this->actingAs($userOne)
            ->postJson('v3/news/create',[
                'workId' => '7a3dc9810cb348079d4256dcd53b33eb',
                'desc' => 'publish work one news',
                'type' => 'news_publish',
            ])
            ->assertJson(['code' => 10402]);
    }

    public function testCreateNewsFailed_TooMany(){
        $counterMock = Mockery::mock(\Illuminate\Contracts\Cache\Store::class);
        $counterMock->shouldReceive('increment')
            ->once()
            ->with('news', 100)
            ->andReturn(100);
        Cache::shouldReceive('driver')
            ->once()
            ->with('counter')
            ->andReturn($counterMock);

        $userOne = factory(\SingPlus\Domains\Users\Models\User::class)->create();
        $profileOne = factory(\SingPlus\Domains\Users\Models\UserProfile::class)->create([
            'user_id'   => $userOne->id,
            'nickname'  => 'zhangsan',
            'gender'    => 'M',
            'avatar'    => 'avatar-one',
        ]);
        $artistOne = factory(\SingPlus\Domains\Musics\Models\Artist::class)->create([
            'name'  => 'SingerOne',
        ]);
        $musicOne = factory(\SingPlus\Domains\Musics\Models\Music::class)->create([
            'name'    => 'musicOne "hell"',
            'lyrics'  => 'music-lyric-one',
            'artists' => [$artistOne->id],
            'covers'  => ['music-one-cover-one', 'music-one-cover-two'],
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
            'status'        => 2,
        ]);

        $response = $this->actingAs($userOne)
            ->postJson('v3/news/create',[
                'workId' => $workOne->id,
                'desc' => 'publish work one news',
                'type' => 'news_transmit',
            ])
            ->assertJson(['code' => 0]);

        $this->assertDatabaseHas('news',[
            'type' => 'news_transmit',
            'user_id' => $userOne->id,
            'detail' => [
                'work_id' => $workOne->id
            ],
            'desc'   => 'publish work one news',
            'status'  => 1,             // normal
        ]);

        $response = $this->actingAs($userOne)
            ->postJson('v3/news/create',[
                'workId' => $workOne->id,
                'desc' => 'publish work one news',
                'type' => 'news_transmit',
            ])
            ->assertJson(['code' => 10603]);
        $response = $this->actingAs($userOne)
            ->postJson('v3/news/create',[
                'workId' => $workOne->id,
                'desc' => 'publish work one news',
                'type' => 'news_transmit',
            ])
            ->assertJson(['code' => 10603]);

    }

    private function prepareNews(){
        $userOne = factory(\SingPlus\Domains\Users\Models\User::class)->create();
        $userTwo = factory(\SingPlus\Domains\Users\Models\User::class)->create();
        $userThree = factory(\SingPlus\Domains\Users\Models\User::class)->create();
        $userFour = factory(\SingPlus\Domains\Users\Models\User::class)->create();
        $userFive = factory(\SingPlus\Domains\Users\Models\User::class)->create();

        $profileOne = factory(\SingPlus\Domains\Users\Models\UserProfile::class)->create([
            'user_id'   => $userOne->id,
            'nickname'  => 'zhangsan',
            'gender'    => 'M',
            'avatar'    => 'avatar-one',
        ]);

        $profileTwo = factory(\SingPlus\Domains\Users\Models\UserProfile::class)->create([
            'user_id'   => $userTwo->id,
            'nickname'  => 'Kito',
            'gender'    => 'F',
            'avatar'    => 'avatar-two',
        ]);

        $profileThree = factory(\SingPlus\Domains\Users\Models\UserProfile::class)->create([
            'user_id'   => $userThree->id,
            'nickname'  => 'Poki',
            'gender'    => 'M',
            'avatar'    => 'avatar-three',
        ]);

        $profileFour = factory(\SingPlus\Domains\Users\Models\UserProfile::class)->create([
            'user_id'   => $userFour->id,
            'nickname'  => 'Fooze',
            'gender'    => 'F',
            'avatar'    => 'avatar-four',
        ]);

        $profileFive = factory(\SingPlus\Domains\Users\Models\UserProfile::class)->create([
            'user_id'   => $userFive->id,
            'nickname'  => 'Feisoy',
            'gender'    => 'F',
            'avatar'    => 'avatar-five',
        ]);

        $artistOne = factory(\SingPlus\Domains\Musics\Models\Artist::class)->create([
            'name'  => 'SingerOne',
        ]);

        $artistTwo = factory(\SingPlus\Domains\Musics\Models\Artist::class)->create([
            'name'  => 'SingerTwo',
        ]);

        $musicOne = factory(\SingPlus\Domains\Musics\Models\Music::class)->create([
            'name'    => 'musicOne "hell"',
            'lyrics'  => 'music-lyric-one',
            'artists' => [$artistOne->id, $artistTwo->id],
            'covers'  => ['music-one-cover-one', 'music-one-cover-two'],
        ]);

        $musicTwo = factory(\SingPlus\Domains\Musics\Models\Music::class)->create([
            'name'    => 'musicTwo "Merry.Fat"',
            'lyrics'  => 'music-lyric-two',
            'artists' => [$artistOne->id],
            'covers'  => ['music-two-cover-one', 'music-two-cover-two'],
        ]);

        $musicThree = factory(\SingPlus\Domains\Musics\Models\Music::class)->create([
            'name'    => 'musicThree "Tatanic Ships"',
            'lyrics'  => 'music-lyric-three',
            'artists' => [$artistTwo->id],
            'covers'  => ['music-three-cover-one', 'music-three-cover-two'],
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
            'status'        => 2,
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
            'duration'      => 128,
            'status'        => 2,
        ]);

        $workThree = factory(\SingPlus\Domains\Works\Models\Work::class)->create([
            'user_id'   => $userThree->id,
            'music_id'  => $musicThree->id,
            'cover'     => 'work-cover-three',
            'slides'    => [
                'work-three-one', 'work-three-two',
            ],
            'display_order' => 300,
            'comment_count' => 0,
            'favourite_count' => 1,
            'resource'      => 'work-three',
            'status'        => 2,
            'chorus_type'   => 1,
            'chorus_start_info' => [
                'chorus_count'  => 123,
            ],
            'description'   => 'work three desc',
        ]);

        $workFour = factory(\SingPlus\Domains\Works\Models\Work::class)->create([
            'user_id'   => $userFour->id,
            'music_id'  => $musicThree->id,
            'cover'     => 'work-cover-four',
            'slides'    => [
                'work-four-one', 'work-four-two',
            ],
            'display_order' => 400,
            'comment_count' => 0,
            'favourite_count' => 1,
            'resource'      => 'work-four',
            'status'        => 2,
            'chorus_type'   => 1,
            'chorus_start_info' => [
                'chorus_count'  => 123,
            ],
            'description'   => 'work four desc',
        ]);

        $workFive = factory(\SingPlus\Domains\Works\Models\Work::class)->create([
            'user_id'   => $userFour->id,
            'music_id'  => $musicThree->id,
            'cover'     => 'work-cover-five',
            'slides'    => [
                'work-five-one', 'work-five-two',
            ],
            'display_order' => 500,
            'comment_count' => 0,
            'favourite_count' => 1,
            'resource'      => 'work-five',
            'status'        => 2,
            'chorus_type'   => 1,
            'chorus_start_info' => [
                'chorus_count'  => 123,
            ],
            'description'   => 'work five desc',
        ]);

        $workSix = factory(\SingPlus\Domains\Works\Models\Work::class)->create([
            'user_id'   => $userFour->id,
            'music_id'  => $musicTwo->id,
            'cover'     => 'work-cover-six',
            'slides'    => [
                'work-six-one', 'work-six-two',
            ],
            'display_order' => 600,
            'comment_count' => 0,
            'favourite_count' => 1,
            'resource'      => 'work-six',
            'duration'      => 128,
            'status'        => 2,
        ]);

        $workSeven = factory(\SingPlus\Domains\Works\Models\Work::class)->create([
            'user_id'   => $userFive->id,
            'music_id'  => $musicTwo->id,
            'cover'     => 'work-cover-seven',
            'slides'    => [
                'work-seven-one', 'work-seven-two',
            ],
            'display_order' => 700,
            'comment_count' => 0,
            'favourite_count' => 1,
            'resource'      => 'work-six',
            'duration'      => 128,
            'status'        => 2,
            'chorus_type'   => 10,
            'chorus_join_info' => [
                'origin_work_id'  => $workSix->id,
            ],
            'description'   => 'work five desc',
        ]);

        $newsOne = factory(\SingPlus\Domains\News\Models\News::class)->create([
            'user_id' => $userOne->id,
            'detail' => [
                'work_id' => $workOne->id
            ],
            'desc' => 'news one desc',
            'type' => 'news_publish',
            'status' => 1,
            'display_order' => 100,
        ]);

        $newsTwo = factory(\SingPlus\Domains\News\Models\News::class)->create([
            'user_id' => $userTwo->id,
            'detail' => [
                'work_id' => $workTwo->id
            ],
            'desc' => 'news two desc',
            'type' => 'news_transmit',
            'status' => 1,
            'display_order' => 200,
        ]);

        $newsThree = factory(\SingPlus\Domains\News\Models\News::class)->create([
            'user_id' => $userThree->id,
            'detail' => [
                'work_id' => $workThree->id
            ],
            'desc' => 'news three desc',
            'type' => 'news_transmit',
            'status' => 1,
            'display_order' => 300,
        ]);

        $newsFour = factory(\SingPlus\Domains\News\Models\News::class)->create([
            'user_id' => $userFour->id,
            'detail' => [
                'work_id' => $workFour->id
            ],
            'desc' => 'news four desc',
            'type' => 'news_publish',
            'status' => 1,
            'display_order' => 400,
        ]);

        $newsFive = factory(\SingPlus\Domains\News\Models\News::class)->create([
            'user_id' => $userFour->id,
            'detail' => [
                'work_id' => $workFive->id
            ],
            'desc' => 'news five desc',
            'type' => 'news_publish',
            'status' => 0,
            'display_order' => 500,
        ]);

        $newsSix = factory(\SingPlus\Domains\News\Models\News::class)->create([
            'user_id' => $userFour->id,
            'detail' => [
                'work_id' => $workSix->id
            ],
            'desc' => 'news six desc',
            'type' => 'news_publish',
            'status' => 1,
            'display_order' => 600,
        ]);

        $newsSeven = factory(\SingPlus\Domains\News\Models\News::class)->create([
            'user_id' => $userOne->id,
            'detail' => [
                'work_id' => $workSeven->id
            ],
            'desc' => 'news seven desc',
            'type' => 'news_transmit',
            'status' => 1,
            'display_order' => 700,
        ]);

        $userOneFollowing = factory(\SingPlus\Domains\Friends\Models\UserFollowing::class)->create([
            'user_id'     => $userOne->id,
            'followings'  => [
                $userFive->id
            ],
            'following_details' => [
                [
                    'user_id'   => $userFive->id,
                    'follow_at' => \Carbon\Carbon::parse('2016-10-01')->getTimestamp()
                ],
            ],
            'display_order' => 300,
        ]);

        $userFourFollowing = factory(\SingPlus\Domains\Friends\Models\UserFollowing::class)->create([
            'user_id'     => $userFour->id,
            'followings'  => [
                $userOne->id, $userTwo->id,$userThree->id
            ],
            'following_details' => [
                [
                    'user_id'   => $userOne->id,
                    'follow_at' => \Carbon\Carbon::parse('2016-10-01')->getTimestamp()
                ],
                [
                    'user_id'   => $userTwo->id,
                    'follow_at' => \Carbon\Carbon::parse('2017-10-01')->getTimestamp()
                ],
                [
                    'user_id'   => $userThree->id,
                    'follow_at' => \Carbon\Carbon::parse('2018-01-01')->getTimestamp()
                ],
            ],
            'display_order' => 300,
        ]);


        return (object)[
            'user' => (object)[
                'one' => $userOne,
                'two' => $userTwo,
                'three' => $userThree,
                'four' => $userFour,
                'five' => $userFive,
            ],
            'user_profile' => (object)[
                'one' => $profileOne,
                'two' => $profileTwo,
                'three' => $profileThree,
                'four' => $profileFour,
                'five' => $profileFive,
            ],
            'music'  => (object)[
                'one' => $musicOne,
                'two' => $musicTwo,
                'three' => $musicThree,
            ] ,
            'work' => (object)[
                'one' => $workOne,
                'two' => $workTwo,
                'three' => $workThree,
                'four' => $workFour,
                'five' => $workFive,
                'six' => $workSix,
                'seven' => $workSeven,
            ],
            'news' => (object)[
                'one' => $newsOne,
                'two' => $newsTwo,
                'three' => $newsThree,
                'four' => $newsFour,
                'five' => $newsFive,
                'six'  => $newsSix,
                'seven' => $newsSeven,
            ]
        ];
    }

  /** 
   * mock http client and response
   * 模拟http响应
   *
   * Usage: $this->mockHttpClient([
   *            [
   *                'body'  => json_encode([
   *                        'code' => 0,
   *                        'data' => [],
   *                        'message'   => 'ok',
   *                    ]),
   *            ]])
   *
   * @param string $respBody 模拟响应body
   * @param int $respCode 模拟响应http status
   * @param array $respHeader 模拟响应http header
   */
  protected function mockHttpClient(array $respArr = [])
  {
    $mock = new MockHandler();
    foreach ($respArr as $resp) {
        $mock->append(new Response(
            array_get($resp, 'code', 200),
            array_get($resp, 'header', []),
            array_get($resp, 'body')
        ));
    }

    $handler = HandlerStack::create($mock);

    $this->app[\GuzzleHttp\ClientInterface::class] = new Client(['handler' => $handler]);
  }
}
