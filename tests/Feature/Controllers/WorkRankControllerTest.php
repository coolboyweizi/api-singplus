<?php

namespace FeatureTest\SingPlus\Controllers;

use Cache;
use Mockery;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Location;
use FeatureTest\SingPlus\TestCase;
use FeatureTest\SingPlus\MongodbClearTrait;

class WorkRankControllerTest extends TestCase
{
    use MongodbClearTrait;

    //=================================
    //      getGlobal
    //=================================
    public function testGetGlobalSuccess()
    {
        $user = factory(\SingPlus\Domains\Users\Models\User::class)->create();
        factory(\SingPlus\Domains\Users\Models\UserProfile::class)->create([
            'user_id' => $user->id,
        ]);
        $data = $this->prepareRanks();

        $musicOne = $data->musics->one;
        $musicOne->status = -1;
        $musicOne->save();

        $response = $this->actingAs($user)
            ->getJson('v3/works/ranks/global')
            ->assertJson(['code' => 0]);

        $ranks = (json_decode($response->getContent()))->data->ranks;

        self::assertCount(2, $ranks);
        self::assertEquals($data->ranks->one->id, $ranks[0]->id);   // order by rank
        self::assertEquals($data->ranks->one->work_id, $ranks[0]->workId);
        self::assertEquals('user-one', $ranks[0]->nickname);
        self::assertTrue(ends_with($ranks[0]->avatar, 'user-one-avatar'));
        self::assertEquals('music-one', $ranks[0]->musicName);
        self::assertEquals($data->ranks->four->id, $ranks[1]->id);
    }

    public function testGetGlobalSuccess_UserNotExists()
    {
        $user = factory(\SingPlus\Domains\Users\Models\User::class)->create();
        factory(\SingPlus\Domains\Users\Models\UserProfile::class)->create([
            'user_id' => $user->id,
        ]);
        $data = $this->prepareRanks();
        $data->works->four->user_id = '573d283e7fe54c0c8f09716d71e0f0cc';
        $data->works->four->save();

        $response = $this->actingAs($user)
            ->getJson('v3/works/ranks/global')
            ->assertJson(['code' => 0]);

        $ranks = (json_decode($response->getContent()))->data->ranks;

        self::assertCount(1, $ranks);
        self::assertEquals($data->ranks->one->id, $ranks[0]->id);   // order by rank
        self::assertEquals($data->ranks->one->work_id, $ranks[0]->workId);
        self::assertEquals('user-one', $ranks[0]->nickname);
        self::assertTrue(ends_with($ranks[0]->avatar, 'user-one-avatar'));
        self::assertEquals('music-one', $ranks[0]->musicName);
    }

    public function testGetGlobalSuccess_WorkNotExists()
    {
        $user = factory(\SingPlus\Domains\Users\Models\User::class)->create();
        factory(\SingPlus\Domains\Users\Models\UserProfile::class)->create([
            'user_id' => $user->id,
        ]);
        $data = $this->prepareRanks();
        $data->ranks->four->work_id = '573d283e7fe54c0c8f09716d71e0f0cc';
        $data->ranks->four->save();

        $response = $this->actingAs($user)
            ->getJson('v3/works/ranks/global')
            ->assertJson(['code' => 0]);

        $ranks = (json_decode($response->getContent()))->data->ranks;

        self::assertCount(1, $ranks);
        self::assertEquals($data->ranks->one->id, $ranks[0]->id);   // order by rank
        self::assertEquals($data->ranks->one->work_id, $ranks[0]->workId);
        self::assertEquals('user-one', $ranks[0]->nickname);
        self::assertTrue(ends_with($ranks[0]->avatar, 'user-one-avatar'));
        self::assertEquals('music-one', $ranks[0]->musicName);
    }

    public function testGetGlobalSuccess_WithoutLogin()
    {
        $user = factory(\SingPlus\Domains\Users\Models\User::class)->create();
        factory(\SingPlus\Domains\Users\Models\UserProfile::class)->create([
            'user_id' => $user->id,
        ]);
        $data = $this->prepareRanks();

        $response = $this->getJson('v3/works/ranks/global')
            ->assertJson(['code' => 0]);

        $ranks = (json_decode($response->getContent()))->data->ranks;

        self::assertCount(2, $ranks);
        self::assertEquals($data->ranks->one->id, $ranks[0]->id);   // order by rank
        self::assertEquals($data->ranks->one->work_id, $ranks[0]->workId);
        self::assertEquals('user-one', $ranks[0]->nickname);
        self::assertTrue(ends_with($ranks[0]->avatar, 'user-one-avatar'));
        self::assertEquals('music-one', $ranks[0]->musicName);
        self::assertEquals($data->ranks->four->id, $ranks[1]->id);
    }

    public function testGetGlobalSuccess_UserNotExists_WithoutLogin()
    {
        $data = $this->prepareRanks();
        $data->works->four->user_id = '573d283e7fe54c0c8f09716d71e0f0cc';
        $data->works->four->save();

        $response = $this->getJson('v3/works/ranks/global')
            ->assertJson(['code' => 0]);

        $ranks = (json_decode($response->getContent()))->data->ranks;

        self::assertCount(1, $ranks);
        self::assertEquals($data->ranks->one->id, $ranks[0]->id);   // order by rank
        self::assertEquals($data->ranks->one->work_id, $ranks[0]->workId);
        self::assertEquals('user-one', $ranks[0]->nickname);
        self::assertTrue(ends_with($ranks[0]->avatar, 'user-one-avatar'));
        self::assertEquals('music-one', $ranks[0]->musicName);
    }

    public function testGetGlobalSuccess_WorkNotExists_WithoutLogin()
    {

        $data = $this->prepareRanks();
        $data->ranks->four->work_id = '573d283e7fe54c0c8f09716d71e0f0cc';
        $data->ranks->four->save();

        $response = $this->getJson('v3/works/ranks/global')
            ->assertJson(['code' => 0]);

        $ranks = (json_decode($response->getContent()))->data->ranks;

        self::assertCount(1, $ranks);
        self::assertEquals($data->ranks->one->id, $ranks[0]->id);   // order by rank
        self::assertEquals($data->ranks->one->work_id, $ranks[0]->workId);
        self::assertEquals('user-one', $ranks[0]->nickname);
        self::assertTrue(ends_with($ranks[0]->avatar, 'user-one-avatar'));
        self::assertEquals('music-one', $ranks[0]->musicName);
    }

    //=================================
    //      getCountry
    //=================================
    public function testGetCountrySuccess()
    {
        $user = factory(\SingPlus\Domains\Users\Models\User::class)->create();
        factory(\SingPlus\Domains\Users\Models\UserProfile::class)->create([
            'user_id' => $user->id,
        ]);
        $data = $this->prepareRanks();

        $response = $this->actingAs($user)
            ->getJson('v3/works/ranks/country', [
                'X-CountryAbbr' => 'NE',
            ])
            ->assertJson(['code' => 0]);

        $ranks = (json_decode($response->getContent()))->data->ranks;

        self::assertCount(2, $ranks);
        self::assertEquals($data->ranks->one->id, $ranks[0]->id);   // order by rank
        self::assertEquals($data->ranks->one->work_id, $ranks[0]->workId);
        self::assertEquals('user-one', $ranks[0]->nickname);
        self::assertTrue(ends_with($ranks[0]->avatar, 'user-one-avatar'));
        self::assertEquals('music-one', $ranks[0]->musicName);
        self::assertEquals($data->ranks->two->id, $ranks[1]->id);
    }

    public function testGetCountrySuccess_WithoutLogin()
    {
        $data = $this->prepareRanks();

        $response = $this->getJson('v3/works/ranks/country', [
            'X-CountryAbbr' => 'NE',
        ])
            ->assertJson(['code' => 0]);

        $ranks = (json_decode($response->getContent()))->data->ranks;

        self::assertCount(2, $ranks);
        self::assertEquals($data->ranks->one->id, $ranks[0]->id);   // order by rank
        self::assertEquals($data->ranks->one->work_id, $ranks[0]->workId);
        self::assertEquals('user-one', $ranks[0]->nickname);
        self::assertTrue(ends_with($ranks[0]->avatar, 'user-one-avatar'));
        self::assertEquals('music-one', $ranks[0]->musicName);
        self::assertEquals($data->ranks->two->id, $ranks[1]->id);
    }
    //=================================
    //      getRookie
    //=================================
    public function testGetRookieSuccess()
    {
        $user = factory(\SingPlus\Domains\Users\Models\User::class)->create();
        factory(\SingPlus\Domains\Users\Models\UserProfile::class)->create([
            'user_id' => $user->id,
        ]);
        $data = $this->prepareRanks();

        $response = $this->actingAs($user)
            ->getJson('v3/works/ranks/new-personality')
            ->assertJson(['code' => 0]);

        $ranks = (json_decode($response->getContent()))->data->ranks;

        self::assertCount(2, $ranks);
        self::assertEquals($data->ranks->three->id, $ranks[0]->id);   // order by rank
        self::assertEquals($data->ranks->three->work_id, $ranks[0]->workId);
        self::assertEquals('user-three', $ranks[0]->nickname);
        self::assertTrue(ends_with($ranks[0]->avatar, 'user-three-avatar'));
        self::assertEquals('music-one', $ranks[0]->musicName);
        self::assertEquals($data->ranks->four->id, $ranks[1]->id);
    }

    public function testGetRookieSuccess_WithoutLogin()
    {

        $data = $this->prepareRanks();

        $response = $this->getJson('v3/works/ranks/new-personality')
            ->assertJson(['code' => 0]);

        $ranks = (json_decode($response->getContent()))->data->ranks;

        self::assertCount(2, $ranks);
        self::assertEquals($data->ranks->three->id, $ranks[0]->id);   // order by rank
        self::assertEquals($data->ranks->three->work_id, $ranks[0]->workId);
        self::assertEquals('user-three', $ranks[0]->nickname);
        self::assertTrue(ends_with($ranks[0]->avatar, 'user-three-avatar'));
        self::assertEquals('music-one', $ranks[0]->musicName);
        self::assertEquals($data->ranks->four->id, $ranks[1]->id);
    }

    private function prepareRanks()
    {
        $userOne = factory(\SingPlus\Domains\Users\Models\User::class)->create();
        $profileOne = factory(\SingPlus\Domains\Users\Models\UserProfile::class)->create([
            'user_id' => $userOne->id,
            'avatar' => 'user-one-avatar',
            'nickname' => 'user-one',
        ]);
        $userTwo = factory(\SingPlus\Domains\Users\Models\User::class)->create();
        $profileTwo = factory(\SingPlus\Domains\Users\Models\UserProfile::class)->create([
            'user_id' => $userTwo->id,
            'avatar' => 'user-two-avatar',
            'nickname' => 'user-two',
        ]);
        $userThree = factory(\SingPlus\Domains\Users\Models\User::class)->create();
        $profileThree = factory(\SingPlus\Domains\Users\Models\UserProfile::class)->create([
            'user_id' => $userThree->id,
            'avatar' => 'user-three-avatar',
            'nickname' => 'user-three',
        ]);
        $userFour = factory(\SingPlus\Domains\Users\Models\User::class)->create();
        $profileFour = factory(\SingPlus\Domains\Users\Models\UserProfile::class)->create([
            'user_id' => $userFour->id,
            'avatar' => 'user-four-avatar',
            'nickname' => 'user-four',
        ]);
        $musicOne = factory(\SingPlus\Domains\Musics\Models\Music::class)->create([
            'name' => 'music-one',
        ]);
        $musicTwo = factory(\SingPlus\Domains\Musics\Models\Music::class)->create([
            'name' => 'music-two',
        ]);
        $workOne = factory(\SingPlus\Domains\Works\Models\Work::class)->create([
            'user_id' => $userOne->id,
            'music_id' => $musicOne->id,
        ]);
        $workTwo = factory(\SingPlus\Domains\Works\Models\Work::class)->create([
            'user_id' => $userTwo->id,
            'music_id' => $musicTwo->id,
        ]);
        $workThree = factory(\SingPlus\Domains\Works\Models\Work::class)->create([
            'user_id' => $userThree->id,
            'music_id' => $musicOne->id,
        ]);
        $workFour = factory(\SingPlus\Domains\Works\Models\Work::class)->create([
            'user_id' => $userFour->id,
            'music_id' => $musicTwo->id,
        ]);
        $rankOne = factory(\SingPlus\Domains\Works\Models\WorkRank::class)->create([
            'work_id' => $workOne->id,
            'country_abbr' => 'NE',
            'is_global' => 1,
            'is_new_comer' => 0,
            'rank' => 1,
        ]);
        $rankTwo = factory(\SingPlus\Domains\Works\Models\WorkRank::class)->create([
            'work_id' => $workTwo->id,
            'country_abbr' => 'NE',
            'is_global' => 0,
            'is_new_comer' => 0,
            'rank' => 2,
        ]);
        $rankThree = factory(\SingPlus\Domains\Works\Models\WorkRank::class)->create([
            'work_id' => $workThree->id,
            'country_abbr' => 'KE',
            'is_global' => 0,
            'is_new_comer' => 1,
            'rank' => 3,
        ]);
        $rankFour = factory(\SingPlus\Domains\Works\Models\WorkRank::class)->create([
            'work_id' => $workFour->id,
            'country_abbr' => 'KE',
            'is_global' => 1,
            'is_new_comer' => 1,
            'rank' => 4,
        ]);

        return (object)[
            'musics' => (object)[
                'one' => $musicOne,
                'two' => $musicTwo,
            ],
            'works' => (object)[
                'one' => $workOne,
                'two' => $workTwo,
                'three' => $workThree,
                'four' => $workFour,
            ],
            'users' => (object)[
                'one' => $userOne,
                'two' => $userTwo,
                'three' => $userThree,
                'four' => $userFour,
            ],
            'profiles' => (object)[
                'one' => $profileOne,
                'two' => $profileTwo,
                'three' => $profileThree,
                'four' => $profileFour,
            ],
            'ranks' => (object)[
                'one' => $rankOne,
                'two' => $rankTwo,
                'three' => $rankThree,
                'four' => $rankFour,
            ],
        ];
    }
}
