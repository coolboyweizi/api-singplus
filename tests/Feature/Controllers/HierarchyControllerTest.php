<?php
/**
 * Created by PhpStorm.
 * User: karl
 * Date: 2018/2/27
 * Time: 下午5:45
 */

namespace FeatureTest\SingPlus\Controllers;
use Mockery;
use FeatureTest\SingPlus\MongodbClearTrait;
use FeatureTest\SingPlus\TestCase;
use SingPlus\Domains\Hierarchy\Models\Hierarchy;
use SingPlus\Domains\Hierarchy\Models\WealthRank;

class HierarchyControllerTest extends TestCase
{
    use MongodbClearTrait;

    //=================================
    //        userPopularityHierarchy
    //=================================
    public function testUserPopularityHierarchySuccess(){

        $hierarchyList = $this->prepareHierarchyList();

        $user = factory(\SingPlus\Domains\Users\Models\User::class)->create();
        $userProfile = factory(\SingPlus\Domains\Users\Models\UserProfile::class)->create([
                'user_id' => $user->id,
                'popularity_info' => [
                    'work_popularity' => 501,
                    'hierarchy_id' => $hierarchyList->popularity->three->id,
                    'hierarchy_gap' => 999
                ]
        ]);

        $response = $this->actingAs($user)
            ->getJson('v3/hierarchy/userHierarchyInfo?'.http_build_query([
                    'userId' => $user->id,
                ]))->assertJson(['code' => 0]);
        $response = json_decode($response->getContent());
        $data = $response->data;
        self::assertEquals(501, $data->popularity);
        self::assertEquals(999, $data->gapPopularity);
        self::assertEquals(0, $data->hierarchy[0]->popularity);
        self::assertCount(11, $data->hierarchy);
    }

    public function testUserPopularityHierarchySuceess_WithOutHierarchyInfo(){
        $hierarchyList = $this->prepareHierarchyList();

        $user = factory(\SingPlus\Domains\Users\Models\User::class)->create();
        $userProfile = factory(\SingPlus\Domains\Users\Models\UserProfile::class)->create([
            'user_id' => $user->id,
            'popularity_info' => [
                'work_popularity' => 501,
            ]
        ]);

        $response = $this->actingAs($user)
            ->getJson('v3/hierarchy/userHierarchyInfo?'.http_build_query([
                    'userId' => $user->id,
                ]))->assertJson(['code' => 0]);
        $response = json_decode($response->getContent());
        $data = $response->data;
        self::assertEquals(501, $data->popularity);
        self::assertEquals(999, $data->gapPopularity);
        self::assertEquals(0, $data->hierarchy[0]->popularity);
        self::assertCount(11, $data->hierarchy);
    }

    public function testUserPopularityHierarchySuccess_WithoutWorkPopularity(){
        $hierarchyList = $this->prepareHierarchyList();

        $user = factory(\SingPlus\Domains\Users\Models\User::class)->create();
        $userProfile = factory(\SingPlus\Domains\Users\Models\UserProfile::class)->create([
            'user_id' => $user->id,
        ]);

        $response = $this->actingAs($user)
            ->getJson('v3/hierarchy/userHierarchyInfo?'.http_build_query([
                    'userId' => $user->id,
                ]))->assertJson(['code' => 0]);
        $response = json_decode($response->getContent());
        $data = $response->data;
        self::assertEquals(0, $data->popularity);
        self::assertEquals(100, $data->gapPopularity);
        self::assertEquals(0, $data->hierarchy[0]->popularity);
        self::assertCount(11, $data->hierarchy);
    }

    //========================================
    //          userWealthHierarchy
    //========================================
    public function testUserWealthHierarchySuccess(){
        $hierarchyList = $this->prepareHierarchyList();
        $user = factory(\SingPlus\Domains\Users\Models\User::class)->create();
        $userProfile = factory(\SingPlus\Domains\Users\Models\UserProfile::class)->create([
            'user_id' => $user->id,
            'consume_coins_info' => [
                'consume_coins' => 501,
                'hierarchy_id' => $hierarchyList->popularity->three->id,
                'hierarchy_gap' => 999
            ]
        ]);

        $response = $this->actingAs($user)
            ->getJson('v3/hierarchy/userWealthInfo?'.http_build_query([
                    'userId' => $user->id,
                ]))->assertJson(['code' => 0]);
        $response = json_decode($response->getContent());
        $data = $response->data;
        self::assertEquals(501, $data->consumeCoins);
        self::assertEquals(1499, $data->gapCoins);
        self::assertEquals(0, $data->hierarchy[0]->consumeCoins);
        self::assertCount(8, $data->hierarchy);
    }

    public function testUserWealthHierarchySuccess_WithoutConsumeCoins(){
        $hierarchyList = $this->prepareHierarchyList();
        $user = factory(\SingPlus\Domains\Users\Models\User::class)->create();
        $userProfile = factory(\SingPlus\Domains\Users\Models\UserProfile::class)->create([
            'user_id' => $user->id,
        ]);

        $response = $this->actingAs($user)
            ->getJson('v3/hierarchy/userWealthInfo?'.http_build_query([
                    'userId' => $user->id,
                ]))->assertJson(['code' => 0]);
        $response = json_decode($response->getContent());
        $data = $response->data;
        self::assertEquals(0, $data->consumeCoins);
        self::assertEquals(1, $data->gapCoins);
        self::assertEquals(0, $data->hierarchy[0]->consumeCoins);
        self::assertCount(8, $data->hierarchy);
    }


    public function testUserWealthHierarchySuccess_WithoutWealthHierarchyInfo(){
        $hierarchyList = $this->prepareHierarchyList();
        $user = factory(\SingPlus\Domains\Users\Models\User::class)->create();
        $userProfile = factory(\SingPlus\Domains\Users\Models\UserProfile::class)->create([
            'user_id' => $user->id,
            'consume_coins_info' => [
                'consume_coins' => 501,
            ]
        ]);

        $response = $this->actingAs($user)
            ->getJson('v3/hierarchy/userWealthInfo?'.http_build_query([
                    'userId' => $user->id,
                ]))->assertJson(['code' => 0]);
        $response = json_decode($response->getContent());
        $data = $response->data;
        self::assertEquals(501, $data->consumeCoins);
        self::assertEquals(1499, $data->gapCoins);
        self::assertEquals(0, $data->hierarchy[0]->consumeCoins);
        self::assertCount(8, $data->hierarchy);
    }

    //========================================
    //              wealthRank
    //========================================
    public function testWealthRankSuccess_TypeDaily(){
        $hierarchyList = $this->prepareHierarchyList();
        $data = $this->prepareWealthRank();

        $response = $this->getJson('v3/hierarchy/wealthRank?'.http_build_query([
                'type' => WealthRank::TYPE_DAILY,
            ]))->assertJson(['code'=>0]);
        $response = json_decode($response->getContent());
        $data = $response->data;
        self::assertCount(4, $data->ranks);
        self::assertEquals('zhangsan', $data->ranks[0]->nickname);
        self::assertEquals('lisi', $data->ranks[1]->nickname);
        self::assertEquals('wangwu', $data->ranks[2]->nickname);
        self::assertEquals('zhaoliu', $data->ranks[3]->nickname);
    }

    public function testWealthRankSuccess_TypeDailyForPagination(){
        $hierarchyList = $this->prepareHierarchyList();
        $data = $this->prepareWealthRank();
        $rankId = $data->ranks->two->id;
        $response = $this->getJson('v3/hierarchy/wealthRank?'.http_build_query([
                'type' => WealthRank::TYPE_DAILY,
                'rankId' => $rankId
            ]))->assertJson(['code'=>0]);
        $response = json_decode($response->getContent());
        $data = $response->data;
        self::assertCount(2, $data->ranks);
        self::assertEquals('wangwu', $data->ranks[0]->nickname);
        self::assertEquals('zhaoliu', $data->ranks[1]->nickname);
    }

    public function testWealthRankSuccess_TypeTotal(){
        $hierarchyList = $this->prepareHierarchyList();
        $data = $this->prepareWealthRank();
        $response = $this->getJson('v3/hierarchy/wealthRank?'.http_build_query([
                'type' => WealthRank::TYPE_TOTAL,
            ]))->assertJson(['code'=>0]);
        $response = json_decode($response->getContent());
        $data = $response->data;
        self::assertCount(4, $data->ranks);
        self::assertEquals('Ella', $data->ranks[0]->nickname);
        self::assertEquals('Foile', $data->ranks[1]->nickname);
        self::assertEquals('Gaily', $data->ranks[2]->nickname);
        self::assertEquals('Foosy', $data->ranks[3]->nickname);
    }

    public function testWealthRankSuccess_TypeTotalForPagination(){
        $hierarchyList = $this->prepareHierarchyList();
        $data = $this->prepareWealthRank();
        $rankId = $data->ranks->seven->id;
        $response = $this->getJson('v3/hierarchy/wealthRank?'.http_build_query([
                'type' => WealthRank::TYPE_TOTAL,
                'rankId' => $rankId
            ]))->assertJson(['code'=>0]);
        $response = json_decode($response->getContent());
        $data = $response->data;
        self::assertCount(1, $data->ranks);
        self::assertEquals('Foosy', $data->ranks[0]->nickname);
    }


    private function prepareWealthRank(){
        $userOne = factory(\SingPlus\Domains\Users\Models\User::class)->create();
        $userProfileOne = factory(\SingPlus\Domains\Users\Models\UserProfile::class)->create([
            'user_id' => $userOne->id,
            'nickname' => 'zhangsan',
            'avatar' => 'zhangsan_avatar.jpg',
            'consume_coins_info' => [
                'consume_coins' => 10000
            ]
        ]);
        $rankOne = factory(\SingPlus\Domains\Hierarchy\Models\WealthRank::class)->create([
            'user_id' => $userOne->id,
            'type' => WealthRank::TYPE_DAILY,
            'display_order' => 100
        ]);

        $userTwo = factory(\SingPlus\Domains\Users\Models\User::class)->create();
        $userProfileTwo = factory(\SingPlus\Domains\Users\Models\UserProfile::class)->create([
            'user_id' => $userTwo->id,
            'nickname' => 'lisi',
            'avatar' => 'lisi_avatar.jpg',
            'consume_coins_info' => [
                'consume_coins' => 8000
            ]
        ]);
        $rankTwo = factory(\SingPlus\Domains\Hierarchy\Models\WealthRank::class)->create([
            'user_id' => $userTwo->id,
            'type' => WealthRank::TYPE_DAILY,
            'display_order' => 200
        ]);

        $userThree = factory(\SingPlus\Domains\Users\Models\User::class)->create();
        $userProfileThree = factory(\SingPlus\Domains\Users\Models\UserProfile::class)->create([
            'user_id' => $userThree->id,
            'nickname' => 'wangwu',
            'avatar' => 'wangwu_avatar.jpg',
            'consume_coins_info' => [
                'consume_coins' => 7000
            ]
        ]);
        $rankThree = factory(\SingPlus\Domains\Hierarchy\Models\WealthRank::class)->create([
            'user_id' => $userThree->id,
            'type' => WealthRank::TYPE_DAILY,
            'display_order' => 300
        ]);

        $userFour = factory(\SingPlus\Domains\Users\Models\User::class)->create();
        $userProfileFour = factory(\SingPlus\Domains\Users\Models\UserProfile::class)->create([
            'user_id' => $userFour->id,
            'nickname' => 'zhaoliu',
            'avatar' => 'zhaoliu_avatar.jpg',
        ]);
        $rankFour = factory(\SingPlus\Domains\Hierarchy\Models\WealthRank::class)->create([
            'user_id' => $userFour->id,
            'type' => WealthRank::TYPE_DAILY,
            'display_order' => 400
        ]);

        $userFive = factory(\SingPlus\Domains\Users\Models\User::class)->create();
        $userProfileFive = factory(\SingPlus\Domains\Users\Models\UserProfile::class)->create([
            'user_id' => $userFive->id,
            'nickname' => 'Ella',
            'avatar' => 'Ella_avatar.jpg',
            'consume_coins_info' => [
                'consume_coins' => 117000
            ]
        ]);
        $rankFive = factory(\SingPlus\Domains\Hierarchy\Models\WealthRank::class)->create([
            'user_id' => $userFive->id,
            'type' => WealthRank::TYPE_TOTAL,
            'display_order' => 101
        ]);

        $userSix = factory(\SingPlus\Domains\Users\Models\User::class)->create();
        $userProfileSix = factory(\SingPlus\Domains\Users\Models\UserProfile::class)->create([
            'user_id' => $userSix->id,
            'nickname' => 'Foile',
            'avatar' => 'Foile_avatar.jpg',
            'consume_coins_info' => [
                'consume_coins' => 110000
            ]
        ]);
        $rankSix = factory(\SingPlus\Domains\Hierarchy\Models\WealthRank::class)->create([
            'user_id' => $userSix->id,
            'type' => WealthRank::TYPE_TOTAL,
            'display_order' => 201
        ]);


        $userSeven = factory(\SingPlus\Domains\Users\Models\User::class)->create();
        $userProfileSeven = factory(\SingPlus\Domains\Users\Models\UserProfile::class)->create([
            'user_id' => $userSeven->id,
            'nickname' => 'Gaily',
            'avatar' => 'Gaily_avatar.jpg',
            'consume_coins_info' => [
                'consume_coins' => 90000,
            ]
        ]);
        $rankSeven = factory(\SingPlus\Domains\Hierarchy\Models\WealthRank::class)->create([
            'user_id' => $userSeven->id,
            'type' => WealthRank::TYPE_TOTAL,
            'display_order' => 301
        ]);

        $userEight = factory(\SingPlus\Domains\Users\Models\User::class)->create();
        $userProfileEight = factory(\SingPlus\Domains\Users\Models\UserProfile::class)->create([
            'user_id' => $userEight->id,
            'nickname' => 'Foosy',
            'avatar' => 'Foosy_avatar.jpg',
        ]);
        $rankEight = factory(\SingPlus\Domains\Hierarchy\Models\WealthRank::class)->create([
            'user_id' => $userEight->id,
            'type' => WealthRank::TYPE_TOTAL,
            'display_order' => 401
        ]);

        return (object)[
            'users' => (object)[
                'one' => $userOne,
                'two' => $userTwo,
                'three' => $userThree,
                'four' => $userFour,
                'five' => $userFive,
                'six' => $userSix,
                'seven' => $userSeven,
                'eight' => $userEight
            ],

            'userProfiles' => (object)[
                'one' => $userProfileOne,
                'two' => $userProfileTwo,
                'three' => $userProfileThree,
                'four' => $userProfileFour,
                'five' => $userProfileFive,
                'six' => $userProfileSix,
                'seven' => $userProfileSeven,
                'eight' => $userProfileEight
            ],

            'ranks' => (object)[
                'one' => $rankOne,
                'two' => $rankTwo,
                'three' => $rankThree,
                'four' => $rankFour,
                'five' => $rankFive,
                'six' => $rankSix,
                'seven' => $rankSeven,
                'eight' => $rankEight
            ]
        ];
    }

    private function prepareHierarchyList(){

        $userOne = factory(\SingPlus\Domains\Hierarchy\Models\Hierarchy::class)->create([
            'name' => '练习生',
            'alias' => '练习生',
            'amount' => 0,
            'rank' => 1,
            'type' => Hierarchy::TYPE_USER,
        ]);

        $userTwo = factory(\SingPlus\Domains\Hierarchy\Models\Hierarchy::class)->create([
            'name' => '出道新人',
            'alias' => '出道新人',
            'amount' => 100,
            'rank' => 2,
            'type' => Hierarchy::TYPE_USER,
        ]);

        $userThree = factory(\SingPlus\Domains\Hierarchy\Models\Hierarchy::class)->create([
            'name' => '新晋歌艺学徒',
            'alias' => '新晋歌艺学徒',
            'amount' => 500,
            'rank' => 3,
            'type' => Hierarchy::TYPE_USER,
        ]);

        $userFour = factory(\SingPlus\Domains\Hierarchy\Models\Hierarchy::class)->create([
            'name' => '进阶歌艺学徒',
            'alias' => '进阶歌艺学徒',
            'amount' => 1500,
            'rank' => 4,
            'type' => Hierarchy::TYPE_USER,
        ]);

        $userFive = factory(\SingPlus\Domains\Hierarchy\Models\Hierarchy::class)->create([
            'name' => '实力歌艺学徒',
            'alias' => '实力歌艺学徒',
            'amount' => 3000,
            'rank' => 5,
            'type' => Hierarchy::TYPE_USER,
        ]);

        $userSix = factory(\SingPlus\Domains\Hierarchy\Models\Hierarchy::class)->create([
            'name' => '新晋选秀歌手',
            'alias' => '新晋选秀歌手',
            'amount' => 5000,
            'rank' => 6,
            'type' => Hierarchy::TYPE_USER,
        ]);

        $userSeven = factory(\SingPlus\Domains\Hierarchy\Models\Hierarchy::class)->create([
            'name' => '进阶选秀歌手',
            'alias' => '进阶选秀歌手',
            'amount' => 8000,
            'rank' => 7,
            'type' => Hierarchy::TYPE_USER,
        ]);

        $userEight = factory(\SingPlus\Domains\Hierarchy\Models\Hierarchy::class)->create([
            'name' => '实力选秀歌手',
            'alias' => '实力选秀歌手',
            'amount' => 12000,
            'rank' => 8,
            'type' => Hierarchy::TYPE_USER,
        ]);

        $userNine = factory(\SingPlus\Domains\Hierarchy\Models\Hierarchy::class)->create([
            'name' => '新晋闪亮明星',
            'alias' => '新晋闪亮明星',
            'amount' => 18000,
            'rank' => 9,
            'type' => Hierarchy::TYPE_USER,
        ]);

        $userTen = factory(\SingPlus\Domains\Hierarchy\Models\Hierarchy::class)->create([
            'name' => '进阶闪亮明星',
            'alias' => '进阶闪亮明星',
            'amount' => 26000,
            'rank' => 10,
            'type' => Hierarchy::TYPE_USER,
        ]);

        $userElev = factory(\SingPlus\Domains\Hierarchy\Models\Hierarchy::class)->create([
            'name' => '超级闪亮明星',
            'alias' => '超级闪亮明星',
            'amount' => 36000,
            'rank' => 11,
            'type' => Hierarchy::TYPE_USER,
        ]);


        $wealthOne = factory(\SingPlus\Domains\Hierarchy\Models\Hierarchy::class)->create([
            'name' => '奋斗中',
            'alias' => '奋斗中',
            'amount' => 0,
            'rank' => 1,
            'type' => Hierarchy::TYPE_WEALTH,
        ]);


        $wealthTwo = factory(\SingPlus\Domains\Hierarchy\Models\Hierarchy::class)->create([
            'name' => '三等布衣',
            'alias' => '三等布衣',
            'amount' => 1,
            'rank' => 2,
            'type' => Hierarchy::TYPE_WEALTH,
        ]);

        $wealthThree = factory(\SingPlus\Domains\Hierarchy\Models\Hierarchy::class)->create([
            'name' => '二等布衣',
            'alias' => '二等布衣',
            'amount' => 500,
            'rank' => 3,
            'type' => Hierarchy::TYPE_WEALTH,
        ]);

        $wealthFour = factory(\SingPlus\Domains\Hierarchy\Models\Hierarchy::class)->create([
            'name' => '一等布衣',
            'alias' => '一等布衣',
            'amount' => 2000,
            'rank' => 4,
            'type' => Hierarchy::TYPE_WEALTH,
        ]);

        $wealthFive = factory(\SingPlus\Domains\Hierarchy\Models\Hierarchy::class)->create([
            'name' => '三等骑士',
            'alias' => '三等骑士',
            'amount' => 5000,
            'rank' => 5,
            'type' => Hierarchy::TYPE_WEALTH,
        ]);

        $wealthSix = factory(\SingPlus\Domains\Hierarchy\Models\Hierarchy::class)->create([
            'name' => '二等骑士',
            'alias' => '二等骑士',
            'amount' => 10000,
            'rank' => 6,
            'type' => Hierarchy::TYPE_WEALTH,
        ]);

        $wealthSeven = factory(\SingPlus\Domains\Hierarchy\Models\Hierarchy::class)->create([
            'name' => '一等骑士',
            'alias' => '一等骑士',
            'amount' => 20000,
            'rank' => 7,
            'type' => Hierarchy::TYPE_WEALTH,
        ]);

        $wealthEight = factory(\SingPlus\Domains\Hierarchy\Models\Hierarchy::class)->create([
            'name' => '玄铁骑士',
            'alias' => '玄铁骑士',
            'amount' => 50000,
            'rank' => 8,
            'type' => Hierarchy::TYPE_WEALTH,
        ]);

        return (object)[
            'popularity' => (object)[
                'one' => $userOne,
                'two' =>$userTwo,
                'three' => $userThree,
                'four' => $userFour,
                'five'  => $userFive,
                'six'   => $userSix,
                'seven' => $userSeven,
                'eight' => $userEight,
                'nine'  => $userNine,
                'ten'   => $userTen,
                'elev'  =>$userElev
            ],
            'wealth' => (object)[
                'one' => $wealthOne,
                'two' =>$wealthTwo,
                'three' => $wealthThree,
                'four' => $wealthFour,
                'five'  => $wealthFive,
                'six'   => $wealthSix,
                'seven' => $wealthSeven,
                'eight' => $wealthEight,
            ]
        ];
    }

}