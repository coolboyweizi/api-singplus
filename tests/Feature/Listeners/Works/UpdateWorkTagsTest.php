<?php
/**
 * Created by PhpStorm.
 * User: karl
 * Date: 2018/3/23
 * Time: 下午4:16
 */

namespace FeatureTest\SingPlus\Listeners\Works;

use Cache;
use FeatureTest\SingPlus\MongodbClearTrait;
use FeatureTest\SingPlus\TestCase;
use SingPlus\Events\Works\WorkUpdateTags;

class UpdateWorkTagsTest extends TestCase
{
    use MongodbClearTrait;

    public function testUpdateWorkTagsSuccess(){
        $user = factory(\SingPlus\Domains\Users\Models\User::class)->create();
        $work = factory(\SingPlus\Domains\Works\Models\Work::class)->create([
            'user_id' => $user->id,
            'description' => 'I love #love and singer #Aolo in #ThisFantasyWorld',
            'work_tags' => [
                '123sss',
                'abcd1'
            ]
        ]);

        $workTagOne = factory(\SingPlus\Domains\Works\Models\WorkTag::class)->create([
            'title' => 'love',
            'join_count' => 1,
        ]);

        $workTagTwo = factory(\SingPlus\Domains\Works\Models\WorkTag::class)->create([
            'title' => 'Aolo',
            'join_count' => 3,
        ]);

        $event = new WorkUpdateTags($work->id);
        $success = $this->getListener()->handle($event);

        $this->assertDatabaseHas('works', [
            'user_id' => $user->id,
            'description' => 'I love #love and singer #Aolo in #ThisFantasyWorld',
            'work_tags' => [
                'love',
                'Aolo',
                'ThisFantasyWorld'
            ]
        ]);

        $this->assertDatabaseHas('work_tags', [
            'title' => 'love',
            'join_count' => 2
        ]);

        $this->assertDatabaseHas('work_tags', [
            'title' => 'Aolo',
            'join_count' => 4
        ]);

        $this->assertDatabaseHas('work_tags', [
            'title' => 'ThisFantasyWorld',
            'join_count' => 1
        ]);
        $cacheKey = sprintf("workTag:joincount:%s", 'LOVE');
        self::assertTrue(2 == Cache::get($cacheKey));
        $cacheKey = sprintf("workTag:joincount:%s", 'AOLO');
        self::assertTrue(4 == Cache::get($cacheKey));
        $cacheKey = sprintf("workTag:joincount:%s", 'THISFANTASYWORLD');
        self::assertTrue(1 == Cache::get($cacheKey));
    }

    use MongodbClearTrait;

    public function testUpdateWorkTagsSuccess_WithCacheFound(){
        $user = factory(\SingPlus\Domains\Users\Models\User::class)->create();
        $work = factory(\SingPlus\Domains\Works\Models\Work::class)->create([
            'user_id' => $user->id,
            'description' => 'I love #love and singer #Aolo in #ThisFantasyWorld',
            'work_tags' => [
                '123sss',
                'abcd1'
            ]
        ]);

        $workTagOne = factory(\SingPlus\Domains\Works\Models\WorkTag::class)->create([
            'title' => 'love',
            'join_count' => 2,
        ]);

        $workTagTwo = factory(\SingPlus\Domains\Works\Models\WorkTag::class)->create([
            'title' => 'Aolo',
            'join_count' => 3,
        ]);

        $cacheKey = sprintf("workTag:joincount:%s", 'LOVE');
        Cache::put($cacheKey, 2, 60*60);

        $event = new WorkUpdateTags($work->id);
        $success = $this->getListener()->handle($event);

        $this->assertDatabaseHas('works', [
            'user_id' => $user->id,
            'description' => 'I love #love and singer #Aolo in #ThisFantasyWorld',
            'work_tags' => [
                'love',
                'Aolo',
                'ThisFantasyWorld'
            ]
        ]);

        $this->assertDatabaseHas('work_tags', [
            'title' => 'love',
            'join_count' => 3
        ]);

        $this->assertDatabaseHas('work_tags', [
            'title' => 'Aolo',
            'join_count' => 4
        ]);

        $this->assertDatabaseHas('work_tags', [
            'title' => 'ThisFantasyWorld',
            'join_count' => 1
        ]);
        $cacheKey = sprintf("workTag:joincount:%s", 'LOVE');
        self::assertTrue(3 == Cache::get($cacheKey));
        $cacheKey = sprintf("workTag:joincount:%s", 'AOLO');
        self::assertTrue(4 == Cache::get($cacheKey));
        $cacheKey = sprintf("workTag:joincount:%s", 'THISFANTASYWORLD');
        self::assertTrue(1 == Cache::get($cacheKey));
    }

    public function testUpdateWorkTagsSuccess_WithEmptyTags(){
        $user = factory(\SingPlus\Domains\Users\Models\User::class)->create();
        $work = factory(\SingPlus\Domains\Works\Models\Work::class)->create([
            'user_id' => $user->id,
            'description' => 'I love',
            'work_tags' => [
                '123sss',
                'abcd1'
            ]
        ]);

        $workTagOne = factory(\SingPlus\Domains\Works\Models\WorkTag::class)->create([
            'title' => 'love',
            'join_count' => 1,
        ]);

        $workTagTwo = factory(\SingPlus\Domains\Works\Models\WorkTag::class)->create([
            'title' => 'Aolo',
            'join_count' => 3,
        ]);

        $event = new WorkUpdateTags($work->id);
        $success = $this->getListener()->handle($event);

        $this->assertDatabaseHas('works', [
            'user_id' => $user->id,
            'description' => 'I love',
            'work_tags' => [
            ]
        ]);

        $this->assertDatabaseHas('work_tags', [
            'title' => 'love',
            'join_count' => 1
        ]);

        $this->assertDatabaseHas('work_tags', [
            'title' => 'Aolo',
            'join_count' => 3
        ]);

        $this->assertDatabaseMissing('work_tags', [
            'title' => 'ThisFantasyWorld',
            'join_count' => 1
        ]);
    }



    private function getListener()
    {
        return $this->app->make(\SingPlus\Listeners\Works\UpdateWorkTags::class);
    }
}