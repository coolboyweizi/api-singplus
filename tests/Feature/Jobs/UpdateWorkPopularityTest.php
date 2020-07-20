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
use SingPlus\Jobs\UpdateWorkPopularity;

class UpdateWorkPopularityTest extends TestCase
{
    use MongodbClearTrait;

    public function testUpdateSuccess(){
        $hierarchyList = $this->prepareHierarchyList();
        $user = factory(\SingPlus\Domains\Users\Models\User::class)->create();
        $userProfile = factory(\SingPlus\Domains\Users\Models\UserProfile::class)->create([
            'user_id'   => $user->id,
            'nickname'  => 'zhangsan',
        ]);

        $work = factory(\SingPlus\Domains\Works\Models\Work::class)->create([
            'user_id' => $user->id
        ]);

        $job = (new UpdateWorkPopularity($work->id))->onQueue('sing_plus_hierarchy_update');
        $job->handle($this->app->make(\SingPlus\Contracts\Hierarchy\Services\PopularityHierarchyService::class));


        $this->assertDatabaseHas('user_profiles', [
            '_id'             => $userProfile->id,
            'user_id'         => $user->id,
            'popularity_info'  => [
                'work_popularity' => 0,
                'hierarchy_id' => $hierarchyList->popularity->one->id,
                'hierarchy_gap'  => $hierarchyList->popularity->two->amount,
            ]
        ]);
    }

    public function testUpdateSuccess_WithPopularityInfo(){
        $hierarchyList = $this->prepareHierarchyList();
        $user = factory(\SingPlus\Domains\Users\Models\User::class)->create();
        $userProfile = factory(\SingPlus\Domains\Users\Models\UserProfile::class)->create([
            'user_id'   => $user->id,
            'nickname'  => 'zhangsan',
            'popularity_info'  => [
                'work_popularity' => 200,
                'hierarchy_id' => $hierarchyList->popularity->one->id,
                'hierarchy_gap'  => 300,
            ]
        ]);

        $work = factory(\SingPlus\Domains\Works\Models\Work::class)->create([
            'user_id' => $user->id,
            'listen_count' => 100,
            'favourite_count' => 100,
            'comment_count' => 100,
            'duration' => 80000,
            'gift_info' => [
                'gift_popularity_amount' => 1000
            ],
            'popularity' => 200,
        ]);

        $job = (new UpdateWorkPopularity($work->id))->onQueue('sing_plus_hierarchy_update');
        $job->handle($this->app->make(\SingPlus\Contracts\Hierarchy\Services\PopularityHierarchyService::class));


        $this->assertDatabaseHas('user_profiles', [
            '_id'             => $userProfile->id,
            'user_id'         => $user->id,
            'popularity_info'  => [
                'work_popularity' => 1220,
                'hierarchy_id' => $hierarchyList->popularity->three->id,
                'hierarchy_gap'  => 280,
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
            'display_order' => 8,
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