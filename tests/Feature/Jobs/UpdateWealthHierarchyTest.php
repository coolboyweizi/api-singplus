<?php
/**
 * Created by PhpStorm.
 * User: karl
 * Date: 2018/3/2
 * Time: 上午9:41
 */

namespace FeatureTest\SingPlus\Jobs;

use FeatureTest\SingPlus\TestCase;
use FeatureTest\SingPlus\MongodbClearTrait;
use SingPlus\Domains\Hierarchy\Models\Hierarchy;
use SingPlus\Jobs\UpdateWealthHierarchy;

class UpdateWealthHierarchyTest extends TestCase
{
    use MongodbClearTrait;

    public function testUpdateSuccess(){
        $hierarchyList = $this->prepareHierarchyList();
        $user = factory(\SingPlus\Domains\Users\Models\User::class)->create();
        $userProfile = factory(\SingPlus\Domains\Users\Models\UserProfile::class)->create([
            'user_id'   => $user->id,
            'nickname'  => 'zhangsan',
        ]);

        $job = (new UpdateWealthHierarchy($user->id))->onQueue('sing_plus_hierarchy_update');
        $job->handle($this->app->make(\SingPlus\Contracts\Hierarchy\Services\WealthHierarchyService::class));

        $this->assertDatabaseHas('user_profiles', [
            '_id'             => $userProfile->id,
            'user_id'         => $user->id,
            'consume_coins_info'  => [
                'consume_coins' => 0,
                'hierarchy_id' => $hierarchyList->wealth->one->id,
                'hierarchy_gap'  => $hierarchyList->wealth->two->amount,
            ]
        ]);
    }

    public function testUpdateSuccess_MaxHierarchy(){
        $hierarchyList = $this->prepareHierarchyList();
        $user = factory(\SingPlus\Domains\Users\Models\User::class)->create();
        $userProfile = factory(\SingPlus\Domains\Users\Models\UserProfile::class)->create([
            'user_id'   => $user->id,
            'nickname'  => 'zhangsan',
            'consume_coins_info' => [
                'consume_coins' => 1000000000
            ]
        ]);

        $job = (new UpdateWealthHierarchy($user->id))->onQueue('sing_plus_hierarchy_update');
        $job->handle($this->app->make(\SingPlus\Contracts\Hierarchy\Services\WealthHierarchyService::class));

        $this->assertDatabaseHas('user_profiles', [
            '_id'             => $userProfile->id,
            'user_id'         => $user->id,
            'consume_coins_info'  => [
                'consume_coins' => 1000000000,
                'hierarchy_id' => $hierarchyList->wealth->eight->id,
                'hierarchy_gap'  => 0,
            ]
        ]);
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