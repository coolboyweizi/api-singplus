<?php
/**
 * Created by PhpStorm.
 * User: karl
 * Date: 2018/3/22
 * Time: 下午5:33
 */

namespace FeatureTest\SingPlus\Controllers;

use Cache;
use Mockery;
use FeatureTest\SingPlus\MongodbClearTrait;
use FeatureTest\SingPlus\TestCase;
use SingPlus\Exceptions\ExceptionCode;

class WorkTagControllerTest extends TestCase
{
    use MongodbClearTrait;

    //=================================
    //         searchWorkTags
    //=================================
    public function testSearchWorkTagSuccess_WithForcedSearch(){
       $this->prepareWorkTags();

       $response = $this->getJson('v3/works/search-tags?'.http_build_query([
                'tag' => 'love',
                'force' => true,
           ]));

       $response = json_decode($response->getContent());
       $data = $response->data;
       self::assertCount(4, $data->tags);
       self::assertEquals('love', $data->tags[0]->title);
       self::assertEquals('Love', $data->tags[1]->title);
       self::assertEquals('Loveass', $data->tags[2]->title);
       self::assertEquals('lovelys', $data->tags[3]->title);

       $cacheKey = sprintf('searchWorkTags:%s:work:tags', 'love');
       $cacheData = Cache::get($cacheKey);
       self::assertCount(4, $cacheData);
       self::assertTrue($cacheData->where('title', 'love') != null);
       self::assertTrue($cacheData->where('title', 'Love') != null);
       self::assertTrue($cacheData->where('title', 'Loveass') != null);
       self::assertTrue($cacheData->where('title', 'lovelys') != null);
    }

    public function testSearchWorkTagSuccess_WithNotForcedSearchAndNoCache(){
        $this->prepareWorkTags();

        $response = $this->getJson('v3/works/search-tags?'.http_build_query([
                'tag' => 'love',
            ]));
        $response = json_decode($response->getContent());
        $data = $response->data;
        self::assertCount(4, $data->tags);
        self::assertEquals('love', $data->tags[0]->title);
        self::assertEquals('Love', $data->tags[1]->title);
        self::assertEquals('Loveass', $data->tags[2]->title);
        self::assertEquals('lovelys', $data->tags[3]->title);

        $cacheKey = sprintf('searchWorkTags:%s:work:tags', 'love');
        $cacheData = Cache::get($cacheKey);
        self::assertCount(4, $cacheData);
        self::assertTrue($cacheData->where('title', 'love') != null);
        self::assertTrue($cacheData->where('title', 'Love') != null);
        self::assertTrue($cacheData->where('title', 'Loveass') != null);
        self::assertTrue($cacheData->where('title', 'lovelys') != null);
    }

    public function testSearchWorkTagSuccess_WithNotForcedSearchAndHasCache(){
        $this->prepareWorkTags();
        $cacheData = collect([
            (object)[
                'title' => 'love'
            ],
            (object)[
                'title' => 'Love'
            ]
        ]);
        $cacheKey = sprintf('searchWorkTags:%s:work:tags', 'love');
        $expireAfter = 5;
        Cache::put($cacheKey, $cacheData, $expireAfter);

        $response = $this->getJson('v3/works/search-tags?'.http_build_query([
                'tag' => 'love',
                'force' => false,
            ]));
        $response = json_decode($response->getContent());
        $data = $response->data;
        self::assertCount(2, $data->tags);
        self::assertEquals('love', $data->tags[0]->title);
        self::assertEquals('Love', $data->tags[1]->title);
    }

    public function testSearchWorkTagSuccess_WithNotForcedAndCacheExpired(){
        $this->prepareWorkTags();
        $cacheData = collect([
            (object)[
                'title' => 'love'
            ],
            (object)[
                'title' => 'Love'
            ]
        ]);
        $cacheKey = sprintf('searchWorkTags:%s:work:tags', 'love');
        $expireAfter = 0;
        Cache::put($cacheKey, $cacheData, $expireAfter);

        $response = $this->getJson('v3/works/search-tags?'.http_build_query([
                'tag' => 'love',
                'force' => false,
            ]));
        $response = json_decode($response->getContent());
        $data = $response->data;
        self::assertCount(4, $data->tags);
        self::assertEquals('love', $data->tags[0]->title);
        self::assertEquals('Love', $data->tags[1]->title);
        self::assertEquals('Loveass', $data->tags[2]->title);
        self::assertEquals('lovelys', $data->tags[3]->title);

        $cacheKey = sprintf('searchWorkTags:%s:work:tags', 'love');
        $cacheData = Cache::get($cacheKey);
        self::assertCount(4, $cacheData);
        self::assertTrue($cacheData->where('title', 'love') != null);
        self::assertTrue($cacheData->where('title', 'Love') != null);
        self::assertTrue($cacheData->where('title', 'Loveass') != null);
        self::assertTrue($cacheData->where('title', 'lovelys') != null);
    }

    public function testSearchWorkTagSuccess_WithEmptyResult(){
        $this->prepareWorkTags();

        $response = $this->getJson('v3/works/search-tags?'.http_build_query([
                'tag' => 'a',
                'force' => true,
            ]));

        $response = json_decode($response->getContent());
        $data = $response->data;
        self::assertCount(0, $data->tags);
        $cacheKey = sprintf('searchWorkTags:%s:work:tags', 'a');
        $cacheData = Cache::get($cacheKey);
        self::assertNull($cacheData);
    }

    //=================================
    //         workTagDetail
    //=================================
    public function testWorkTagDetailSuccess(){
        factory(\SingPlus\Domains\Works\Models\WorkTag::class)->create([
            'title' => 'World',
            'description'  => '1.dsaa',
            'cover' => 'tags/cover_1.png',
            'join_count' => 10
        ]);
        $storageService = $this->mockStorage();
        $storageService->shouldReceive('toHttpUrl')
            ->once()
            ->with('tags/cover_1.png')
            ->andReturn('https://singplus/work/tags/cover_1.png');
        $response = $this->getJson('v3/works/tag-info?'.http_build_query([
                'tag' => 'World',
            ]))->assertJson(['code' => 0]);
        $response = json_decode($response->getContent());
        $data = $response->data;
        self::assertEquals(10, $data->joinCount);
        self::assertEquals('World', $data->title);
        self::assertEquals('https://singplus/work/tags/cover_1.png', $data->cover);
        self::assertEquals('1.dsaa', $data->desc);
    }

    public function testWorkTagDetailSuccess_WithTitleOnly(){
        $this->prepareWorkTags();
        $storageService = $this->mockStorage();
        $storageService->shouldReceive('toHttpUrl')
            ->never()
            ->andReturn('https://singplus/work/cover_placeholder.png');
        $response = $this->getJson('v3/works/tag-info?'.http_build_query([
                'tag' => 'lovee'
            ]))->assertJson(['code' => 0]);
        $response = json_decode($response->getContent());
        $data = $response->data;
        self::assertEquals(0, $data->joinCount);
        self::assertEquals('lovee', $data->title);
        self::assertTrue(object_get($data, 'cover') == null);
        self::assertTrue(object_get($data, 'desc') == null);
    }

    public function testWorkTagDetailFailed_TagNotExists(){
        $this->prepareWorkTags();
        $storageService = $this->mockStorage();
        $storageService->shouldReceive('toHttpUrl')
            ->never()
            ->andReturn('https://singplus/work/cover_placeholder.png');
        $response = $this->getJson('v3/works/tag-info?'.http_build_query([
                'tag' => 'aaa'
            ]))->assertJson(['code' => ExceptionCode::WORK_TAG_NOT_EXISTS]);
    }

    public function testWorkTagDetailSuccess_WithJoinCountCaseSensitiveFalse(){
        factory(\SingPlus\Domains\Works\Models\WorkTag::class)->create([
            'title' => 'World',
            'description'  => '1.dsaa',
            'cover' => 'tags/cover_1.png',
            'join_count' => 10
        ]);

        factory(\SingPlus\Domains\Works\Models\WorkTag::class)->create([
            'title' => 'WoRld',
            'description'  => '1.dsaa',
            'cover' => 'tags/cover_1.png',
            'join_count' => 10
        ]);

        $storageService = $this->mockStorage();
        $storageService->shouldReceive('toHttpUrl')
            ->once()
            ->with('tags/cover_1.png')
            ->andReturn('https://singplus/work/tags/cover_1.png');
        $response = $this->getJson('v3/works/tag-info?'.http_build_query([
                'tag' => 'World',
            ]))->assertJson(['code' => 0]);
        $response = json_decode($response->getContent());
        $data = $response->data;
        self::assertEquals(20, $data->joinCount);
        self::assertEquals('World', $data->title);
        self::assertEquals('https://singplus/work/tags/cover_1.png', $data->cover);
        self::assertEquals('1.dsaa', $data->desc);
        $cacheKey = sprintf("workTag:joincount:%s", 'WORLD');
        self::assertTrue(20 == Cache::get($cacheKey));
    }

    public function testWorkTagDetailSuccess_WithJoinCountCaseSensitiveFalseHasCache(){
        factory(\SingPlus\Domains\Works\Models\WorkTag::class)->create([
            'title' => 'World',
            'description'  => '1.dsaa',
            'cover' => 'tags/cover_1.png',
            'join_count' => 10
        ]);

        $cacheKey = sprintf("workTag:joincount:%s", 'WORLD');
        $expired = 24*60*2;
        Cache::put($cacheKey, 100,$expired);
        $storageService = $this->mockStorage();
        $storageService->shouldReceive('toHttpUrl')
            ->once()
            ->with('tags/cover_1.png')
            ->andReturn('https://singplus/work/tags/cover_1.png');
        $response = $this->getJson('v3/works/tag-info?'.http_build_query([
                'tag' => 'World',
            ]))->assertJson(['code' => 0]);
        $response = json_decode($response->getContent());
        $data = $response->data;
        self::assertEquals(100, $data->joinCount);
        self::assertEquals('World', $data->title);
        self::assertEquals('https://singplus/work/tags/cover_1.png', $data->cover);
        self::assertEquals('1.dsaa', $data->desc);
    }

    //=================================
    //         latestWorkList
    //=================================
    public function testGetLatestWorkListSuccess(){
        $this->prepareWorkWithTags();
        $response = $this->getJson('v3/works/tag/latest?'.http_build_query([
                'tag' => 'love'
            ]))->assertJson(['code' => 0]);
        $response = json_decode($response->getContent());
        $data = $response->data;
        self::assertCount(5, $data->works);
        self::assertEquals('work five', $data->works[0]->musicName);
        self::assertEquals('work four', $data->works[1]->musicName);
        self::assertEquals('work three', $data->works[2]->musicName);
        self::assertEquals('Sing Koiy', $data->works[3]->musicName);
        self::assertEquals('work one', $data->works[4]->musicName);
    }

    public function testGetLatestWorkListSuccess_ForPagination(){
        $works = $this->prepareWorkWithTags();
        $response = $this->getJson('v3/works/tag/latest?'.http_build_query([
                'tag' => 'love',
                'id' => $works->three->id
            ]))->assertJson(['code' => 0]);
        $response = json_decode($response->getContent());
        $data = $response->data;
        self::assertCount(2, $data->works);
        self::assertEquals('Sing Koiy', $data->works[0]->musicName);
        self::assertEquals('work one', $data->works[1]->musicName);
    }

    public function testGetLatestWorkListFailed_WithWorkNotExists(){
        $user = factory(\SingPlus\Domains\Users\Models\User::class)->create();

        $musicOne = factory(\SingPlus\Domains\Musics\Models\Music::class)->create([
            'name' => 'Sing Woky'
        ]);
        $work = factory(\SingPlus\Domains\Works\Models\Work::class)->create([
            'user_id' => $user->id,
            'name' => 'work one',
            'music_id' => $musicOne->id,
            'work_tags' => [
                'love'
            ],
            'display_order' => 100
        ]);
        $response = $this->getJson('v3/works/tag/latest?'.http_build_query([
                'tag' => 'love',
            ]))->assertJson(['code' => 0]);
        $response = json_decode($response->getContent());
        $data = $response->data;
        self::assertCount(0, $data->works);
    }

    public function testGetLatestWorkListFailed_WithMusicNotExist(){
        $user = factory(\SingPlus\Domains\Users\Models\User::class)->create();
        $userProfile = factory(\SingPlus\Domains\Users\Models\UserProfile::class)->create([
            'user_id' => $user->id,
            'avatar' => 'avatar.png',
            'nickname' => 'Zhen'
        ]);
        $work = factory(\SingPlus\Domains\Works\Models\Work::class)->create([
            'user_id' => $user->id,
            'name' => 'work one',
            'music_id' => str_random(12),
            'work_tags' => [
                'love'
            ],
            'display_order' => 100
        ]);
        $response = $this->getJson('v3/works/tag/latest?'.http_build_query([
                'tag' => 'love',
            ]))->assertJson(['code' => 0]);
        $response = json_decode($response->getContent());
        $data = $response->data;
        self::assertCount(0, $data->works);
    }

    public function testGetLatestWorkListSuccess_WithoutCaseSensitive(){
        $user = factory(\SingPlus\Domains\Users\Models\User::class)->create();
        $userProfile = factory(\SingPlus\Domains\Users\Models\UserProfile::class)->create([
            'user_id' => $user->id,
            'avatar' => 'avatar.png',
            'nickname' => 'Zhen'
        ]);
        $musicOne = factory(\SingPlus\Domains\Musics\Models\Music::class)->create([
            'name' => 'Sing Woky'
        ]);
        $musicTwo = factory(\SingPlus\Domains\Musics\Models\Music::class)->create([
            'name' => 'Sing Koiy'
        ]);
        $workOne = factory(\SingPlus\Domains\Works\Models\Work::class)->create([
            'user_id' => $user->id,
            'name' => 'work one',
            'music_id' => $musicOne,
            'work_tags' => [
                'LoVe',
                'LoveSing'
            ],
            'display_order' => 100,
        ]);

        $workTwo = factory(\SingPlus\Domains\Works\Models\Work::class)->create([
            'user_id' => $user->id,
            'music_id' => $musicTwo,
            'work_tags' => [
                'love',
                'LoveWorld'
            ],
            'display_order' => 200,
        ]);

        $response = $this->getJson('v3/works/tag/latest?'.http_build_query([
                'tag' => 'love',
            ]))->assertJson(['code' => 0]);

        $response = json_decode($response->getContent());
        $data = $response->data;
        self::assertCount(2, $data->works);
    }

    //=================================
    //         tagWorkSelectionList
    //=================================
    public function testTagWorkSelectionListSuccess(){
        $selections = $this->prepareTagWorkSelection();
        $response = $this->getJson('v3/works/tag/selection?'.http_build_query([
                'tag' => 'love'
            ]))->assertJson(['code' => 0]);
        $response = json_decode($response->getContent());
        $data = $response->data;
        self::assertCount(5, $data->works);
        self::assertEquals($selections->seven->work_id, $data->works[0]->workId);
        self::assertEquals($selections->six->work_id, $data->works[1]->workId);
        self::assertEquals($selections->five->work_id, $data->works[2]->workId);
        self::assertEquals($selections->three->work_id, $data->works[3]->workId);
        self::assertEquals($selections->one->work_id, $data->works[4]->workId);
    }

    public function testTagWorkSelectionListSuccess_ForPagination(){
        $selections = $this->prepareTagWorkSelection();
        $response = $this->getJson('v3/works/tag/selection?'.http_build_query([
                'tag' => 'love',
                'id'  => $selections->five->work_id
            ]))->assertJson(['code' => 0]);
        $response = json_decode($response->getContent());
        $data = $response->data;
        self::assertCount(2, $data->works);
        self::assertEquals($selections->three->work_id, $data->works[0]->workId);
        self::assertEquals($selections->one->work_id, $data->works[1]->workId);
    }

    public function testTagWorkSelectionListFailed_WithWorkNotExists(){
        $user = factory(\SingPlus\Domains\Users\Models\User::class)->create();
        $musicOne = factory(\SingPlus\Domains\Musics\Models\Music::class)->create([
            'name' => 'Sing Woky'
        ]);
        $work = factory(\SingPlus\Domains\Works\Models\Work::class)->create([
            'user_id' => $user->id,
            'name' => 'work one',
            'music_id' => $musicOne->id,
            'work_tags' => [
                'love'
            ],
            'display_order' => 100
        ]);

        $selection = factory(\SingPlus\Domains\Works\Models\TagWorkSelection::class)->create([
            'work_id' => $work->id,
            'work_tag' => 'love',
            'display_order' => 100
        ]);

        $response = $this->getJson('v3/works/tag/selection?'.http_build_query([
                'tag' => 'love',
            ]))->assertJson(['code' => 0]);
        $response = json_decode($response->getContent());
        $data = $response->data;
        self::assertCount(0, $data->works);
    }

    public function testTagWorkSelectionListFailed_WithMusicNotExists(){
        $user = factory(\SingPlus\Domains\Users\Models\User::class)->create();
        $userProfile = factory(\SingPlus\Domains\Users\Models\UserProfile::class)->create([
            'user_id' => $user->id,
            'avatar' => 'avatar.png',
            'nickname' => 'Zhen'
        ]);
        $work = factory(\SingPlus\Domains\Works\Models\Work::class)->create([
            'user_id' => $user->id,
            'name' => 'work one',
            'music_id' => str_random(12),
            'work_tags' => [
                'love'
            ],
            'display_order' => 100
        ]);

        $selection = factory(\SingPlus\Domains\Works\Models\TagWorkSelection::class)->create([
            'work_id' => $work->id,
            'work_tag' => 'love',
            'display_order' => 100
        ]);

        $response = $this->getJson('v3/works/tag/selection?'.http_build_query([
                'tag' => 'love',
            ]))->assertJson(['code' => 0]);
        $response = json_decode($response->getContent());
        $data = $response->data;
        self::assertCount(0, $data->works);
    }

    private function prepareTagWorkSelection(){
        $works = $this->prepareWorkWithTags();
        $selectionOne = factory(\SingPlus\Domains\Works\Models\TagWorkSelection::class)->create([
            'work_id' => $works->one->id,
            'work_tag' => 'love',
            'display_order' => 100
        ]);

        $selectionTwo = factory(\SingPlus\Domains\Works\Models\TagWorkSelection::class)->create([
            'work_id' => $works->one->id,
            'work_tag' => 'LoveSing',
            'display_order' => 200
        ]);

        $selectionThree = factory(\SingPlus\Domains\Works\Models\TagWorkSelection::class)->create([
            'work_id' => $works->two->id,
            'work_tag' => 'love',
            'display_order' => 300
        ]);

        $selectionFour = factory(\SingPlus\Domains\Works\Models\TagWorkSelection::class)->create([
            'work_id' => $works->two->id,
            'work_tag' => 'LoveWorld',
            'display_order' => 400
        ]);
        $selectionFive = factory(\SingPlus\Domains\Works\Models\TagWorkSelection::class)->create([
            'work_id' => $works->three->id,
            'work_tag' => 'love',
            'display_order' => 500
        ]);
        $selectionSix = factory(\SingPlus\Domains\Works\Models\TagWorkSelection::class)->create([
            'work_id' => $works->four->id,
            'work_tag' => 'love',
            'display_order' => 600
        ]);

        $selectionSeven = factory(\SingPlus\Domains\Works\Models\TagWorkSelection::class)->create([
            'work_id' => $works->five->id,
            'work_tag' => 'love',
            'display_order' => 700
        ]);

        $selectionEight = factory(\SingPlus\Domains\Works\Models\TagWorkSelection::class)->create([
            'work_id' => $works->six->id,
            'work_tag' => 'world',
            'display_order' => 800
        ]);

        return (object)[
            'one' => $selectionOne,
            'two' => $selectionTwo,
            'three' => $selectionThree,
            'four' => $selectionFour,
            'five' => $selectionFive,
            'six' => $selectionSix,
            'seven' => $selectionSeven,
            'eight' => $selectionEight
        ];

    }

    private function prepareWorkWithTags(){
        $user = factory(\SingPlus\Domains\Users\Models\User::class)->create();
        $userProfile = factory(\SingPlus\Domains\Users\Models\UserProfile::class)->create([
            'user_id' => $user->id,
            'avatar' => 'avatar.png',
            'nickname' => 'Zhen'
        ]);
        $musicOne = factory(\SingPlus\Domains\Musics\Models\Music::class)->create([
            'name' => 'Sing Woky'
        ]);
        $musicTwo = factory(\SingPlus\Domains\Musics\Models\Music::class)->create([
            'name' => 'Sing Koiy'
        ]);
        $workOne = factory(\SingPlus\Domains\Works\Models\Work::class)->create([
            'user_id' => $user->id,
            'name' => 'work one',
            'music_id' => $musicOne,
            'work_tags' => [
                'love',
                'LoveSing'
            ],
            'display_order' => 100,
        ]);

        $workTwo = factory(\SingPlus\Domains\Works\Models\Work::class)->create([
            'user_id' => $user->id,
            'music_id' => $musicTwo,
            'work_tags' => [
                'love',
                'LoveWorld'
            ],
            'display_order' => 200,
        ]);

        $workThree = factory(\SingPlus\Domains\Works\Models\Work::class)->create([
            'user_id' => $user->id,
            'music_id' => $musicTwo,
            'name' => 'work three',
            'work_tags' => [
                'love',
            ],
            'display_order' => 300,
        ]);


        $workFour = factory(\SingPlus\Domains\Works\Models\Work::class)->create([
            'user_id' => $user->id,
            'music_id' => $musicTwo,
            'name' => 'work four',
            'work_tags' => [
                'love',
            ],
            'display_order' => 400,
        ]);

        $workFive = factory(\SingPlus\Domains\Works\Models\Work::class)->create([
            'user_id' => $user->id,
            'music_id' => $musicTwo,
            'name' => 'work five',
            'work_tags' => [
                'love',
            ],
            'display_order' => 500,
        ]);

        $workSix = factory(\SingPlus\Domains\Works\Models\Work::class)->create([
            'user_id' => $user->id,
            'music_id' => $musicTwo,
            'name' => 'work five',
            'work_tags' => [
                'world',
            ],
            'display_order' => 600,
        ]);

        return (object)[
            'one' => $workOne,
            'two' => $workTwo,
            'three' => $workThree,
            'four' => $workFour,
            'five' => $workFive,
            'six' => $workSix
        ];
    }



    private function mockStorage()
    {
        $storageService = Mockery::mock(\SingPlus\Contracts\Storages\Services\StorageService::class);
        $this->app[\SingPlus\Contracts\Storages\Services\StorageService::class ] = $storageService;

        return $storageService;
    }

    private function prepareWorkTags(){
        factory(\SingPlus\Domains\Works\Models\WorkTag::class)->create([
            'title' => 'lovee',
            'source' => 'user'
        ]);

        factory(\SingPlus\Domains\Works\Models\WorkTag::class)->create([
            'title' => 'love'
        ]);

        factory(\SingPlus\Domains\Works\Models\WorkTag::class)->create([
            'title' => 'lovelys',
            'source' => 'official'
        ]);

        factory(\SingPlus\Domains\Works\Models\WorkTag::class)->create([
            'title' => 'lovsssss'
        ]);

        factory(\SingPlus\Domains\Works\Models\WorkTag::class)->create([
            'title' => 'Love'
        ]);

        factory(\SingPlus\Domains\Works\Models\WorkTag::class)->create([
            'title' => 'Loveass',
            'source' => 'official'
        ]);
    }


}