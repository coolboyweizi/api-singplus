<?php
/**
 * Created by PhpStorm.
 * User: karl
 * Date: 2018/3/28
 * Time: 上午11:29
 */

namespace FeatureTest\SingPlus\Controllers;

use Carbon\Carbon;
use Cache;
use Mockery;
use FeatureTest\SingPlus\MongodbClearTrait;
use FeatureTest\SingPlus\TestCase;
use SingPlus\Contracts\DailyTask\Constants\DailyTask;
use SingPlus\Contracts\News\Constants\News;
use SingPlus\Domains\Friends\Models\UserFollowing;
use SingPlus\Domains\Friends\Repositories\UserFollowingRepository;
use SingPlus\Domains\Works\Models\Comment;
use SingPlus\Domains\Works\Models\Work;
use SingPlus\Events\Works\WorkUpdateCommentGiftCacheData;


class NewsControllerSpeedTest extends TestCase
{
    use MongodbClearTrait;

    public function testNewsListSpeedSuccess(){
        $user = factory(\SingPlus\Domains\Users\Models\User::class)->create();
        $userProfile = factory(\SingPlus\Domains\Users\Models\UserProfile::class)->create([
            'user_id' => $user->id,
            'nickname' => 'SinPa',
            'avatar' => 'avatar.png',
        ]);
        $userFollowings = factory(\SingPlus\Domains\Friends\Models\UserFollowing::class)->create([
            'user_id' => $user->id
        ]);

        $this->prepareUserDatas($user, 10);

        $response = $this->actingAs($user)
            ->getJson('v3/news/latest?' . http_build_query([
                    'self'  => false,
                ]))
            ->assertJson(['code' => 0]);
        $response = json_decode($response->getContent());

    }

    public function testGetMultiWorksCommentsAndGiftsSpeedSuccess(){
        $prepareDataStart = $this->getMillisecond();
        $workIds = $this->prepareWorksData();
        $user = factory(\SingPlus\Domains\Users\Models\User::class)->create();
        $userProfile = factory(\SingPlus\Domains\Users\Models\UserProfile::class)->create([
            'user_id' => $user->id,
            'nickname' => 'SinPa',
            'avatar' => 'avatar.png',
        ]);
        $prepareDataEnd = $this->getMillisecond();

        $response = $this->actingAs($user)
            ->getJson('v3/works/comments-gifts?' . http_build_query([
                    'workIds'  => $workIds,
                ]))
            ->assertJson(['code' => 0]);
        $requestEnd = $this->getMillisecond();

//        print sprintf("\n------prepareData Cost:--%.0f-------\n", $prepareDataEnd - $prepareDataStart);
//        print sprintf("\n------request Cost:--%.0f-------\n", $requestEnd - $prepareDataEnd);

        $prepareDataEnd = $this->getMillisecond();

        $response = $this->actingAs($user)
            ->getJson('v3/works/comments-gifts?' . http_build_query([
                    'workIds'  => $workIds,
                ]))
            ->assertJson(['code' => 0]);
        $requestEnd = $this->getMillisecond();

//        print sprintf("\n------request with cached Cost:--%.0f-------\n", $requestEnd - $prepareDataEnd);

        $userOne = factory(\SingPlus\Domains\Users\Models\User::class)->create();
        $userProfileOne = factory(\SingPlus\Domains\Users\Models\UserProfile::class)->create([
            'user_id' => $userOne->id
        ]);
        $comment = factory(\SingPlus\Domains\Works\Models\Comment::class)->create([
            'author_id' => $userOne->id,
            'replied_user_id' => $user->id,
            'work_id' => $workIds[0],
            'status' => Comment::STATUS_NORMAL,
            'content' => 'contsdsdent'
        ]);
        $event = new WorkUpdateCommentGiftCacheData($workIds[0], null,$comment->id );
        $success = $this->getListener()->handle($event);
        $requestEnd = $this->getMillisecond();
        $response = $this->actingAs($user)
            ->getJson('v3/works/comments-gifts?' . http_build_query([
                    'workIds'  => $workIds,
                ]))
            ->assertJson(['code' => 0]);
        $requestEndAll = $this->getMillisecond();
//        print sprintf("\n------request with cached Cost:--%.0f-------\n", $requestEndAll - $requestEnd);

    }

    private function prepareWorksData(): array{

        $count = 10;
        // gift
        $gifts = array();
        $users = array();
        $comments = array();
        $workIds = array();
        $giftContributions = array();
        $displayOrder = 100;

        for ($i = 0; $i < 40; $i++){
            $gift = factory(\SingPlus\Domains\Gifts\Models\Gift::class)->create([
                'type' => "type d",
                'name' => 'gift '.$i,
                'display_order'=> $displayOrder
            ]);
            $gifts[] = $gift;
            $displayOrder = $displayOrder + 100;
        }

        $displayOrder = 100;

        for ($i = 0; $i < $count; $i++){


            $user = factory(\SingPlus\Domains\Users\Models\User::class)->create();
            $userProfile = factory(\SingPlus\Domains\Users\Models\UserProfile::class)->create([
                'user_id' => $user->id,
                'nickname' => 'user '.$i
            ]);

            $work = factory(\SingPlus\Domains\Works\Models\Work::class)->create([
                'user_id' => $user->id,
                'name' => 'work '.$i,
                'status' => Work::STATUS_NORMAL,
                'display_order' => $displayOrder,
            ]);

            $commentDisplayOrder = $displayOrder * 4;

            for ($j = 0; $j < 4; $j++){
                $commentAuthor = factory(\SingPlus\Domains\Users\Models\User::class)->create();
                $userProfile = factory(\SingPlus\Domains\Users\Models\UserProfile::class)->create([
                    'user_id' => $commentAuthor->id,
                    'nickname' => sprintf('work %d comment user %d ', $i, $j)
                ]);

                $comment = factory(\SingPlus\Domains\Works\Models\Comment::class)->create([
                    'author_id' => $commentAuthor->id,
                    'replied_user_id' => $user->id,
                    'work_id' => $work->id,
                    'status' => Comment::STATUS_NORMAL,
                    'display_order' => $commentDisplayOrder + 100
                ]);

                $commentDisplayOrder = $commentDisplayOrder + 100;

            }

            for ($j = 0; $j < 4; $j++){
                $sender = factory(\SingPlus\Domains\Users\Models\User::class)->create();
                $userProfile = factory(\SingPlus\Domains\Users\Models\UserProfile::class)->create([
                    'user_id' => $sender->id,
                    'nickname' => sprintf('work %d giftContr user %d ', $i, $j)
                ]);
                $giftOne = $gifts[random_int(0, 9)];
                $giftTwo = $gifts[random_int(10, 19)];
                $giftThree = $gifts[random_int(20, 39)];

                $giftContribution = factory(\SingPlus\Domains\Gifts\Models\GiftContribution::class)->create([
                    'sender_id' => $sender->id,
                    'receiver_id' => $user->id,
                    'work_id' => $work->id,
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
            }
            $displayOrder = $displayOrder + 100;
            $workIds[] = $work->id;
        }

        return $workIds;
    }

    private function prepareUserDatas($user, int $followingCount){

        //prepare users
        $display_order = 100;
        $userFollowingRepo  = new UserFollowingRepository();
        $prepareDataStart = $this->getMillisecond();

        for ($i = 0; $i < $followingCount; $i++){
            $userOne = factory(\SingPlus\Domains\Users\Models\User::class)->create();
            factory(\SingPlus\Domains\Users\Models\UserProfile::class)->create([
                'user_id' => $userOne->id,
                'nickname' => 'user '.$i
            ]);

            $music = factory(\SingPlus\Domains\Musics\Models\Music::class)->create([
                'name' => 'music '.$i
            ]);

            $work = factory(\SingPlus\Domains\Works\Models\Work::class)->create([
                'user_id' => $userOne->id,
                'desc' => 'work desc '.$i,
                'name' => 'work name '.$i,
                'music_id' => $music->id,
                'status' => Work::STATUS_NORMAL
            ]);

            $news = factory(\SingPlus\Domains\News\Models\News::class)->create([
                'user_id' => $userOne->id,
                'type' => News::TYPE_PUBLISH,
                'status' => \SingPlus\Domains\News\Models\News::STATUS_NORMAL,
                'detail' => [
                    'work_id' => $work->id
                ],
                'display_order' => $display_order
            ]);

            $userFollowingRepo->addFollowingForUser($user->id, $userOne->id);

            $display_order = $display_order + 100;
        }
//        print sprintf("\n------prepareDataCost--%.0f-------\n", $this->getMillisecond() - $prepareDataStart);

    }

    function getMillisecond() {
        list($s1, $s2) = explode(' ', microtime());
        return (float)sprintf('%.0f', (floatval($s1) + floatval($s2)) * 1000);
    }

    private function mockDailyTaskService(){
        $dailyTaskService = Mockery::mock(\SingPlus\Contracts\DailyTask\Services\DailyTaskService::class);
        $this->app[\SingPlus\Contracts\DailyTask\Services\DailyTaskService::class ] = $dailyTaskService;
        return $dailyTaskService;
    }

    private function mockPopularityHierarchyService(){
        $popularityHierarchyService = Mockery::mock(\SingPlus\Contracts\Hierarchy\Services\PopularityHierarchyService::class);
        $this->app[\SingPlus\Contracts\Hierarchy\Services\PopularityHierarchyService::class ] = $popularityHierarchyService;
        return $popularityHierarchyService;
    }

    private function getListener()
    {
        return $this->app->make(\SingPlus\Listeners\Works\UpdateWorkCommentGiftCacheData::class);
    }

}