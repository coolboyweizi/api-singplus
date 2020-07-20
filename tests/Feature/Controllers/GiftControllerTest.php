<?php
/**
 * Created by PhpStorm.
 * User: karl
 * Date: 2018/1/30
 * Time: 下午1:38
 */

namespace FeatureTest\SingPlus\Controllers;

use Mockery;
use Cache;
use FeatureTest\SingPlus\MongodbClearTrait;
use FeatureTest\SingPlus\TestCase;
use SingPlus\Contracts\Coins\Constants\Trans;
use SingPlus\Contracts\DailyTask\Constants\DailyTask as DailyTaskConstant;
use SingPlus\Domains\DailyTask\Models\DailyTask;
use SingPlus\Domains\Gifts\Models\Gift;
use SingPlus\Exceptions\Coins\AccountBalanceNotEnoughException;
use SingPlus\Exceptions\ExceptionCode;


class GiftControllerTest extends TestCase
{
    use MongodbClearTrait;

    //------------------------------
    //      getGiftList
    //------------------------------
    public function testGetGiftList(){

        $data = $this->prepareGifts();
        $accountService = $this->mockAccountService();

        $response = $this->getJson('v3/gifts/lists')
            ->assertJson(['code' => 0]);
        $response = json_decode($response->getContent());
        $gifts = $response->data->gifts;
        self::assertCount(4, $gifts);
        self::assertEquals($data->gift->one->name, $gifts[0]->name);
        self::assertEquals($data->gift->two->name, $gifts[1]->name);
        self::assertEquals($data->gift->three->name, $gifts[2]->name);
        self::assertEquals($data->gift->four->name, $gifts[3]->name);
    }

    public function testGetGiftListSuccess_WithEmptyList(){
        $accountService = $this->mockAccountService();
        $response = $this->getJson('v3/gifts/lists')
            ->assertJson(['code' => 0]);
        $response = json_decode($response->getContent());
        $gifts = $response->data->gifts;
        self::assertCount(0, $gifts);
    }


    //------------------------------
    //      getWorkGiftRank
    //------------------------------
    public function testGetWorkGiftRank(){
        $data = $this->prepareGifts();
        $accountService = $this->mockAccountService();

        $work = $data->work->one;
        $response = $this->postJson('v3/gifts/workRank', [
            'workId' => $work->id,
        ])->assertJson(['code' => 0]);
        $response = json_decode($response->getContent());
        $users = $response->data->users;
        self::assertCount(3, $users);
        self::assertEquals(4000, $users[0]->goldNumber);
        self::assertEquals($data->users->three->id, $users[0]->userId);
        self::assertCount(3, $users[0]->gifts);
    }

    //------------------------------
    //      getWorkGiftRank
    //------------------------------
    public function testGetWorkGiftRank_WhenGiftOffLine(){
        $data = $this->prepareGifts();
        $accountService = $this->mockAccountService();

        Gift::where('type', 'type c')->update(['status' => 0]);


        $work = $data->work->one;
        $response = $this->postJson('v3/gifts/workRank', [
            'workId' => $work->id,
        ])->assertJson(['code' => 0]);
        $response = json_decode($response->getContent());
        $users = $response->data->users;
        self::assertCount(3, $users);
        self::assertEquals(4000, $users[0]->goldNumber);
        self::assertEquals($data->users->three->id, $users[0]->userId);
        self::assertCount(3, $users[0]->gifts);
    }

    public function testGetWorkGiftRankFailed_WorkNotExist(){
        $accountService = $this->mockAccountService();
        $response = $this->postJson('v3/gifts/workRank', [
            'workId' => "1cec4cbcccc840da81b11e01fa8d19bb",
        ])->assertJson(['code' => ExceptionCode::WORK_NOT_EXISTS]);
    }

    public function testGetWorkGiftRankSuccess_WithEmptyRank(){
        $accountService = $this->mockAccountService();
        $work = factory(\SingPlus\Domains\Works\Models\Work::class)->create();
        $response = $this->postJson('v3/gifts/workRank', [
            'workId' => $work->id,
        ])->assertJson(['code' => 0]);
        $response = json_decode($response->getContent());
        $users = $response->data->users;
        self::assertCount(0, $users);
    }

    //------------------------------
    //      getWorkGiftRankForUser
    //------------------------------
    public function testGetWorkGiftRankForUser(){
        $data = $this->prepareGifts();
        $accountService = $this->mockAccountService();

        $work = $data->work->one;
        $user = $data->users->one;
        $response = $this->actingAs($user)
         ->getJson('v3/gifts/userWorkRank?'.http_build_query([
                 'workId' => $work->id,
             ]))->assertJson(['code' => 0]);
        $response = json_decode($response->getContent());
        $rank = $response->data;
        self::assertEquals(3, $rank->rank);
        self::assertEquals(12, $rank->giftAmount);
        self::assertEquals(1000, $rank->coinAmount);
    }

    public function testGetWorkGiftRankForUser_UserNoContrib(){
        $data = $this->prepareGifts();
        $accountService = $this->mockAccountService();

        $work = $data->work->one;
        $user = $data->users->four;
        $response = $this->actingAs($user)
            ->getJson('v3/gifts/userWorkRank?'.http_build_query([
                    'workId' => $work->id,
                ]))->assertJson(['code' => 0]);
        $response = json_decode($response->getContent());
        $rank = $response->data;
        self::assertEquals(0, $rank->rank);
        self::assertEquals(0, $rank->giftAmount);
        self::assertEquals(0, $rank->coinAmount);
    }

    public function testGetWorkGiftRankForUserFailed_NoLogin(){
        $data = $this->prepareGifts();
        $accountService = $this->mockAccountService();
        $work = $data->work->one;
        $user = $data->users->four;
        $response = $this->getJson('v3/gifts/userWorkRank?'.http_build_query([
                    'workId' => $work->id,
                ]))->assertJson(['code' => 10101]);
    }

    //------------------------------
    //      sendGiftForWork
    //------------------------------

    public function testSendGiftForWork(){
        $this->expectsEvents(\SingPlus\Events\Gifts\UserSendGiftForWork::class);
        $this->enableNationOperationMiddleware();
        $data = $this->prepareGifts();
        $work = $data->work->one;
        $user = $data->users->five;
        $gift = $data->gift->one;

        $counterMock = Mockery::mock(\Illuminate\Contracts\Cache\Store::class);
        $counterMock->shouldReceive('increment')
            ->once()
            ->with('giftHistory', 100)
            ->andReturn(100);
        Cache::shouldReceive('driver')
            ->once()
            ->with('counter')
            ->andReturn($counterMock);


        $accountService = $this->mockAccountService();
        $accountService->shouldReceive('withdraw')
            ->once()
            ->with($user->id, 5 * $gift->coins, Trans::SOURCE_WITHDRAW_GIVE_GIFT,
                $user->id, Mockery::on(function ($data) {
                    return $data instanceof \stdClass &&
                        $data->giftHistory_id != null;
                }))
            ->andReturn(100);

        $dailyTaskService = $this->mockDailyTaskService();
        $dailyTaskService->shouldReceive('resetDailyTaskLists')
            ->once()
            ->with($user->id, 'CN')
            ->andReturn();

        $dailyTaskService->shouldReceive('finisheDailyTask')
            ->once()
            ->with($user->id, DailyTaskConstant::TYPE_GIFT)
            ->andReturn();

        $popularityService = $this->mockPopularityHierarchyService();
        $popularityService->shouldReceive('updatePopularity')
            ->once()
            ->with($work->id)
            ->andReturn();

        $wealthService = $this->mockWealthHierarchyService();
        $wealthService->shouldReceive('updateWealthHierarchy')
            ->once()
            ->with($user->id)
            ->andReturn();

        $response = $this->actingAs($user)
            ->postJson('v3/gifts/sendToWork?', [
                    'workId' => $work->id,
                    'giftId' => $gift->id,
                    'number' => 5
                ], ['X-CountryAbbr' => 'CN'])->assertJson(['code' => 0]);

        $this->assertDatabaseHas('gift_history', [
            'sender_id' => $user->id,
            'work_id' => $work->id,
            'receiver_id' => $work->user_id,
            'amount' => 5,
            'display_order' => 100,
            'gift_info' => [
                "id"=> $gift->id,
                "type"=> "type a",
                "name"=> "gift A",
                "icon"=> [
                    "icon_small"=> "xxxx.png",
                    "icon_big"=> "xxxx.png"
                ],
                "coins"=> 10,
                "sold_amount"=> 0,
                "sold_coin_amount"=> 0,
                "status"=> 1,
                "popularity"=> 20,
                "animation"=> [
                    "type"=> 1,
                    "url"=> "xxxx.gift",
                    "duration"=> 1
                ]

            ]

        ]);

        $this->assertDatabaseHas('gift_contribution',[
            'sender_id' => $user->id,
            'work_id' => $work->id,
            'receiver_id' => $work->user_id,
            'coin_amount'  => 50,
            'gift_amount'  => 5,
            'gift_ids' => [$gift->id],
            'gift_detail' => [
                (object)[
                    'gift_id' => $gift->id,
                    'gift_coins' => 50,
                    'gift_amount' => 5
                ]
            ]
        ]);

        $this->assertDatabaseHas('gifts', [
            '_id' => $gift->id,
            'sold_coin_amount' => 50,
            'sold_amount' => 5,
        ]);

        $this->assertDatabaseHas('works', [
            '_id' => $work->id,
            'gift_info' => [
                'gift_amount' => 5,
                'gift_coin_amount' => 50,
                'gift_popularity_amount' => 100
            ]
        ]);

        $this->assertDatabaseHas('user_profiles', [
            'user_id' => $user->id,
            'coins' => [
                'gift_consume_amount' => 50
            ],

            'consume_coins_info' => [
                'consume_coins' => 50
            ]
        ]);

    }

    public function testSendGiftForWorkSuccess_SendForOneself(){
        $this->expectsEvents(\SingPlus\Events\Gifts\UserSendGiftForWork::class);
        $this->enableNationOperationMiddleware();
        $data = $this->prepareGifts();
        $work = $data->work->one;
        $user = $data->users->one;
        $gift = $data->gift->one;

        $counterMock = Mockery::mock(\Illuminate\Contracts\Cache\Store::class);
        $counterMock->shouldReceive('increment')
            ->once()
            ->with('giftHistory', 100)
            ->andReturn(100);
        Cache::shouldReceive('driver')
            ->once()
            ->with('counter')
            ->andReturn($counterMock);


        $accountService = $this->mockAccountService();
        $accountService->shouldReceive('withdraw')
            ->once()
            ->with($user->id, 5 * $gift->coins, Trans::SOURCE_WITHDRAW_GIVE_GIFT,
                $user->id, Mockery::on(function ($data) {
                    return $data instanceof \stdClass &&
                        $data->giftHistory_id != null;
                }))
            ->andReturn(100);

        $dailyTaskService = $this->mockDailyTaskService();
        $dailyTaskService->shouldReceive('resetDailyTaskLists')
            ->once()
            ->with($user->id, 'CN')
            ->andReturn();

        $dailyTaskService->shouldReceive('finisheDailyTask')
            ->once()
            ->with($user->id, DailyTaskConstant::TYPE_GIFT)
            ->andReturn();

        $popularityService = $this->mockPopularityHierarchyService();
        $popularityService->shouldReceive('updatePopularity')
            ->once()
            ->with($work->id)
            ->andReturn();

        $wealthService = $this->mockWealthHierarchyService();
        $wealthService->shouldReceive('updateWealthHierarchy')
            ->once()
            ->with($user->id)
            ->andReturn();

        $response = $this->actingAs($user)
            ->postJson('v3/gifts/sendToWork?', [
                'workId' => $work->id,
                'giftId' => $gift->id,
                'number' => 5
            ], ['X-CountryAbbr' => 'CN'])->assertJson(['code' => 0]);

        $this->assertDatabaseHas('gift_history', [
            'sender_id' => $user->id,
            'work_id' => $work->id,
            'receiver_id' => $work->user_id,
            'amount' => 5,
            'display_order' => 100
        ]);

        $this->assertDatabaseHas('gift_contribution',[
            'sender_id' => $user->id,
            'work_id' => $work->id,
            'receiver_id' => $work->user_id,
            'coin_amount'  => 1050,
            'gift_amount'  => 17,
            'gift_ids' => [$gift->id, $data->gift->two->id, $data->gift->three->id],
            'gift_detail' => [
                (object)[
                    'gift_id' => $data->gift->two->id,
                    'gift_coins' => 300,
                    'gift_amount' => 3
                ],
                (object)[
                    'gift_id' => $data->gift->three->id,
                    'gift_coins' => 500,
                    'gift_amount' => 5
                ],
                (object)[
                    'gift_id' => $gift->id,
                    'gift_coins' => 250,
                    'gift_amount' => 9
                ],
            ]
        ]);

        $this->assertDatabaseHas('gifts', [
            '_id' => $gift->id,
            'sold_coin_amount' => 50,
            'sold_amount' => 5,
        ]);

        $this->assertDatabaseHas('works', [
            '_id' => $work->id,
            'gift_info' => [
                'gift_amount' => 5,
                'gift_coin_amount' => 50,
                'gift_popularity_amount' => 100
            ]
        ]);

    }


    public function testSendGiftForWorkFailed_NotEnougCoins(){
        $this->doesntExpectEvents(\SingPlus\Events\Gifts\UserSendGiftForWork::class);
        $this->enableNationOperationMiddleware();
        $data = $this->prepareGifts();
        $work = $data->work->one;
        $user = $data->users->five;
        $gift = $data->gift->one;

        $counterMock = Mockery::mock(\Illuminate\Contracts\Cache\Store::class);
        $counterMock->shouldReceive('increment')
            ->once()
            ->with('giftHistory', 100)
            ->andReturn(100);
        Cache::shouldReceive('driver')
            ->once()
            ->with('counter')
            ->andReturn($counterMock);


        $accountService = $this->mockAccountService();
        $accountService->shouldReceive('withdraw')
            ->once()
            ->with($user->id, 5 * $gift->coins, Trans::SOURCE_WITHDRAW_GIVE_GIFT,
                $user->id, Mockery::on(function ($data) {
                    return $data instanceof \stdClass &&
                        $data->giftHistory_id != null;
                }))
            ->andThrow(new AccountBalanceNotEnoughException());

        $dailyTaskService = $this->mockDailyTaskService();
        $dailyTaskService->shouldReceive('resetDailyTaskLists')
            ->never()
            ->with($user->id, 'CN')
            ->andReturn();

        $dailyTaskService->shouldReceive('finisheDailyTask')
            ->never()
            ->with($user->id, DailyTaskConstant::TYPE_GIFT)
            ->andReturn();

        $popularityService = $this->mockPopularityHierarchyService();
        $popularityService->shouldReceive('updatePopularity')
            ->never()
            ->with($work->id)
            ->andReturn();

        $wealthService = $this->mockWealthHierarchyService();
        $wealthService->shouldReceive('updateWealthHierarchy')
            ->never()
            ->with($user->id)
            ->andReturn();

        $response = $this->actingAs($user)
            ->postJson('v3/gifts/sendToWork?', [
                'workId' => $work->id,
                'giftId' => $gift->id,
                'number' => 5
            ], ['X-CountryAbbr' => 'CN'])->assertJson(['code' => ExceptionCode::ACCOUNT_BALANCE_NOT_ENOUGH]);

        $this->assertDatabaseHas('gift_history', [
            'sender_id' => $user->id,
            'work_id' => $work->id,
            'receiver_id' => $work->user_id,
            'amount' => 5,
            'display_order' => 100,
            'status'    => 0,
        ]);

        $this->assertDatabaseMissing('gift_contribution',[
            'sender_id' => $user->id,
            'work_id' => $work->id,
            'receiver_id' => $work->user_id,
            'coin_amount'  => 50,
            'gift_amount'  => 5,
            'gift_ids' => [$gift->id],
            'gift_detail' => [
                (object)[
                    'gift_id' => $gift->id,
                    'gift_coins' => 50,
                    'gift_amount' => 5
                ]
            ]
        ]);

        $this->assertDatabaseMissing('gifts', [
            '_id' => $gift->id,
            'sold_coin_amount' => 50,
            'sold_amount' => 5,
        ]);

        $this->assertDatabaseMissing('works', [
            '_id' => $work->id,
            'gift_info' => [
                'gift_amount' => 5,
                'gift_coin_amount' => 50,
                'gift_popularity_amount' => 100
            ]
        ]);

    }

    private function mockAccountService(){
        $accountService = Mockery::mock(\SingPlus\Contracts\Coins\Services\AccountService::class);
        $this->app[\SingPlus\Contracts\Coins\Services\AccountService::class ] = $accountService;
        return $accountService;
    }

    private function mockDailyTaskService(){
        $dailyTaskService = Mockery::mock(\SingPlus\Contracts\DailyTask\Services\DailyTaskService::class);
        $this->app[\SingPlus\Contracts\DailyTask\Services\DailyTaskService::class ] = $dailyTaskService;
        return $dailyTaskService;
    }

    private function prepareGifts(){

        $giftOne = factory(\SingPlus\Domains\Gifts\Models\Gift::class)->create([
           'type' => "type a",
           'name' => 'gift A',
           'display_order'=> 1000
        ]);

        $giftTwo = factory(\SingPlus\Domains\Gifts\Models\Gift::class)->create([
            'type' => "type b",
            'name' => 'gift B',
            'display_order'=> 900
        ]);

        $giftThree = factory(\SingPlus\Domains\Gifts\Models\Gift::class)->create([
            'type' => "type c",
            'name' => 'gift C',
            'display_order'=> 800
        ]);

        $giftFour = factory(\SingPlus\Domains\Gifts\Models\Gift::class)->create([
            'type' => "type d",
            'name' => 'gift D',
            'display_order'=> 700
        ]);

        $userOne = factory(\SingPlus\Domains\Users\Models\User::class)->create();
        $userProfileOne = factory(\SingPlus\Domains\Users\Models\UserProfile::class)->create([
            'user_id' => $userOne->id,
            'nickname' => 'Onesile',
            'avatar' => 'avatar one',
        ]);

        $userTwo = factory(\SingPlus\Domains\Users\Models\User::class)->create();
        $userProfileTwo = factory(\SingPlus\Domains\Users\Models\UserProfile::class)->create([
            'user_id' => $userTwo->id,
            'nickname' => 'Twosile',
            'avatar' => 'avatar two',
        ]);

        $userThree = factory(\SingPlus\Domains\Users\Models\User::class)->create();
        $userProfileThree = factory(\SingPlus\Domains\Users\Models\UserProfile::class)->create([
            'user_id' => $userThree->id,
            'nickname' => 'Threesile',
            'avatar' => 'avatar three',
        ]);

        $userFour = factory(\SingPlus\Domains\Users\Models\User::class)->create();
        $userProfileFour = factory(\SingPlus\Domains\Users\Models\UserProfile::class)->create([
            'user_id' => $userFour->id,
            'nickname' => 'Foursile',
            'avatar' => 'avatar four',
        ]);

        $userFive = factory(\SingPlus\Domains\Users\Models\User::class)->create();
        $userProfileFive = factory(\SingPlus\Domains\Users\Models\UserProfile::class)->create([
            'user_id' => $userFive->id,
            'nickname' => 'Fivesile',
            'avatar' => 'avatar five',
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
            'user_id'   => $userFour->id,
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

        $giftContribOne = factory(\SingPlus\Domains\Gifts\Models\GiftContribution::class)->create([
             'sender_id' => $userOne->id,
            'receiver_id' => $userFour->id,
            'work_id' => $workOne->id,
            'coin_amount' => 1000,
            'gift_amount' => 12,
            'gift_ids' => [$giftOne->id, $giftTwo->id, $giftThree->id],
            'gift_detail' => [
                (object)[
                    'gift_id' => $giftOne->id,
                    'gift_coins' => 200,
                    'gift_amount' => 4
                ],
                (object)[
                    'gift_id' => $giftTwo->id,
                    'gift_coins' => 300,
                    'gift_amount' => 3
                ],
                (object)[
                    'gift_id' => $giftThree->id,
                    'gift_coins' => 500,
                    'gift_amount' => 5
                ]
            ]
        ]);

        $giftContribTwo = factory(\SingPlus\Domains\Gifts\Models\GiftContribution::class)->create([
            'sender_id' => $userTwo->id,
            'receiver_id' => $userFour->id,
            'work_id' => $workOne->id,
            'coin_amount' => 2000,
            'gift_amount' => 24,
            'gift_ids' => [$giftOne->id, $giftTwo->id, $giftThree->id],
            'gift_detail' => [
                (object)[
                    'gift_id' => $giftOne->id,
                    'gift_coins' => 400,
                    'gift_amount' => 8
                ],
                (object)[
                    'gift_id' => $giftTwo->id,
                    'gift_coins' => 600,
                    'gift_amount' => 6
                ],
                (object)[
                    'gift_id' => $giftThree->id,
                    'gift_coins' => 1000,
                    'gift_amount' => 10
                ]
            ]
        ]);

        $giftContribThree = factory(\SingPlus\Domains\Gifts\Models\GiftContribution::class)->create([
            'sender_id' => $userThree->id,
            'receiver_id' => $userFour->id,
            'work_id' => $workOne->id,
            'coin_amount' => 4000,
            'gift_amount' => 36,
            'gift_ids' => [$giftOne->id, $giftTwo->id, $giftThree->id],
            'gift_detail' => [
                (object)[
                    'gift_id' => $giftOne->id,
                    'gift_coins' => 800,
                    'gift_amount' => 16
                ],
                (object)[
                    'gift_id' => $giftTwo->id,
                    'gift_coins' => 1200,
                    'gift_amount' => 12
                ],
                (object)[
                    'gift_id' => $giftThree->id,
                    'gift_coins' => 2000,
                    'gift_amount' => 20
                ]
            ]
        ]);


        return (object)[
          'gift' => (object)[
              'one' => $giftOne,
              'two' => $giftTwo,
              'three' => $giftThree,
              'four'  => $giftFour,
          ],
          'work' => (object)[
              'one' => $workOne
          ],
          'users' => (object)[
            'one' => $userOne,
            'two' => $userTwo,
            'three' => $userThree,
            'four'  => $userFour,
            'five'  => $userFive,
          ],
          'userProfile' => (object)[
             'one' => $userProfileOne,
             'two'  => $userProfileTwo,
             'three' => $userProfileThree,
             'four'  => $userProfileFour,
             'five'  => $userProfileFive,
          ],
          'giftContrib' => (object)[
              'one' => $giftContribOne,
              'two' => $giftContribTwo,
              'three' => $giftContribThree
          ]
        ];
    }

    private function mockPopularityHierarchyService(){
        $popularityHierarchyService = Mockery::mock(\SingPlus\Contracts\Hierarchy\Services\PopularityHierarchyService::class);
        $this->app[\SingPlus\Contracts\Hierarchy\Services\PopularityHierarchyService::class ] = $popularityHierarchyService;
        return $popularityHierarchyService;
    }

    private function mockWealthHierarchyService(){
        $wealthHierarchyService = Mockery::mock(\SingPlus\Contracts\Hierarchy\Services\WealthHierarchyService::class);
        $this->app[\SingPlus\Contracts\Hierarchy\Services\WealthHierarchyService::class ] = $wealthHierarchyService;
        return $wealthHierarchyService;
    }

}