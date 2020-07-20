<?php
/**
 * Created by PhpStorm.
 * User: karl
 * Date: 2018/5/12
 * Time: 下午10:48
 */

namespace FeatureTest\SingPlus\Jobs;


use Carbon\Carbon;
use FeatureTest\SingPlus\CheckActivityTrait;
use FeatureTest\SingPlus\MongodbClearTrait;
use FeatureTest\SingPlus\TestCase;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Queue;
use SingPlus\Activities\BoomsingCoinActivity;
use SingPlus\Jobs\Activities\BoomsingCoinActivityJob;
use SingPlus\Jobs\IMSendSimpleMsg;
use Cache;
use Mockery;
use SingPlus\Contracts\Coins\Constants\Trans;

class BoomsingCoinActivityTest extends TestCase
{
    use CheckActivityTrait;
    use MongodbClearTrait;

    public function testBoomsingCoinActvityJobSuccess(){
        $this->enableActivityCheckMiddleware();
        Config::set('apiChannel.channel', 'boomsing');
        $date = Carbon::create(2018, 05, 12, 11, 30, 30);
        Carbon::setTestNow($date);
        $user = factory(\SingPlus\Domains\Users\Models\User::class)->create();
        $userProfile = factory(\SingPlus\Domains\Users\Models\UserProfile::class)->create([
            'user_id' => $user->id
        ]);
        Queue::fake();

        $response = $this->actingAs($user)
            ->postJson('v3/startup', [], [
                'X-CountryAbbr' => 'TZ',
            ])
            ->assertJson(['code'=>0]);

        Queue::assertPushed(BoomsingCoinActivityJob::class,function ($job) use ($user){
            return $job->userId = $user->id;
        });

    }

    public function testBoomsingCoinActivityJobFailed_WithoutUserLogin(){
        $this->enableActivityCheckMiddleware();
        Config::set('apiChannel.channel', 'boomsing');
        $date = Carbon::create(2018, 05, 12, 11, 30, 30);
        Carbon::setTestNow($date);
        $user = factory(\SingPlus\Domains\Users\Models\User::class)->create();
        $userProfile = factory(\SingPlus\Domains\Users\Models\UserProfile::class)->create([
            'user_id' => $user->id
        ]);
        Queue::fake();

        $response = $this->postJson('v3/startup', [], [
                'X-CountryAbbr' => 'TZ',
            ])
            ->assertJson(['code'=>0]);

        Queue::assertNotPushed(BoomsingCoinActivityJob::class,function ($job) use ($user){
            return $job->userId = $user->id;
        });
    }

    public function testBoomsingCoinActivityJobFailed_WithActivityBeenDone(){
        $this->enableActivityCheckMiddleware();
        Config::set('apiChannel.channel', 'boomsing');
        $date = Carbon::create(2018, 05, 12, 11, 30, 30);
        Carbon::setTestNow($date);
        $user = factory(\SingPlus\Domains\Users\Models\User::class)->create();
        $userProfile = factory(\SingPlus\Domains\Users\Models\UserProfile::class)->create([
            'user_id' => $user->id
        ]);
        Queue::fake();
        Cache::put(BoomsingCoinActivity::getCacheKey($user->id), "done", BoomsingCoinActivity::getEndTime());
        $response = $this->actingAs($user)->postJson('v3/startup', [], [
            'X-CountryAbbr' => 'TZ',
        ])
            ->assertJson(['code'=>0]);
        Queue::assertNotPushed(BoomsingCoinActivityJob::class,function ($job) use ($user){
            return $job->userId = $user->id;
        });
    }

    public function testBoomsingCoinActivityJobFailed_AfterEndTime(){
        $this->enableActivityCheckMiddleware();
        Config::set('apiChannel.channel', 'boomsing');
        $date = Carbon::create(2018, 06, 01, 00, 00, 00);
        Carbon::setTestNow($date);
        $user = factory(\SingPlus\Domains\Users\Models\User::class)->create();
        $userProfile = factory(\SingPlus\Domains\Users\Models\UserProfile::class)->create([
            'user_id' => $user->id
        ]);
        Queue::fake();

        $response = $this->actingAs($user)
            ->postJson('v3/startup', [], [
                'X-CountryAbbr' => 'TZ',
            ])
            ->assertJson(['code'=>0]);

        Queue::assertNotPushed(BoomsingCoinActivityJob::class,function ($job) use ($user){
            return $job->userId = $user->id;
        });
    }

    public function testBoomsingCoinActivityJobHandleSuccess(){
        $user = factory(\SingPlus\Domains\Users\Models\User::class)->create([
            'id' => "06ba3c45b6cb4de49b01296589515834"
        ]);
        $userProfile = factory(\SingPlus\Domains\Users\Models\UserProfile::class)->create([
            'user_id'   => $user->id,
            'nickname'  => 'zhangsan',
            'coins' => [
                'balance' => 100
            ]
        ]);

        $accountService = $this->mockAccountService();
        $accountService->shouldReceive('deposit')
            ->once()
            ->with($user->id,
                100,
                Trans::SOURCE_DEPOSIT_ADMIN_GIVE,
                config('txim.senders.Annoucements'),
                null)
            ->andReturn(200);

        Queue::fake();

        $job = (new BoomsingCoinActivityJob($user->id))->onQueue('sing_plus_hierarchy_update');
        $job->handle($this->app->make(\SingPlus\Services\ActivityService::class));

        Queue::assertPushed(IMSendSimpleMsg::class,function ($job) use ($user){
            return $job->receiver = $user->id && $job->sender = config('txim.senders.Contests');
        });
    }

    private function mockAccountService(){
        $accountService = Mockery::mock(\SingPlus\Contracts\Coins\Services\AccountService::class);
        $this->app[\SingPlus\Contracts\Coins\Services\AccountService::class ] = $accountService;
        return $accountService;
    }

}