<?php
/**
 * Created by PhpStorm.
 * User: karl
 * Date: 2018/1/26
 * Time: 下午2:35
 */

namespace FeatureTest\SingPlus\Controllers;

use Carbon\Carbon;
use Mockery;
use Cache;
use FeatureTest\SingPlus\MongodbClearTrait;
use FeatureTest\SingPlus\TestCase;
use SingPlus\Contracts\Coins\Constants\Trans;
use SingPlus\Contracts\DailyTask\Constants\DailyTask as DailyTaskConstant;
use SingPlus\Domains\DailyTask\Models\DailyTask;
use SingPlus\Domains\DailyTask\Models\DailyTaskHistory;
use SingPlus\Domains\DailyTask\Models\Task;

class DailyTaskControllerTest extends TestCase
{
    use MongodbClearTrait;

    public function testGetDailyTaskListSuccess(){
        $this->enableNationOperationMiddleware();
        $data = $this->prepareDailyTasks();
        $tasksData = $this->prepareTasks();
        $user = $data->user;

        $date = Carbon::create(2018, 01, 01, 11, 30, 30);
        Carbon::setTestNow($date);

        $accountService = $this->mockAccountService();
        $accountService->shouldReceive('getUserBalance')
            ->once()
            ->with($user->id)
            ->andReturn(1000);

        $response = $this->actingAs($user)
            ->getJson('v3/dailyTask/list', ['X-CountryAbbr' => 'CN'])
            ->assertJson(['code' => 0]);
        $response = json_decode($response->getContent());
        $tasks = $response->data->tasks;
        self::assertCount(6, $tasks);
        self::assertEquals(1000, $response->data->totalCoins);
        self::assertEquals('daily_task_a_signup', $tasks[0]->type);
        self::assertEquals('daily_task_b_publish', $tasks[1]->type);
        self::assertEquals('daily_task_c_share', $tasks[2]->type);
        self::assertEquals('daily_task_d_comment', $tasks[3]->type);
        self::assertEquals('daily_task_e_invite', $tasks[4]->type);
    }

    public function testGetDailyTaskListSuccess_IncreateOne(){
        $this->enableNationOperationMiddleware();
        $data = $this->prepareDailyTasks();
        $tasksData = $this->prepareTasks();
        $user = $data->user;

        $taskSeven = factory(\SingPlus\Domains\DailyTask\Models\Task::class)->create([
            'category' => Task::CATEGORY_DAILY_TASK,
            'type'     => "daily_task_y_newtype",
            'value'    => 2,
            'value_step' => 0,
            'title'  => 'Gift',
            'desc'   => 'Send gifts to your favourite songs',
            'maximum_value' => 11,
            'status'   => 1]);

        $date = Carbon::create(2018, 01, 01, 11, 30, 30);
        Carbon::setTestNow($date);

        $accountService = $this->mockAccountService();
        $accountService->shouldReceive('getUserBalance')
            ->once()
            ->with($user->id)
            ->andReturn(1000);

        $counterMock = Mockery::mock(\Illuminate\Contracts\Cache\Store::class);
        $counterMock->shouldReceive('increment')
            ->times(1)
            ->with('dailyTaskHistory', 100)
            ->andReturn(100);
        Cache::shouldReceive('driver')
            ->times(1)
            ->with('counter')
            ->andReturn($counterMock);

        $response = $this->actingAs($user)
            ->getJson('v3/dailyTask/list', ['X-CountryAbbr' => 'CN'])
            ->assertJson(['code' => 0]);
        $response = json_decode($response->getContent());
        $tasks = $response->data->tasks;
        self::assertCount(7, $tasks);
        self::assertEquals(1000, $response->data->totalCoins);
        self::assertEquals('daily_task_a_signup', $tasks[0]->type);
        self::assertEquals('daily_task_b_publish', $tasks[1]->type);
        self::assertEquals('daily_task_c_share', $tasks[2]->type);
        self::assertEquals('daily_task_d_comment', $tasks[3]->type);
        self::assertEquals('daily_task_e_invite', $tasks[4]->type);

        $dailyTaskHistory = DailyTaskHistory::where('user_id', $user->id)
            ->where('task_id', $tasks[6]->taskId)
            ->where('type', $tasks[6]->type)->first();

        $this->assertDatabaseHas('daily_task',[
            'user_id' => $user->id,
            'type' => 'daily_task_y_newtype',
            'finished_at' => '2018-01-01 11:30:30',
            'history_id'  => $dailyTaskHistory->id,
        ]);
    }

    public function testGetDailyTaskListSuccess_AutoCreate(){
        $this->enableNationOperationMiddleware();
        $tasksData = $this->prepareTasks();
        $user = factory(\SingPlus\Domains\Users\Models\User::class)->create();
        $userProfile = factory(\SingPlus\Domains\Users\Models\UserProfile::class)->create([
            'user_id' => $user->id,
        ]);
        $date = Carbon::create(2018, 01, 01, 11, 30, 30);
        Carbon::setTestNow($date);

        $accountService = $this->mockAccountService();
        $accountService->shouldReceive('getUserBalance')
            ->once()
            ->with($user->id)
            ->andReturn(1000);

        $counterMock = Mockery::mock(\Illuminate\Contracts\Cache\Store::class);
        $counterMock->shouldReceive('increment')
            ->times(6)
            ->with('dailyTaskHistory', 100)
            ->andReturn(100);
        Cache::shouldReceive('driver')
            ->times(6)
            ->with('counter')
            ->andReturn($counterMock);

        $response = $this->actingAs($user)
            ->getJson('v3/dailyTask/list', ['X-CountryAbbr' => 'CN'])
            ->assertJson(['code' => 0]);
        $response = json_decode($response->getContent());
        $tasks = $response->data->tasks;
        self::assertCount(6, $tasks);
        self::assertEquals(1000, $response->data->totalCoins);
        self::assertEquals('daily_task_a_signup', $tasks[0]->type);
        self::assertEquals('daily_task_b_publish', $tasks[1]->type);
        self::assertEquals('daily_task_c_share', $tasks[2]->type);
        self::assertEquals('daily_task_d_comment', $tasks[3]->type);
        self::assertEquals('daily_task_e_invite', $tasks[4]->type);
        self::assertEquals('daily_task_f_gift', $tasks[5]->type);

        $this->assertDatabaseHas('daily_task',[
            'user_id' => $user->id,
            'type' => 'daily_task_a_signup',
            'finished_at' => '2018-01-01 11:30:30'
        ]);

        $this->assertDatabaseHas('daily_task_history',[
            'user_id' => $user->id,
            'type' => 'daily_task_a_signup',
            'finished_at' => '2018-01-01 11:30:30'
        ]);
    }

    public function testGetDailyTaskListSuccess_ResetDailyTask(){
        $this->enableNationOperationMiddleware();
        $tasksData = $this->prepareTasks();
        $user = factory(\SingPlus\Domains\Users\Models\User::class)->create();
        $userProfile = factory(\SingPlus\Domains\Users\Models\UserProfile::class)->create([
            'user_id' => $user->id,
            'last_dailytask_at' => '2018-01-10 11:20:19'
        ]);
        $commentTask = factory(\SingPlus\Domains\DailyTask\Models\DailyTask::class)
            ->create([
                'type' => DailyTaskConstant::TYPE_COMMENT,
                'user_id' => $user->id,
                'finished_status' => DailyTask::NEED_AWARD,
                'finished_at' => '2018-01-10 11:20:19',
            ]);
        $date = Carbon::create(2018, 01, 10, 16, 30, 30);
        Carbon::setTestNow($date);
        $accountService = $this->mockAccountService();
        $accountService->shouldReceive('getUserBalance')
            ->once()
            ->with($user->id)
            ->andReturn(1000);

        $counterMock = Mockery::mock(\Illuminate\Contracts\Cache\Store::class);
        $counterMock->shouldReceive('increment')
            ->times(6)
            ->with('dailyTaskHistory', 100)
            ->andReturn(100);
        Cache::shouldReceive('driver')
            ->times(6)
            ->with('counter')
            ->andReturn($counterMock);

        $response = $this->actingAs($user)
            ->getJson('v3/dailyTask/list', ['X-CountryAbbr' => 'CN'])
            ->assertJson(['code' => 0]);
        $response = json_decode($response->getContent());
        $tasks = $response->data->tasks;
        self::assertCount(6, $tasks);
        $this->assertDatabaseHas('daily_task',[
            'user_id' => $user->id,
            'type' => DailyTaskConstant::TYPE_COMMENT,
            'finished_status' => DailyTask::NEED_FINISH,
            'finished_at' => '2018-01-10 11:20:19',
            'days'        => 2,
        ]);

        $this->assertDatabaseHas('daily_task_history', [
            'task_id' => $commentTask->id,
            'user_id' => $user->id,
            'type' => DailyTaskConstant::TYPE_COMMENT,
            'finished_status' => DailyTask::NEED_FINISH,
            'finished_at' => '2018-01-10 16:30:30',
            'days'        => 2,
        ]);

        $this->assertDatabaseHas('user_profiles', [
            'user_id' => $user->id,
            'last_dailytask_at'  => '2018-01-10 16:30:30'
        ]);

    }

    public function testGetDailyTaskAwardSuccess(){
        $this->enableNationOperationMiddleware();
        $tasksData = $this->prepareTasks();
        $user = factory(\SingPlus\Domains\Users\Models\User::class)->create();
        $userProfile = factory(\SingPlus\Domains\Users\Models\UserProfile::class)->create([
            'user_id' => $user->id,
            'last_dailytask_at' => '2018-01-10 11:20:19'
        ]);
        $date = Carbon::create(2018, 01, 10, 11, 30, 30);
        Carbon::setTestNow($date);

        $commentTaskHistory = factory(\SingPlus\Domains\DailyTask\Models\DailyTaskHistory::class)
            ->create([
                'type' => DailyTaskConstant::TYPE_COMMENT,
                'user_id' => $user->id,
                'finished_status' => DailyTask::NEED_AWARD,
                'finished_at' => '2018-01-10 11:20:19',
            ]);

        $commentTask = factory(\SingPlus\Domains\DailyTask\Models\DailyTask::class)
            ->create([
                'type' => DailyTaskConstant::TYPE_COMMENT,
                'user_id' => $user->id,
                'finished_status' => DailyTask::NEED_AWARD,
                'finished_at' => '2018-01-10 11:20:19',
                'history_id'  => $commentTaskHistory->id
            ]);
        $commentTaskHistory->task_id = $commentTask->id;
        $commentTaskHistory->save();


        $accountService = $this->mockAccountService();
        $accountService->shouldReceive('getUserBalance')
            ->once()
            ->with($user->id)
            ->andReturn(1000);
        $accountService->shouldReceive('deposit')
            ->once()
            ->with($user->id,
                DailyTaskConstant::getDailyTaskValue(DailyTaskConstant::TYPE_COMMENT,
                    $tasksData->comment->value, $tasksData->comment->value_step,
                    $tasksData->comment->maximum_value,1),
                Trans::SOURCE_DEPOSIT_TASK_DAILY,
                $user->id, Mockery::on(function ($data) use($commentTaskHistory) {
                    return $data instanceof \stdClass &&
                           $data->history_id == $commentTaskHistory->id;
                }))
            ->andReturn(DailyTaskConstant::getDailyTaskValue(DailyTaskConstant::TYPE_COMMENT,
                    $tasksData->comment->value, $tasksData->comment->value_step,
                    $tasksData->comment->maximum_value,1));

        $response = $this->actingAs($user)
            ->postJson('v3/dailyTask/dailyTaskAward',[
                'taskId' => $commentTask->id,
                'type'  => $commentTask->type
            ],['X-CountryAbbr' => 'CN'])
            ->assertJson(['code' => 0]);
        $response = json_decode($response->getContent());
        self::assertEquals(3, $response->data->status);
        self::assertEquals(1000, $response->data->totalCoins);
        $this->assertDatabaseHas('daily_task',[
            'user_id' => $user->id,
            'type' => DailyTaskConstant::TYPE_COMMENT,
            'finished_status' => DailyTask::FINISHED,
            'finished_at' => '2018-01-10 11:30:30',
        ]);

        $this->assertDatabaseHas('daily_task_history',[
            '_id'  => $commentTaskHistory->id,
            'user_id' => $user->id,
            'type' => DailyTaskConstant::TYPE_COMMENT,
            'finished_status' => DailyTask::FINISHED,
            'finished_at' => '2018-01-10 11:30:30',
        ]);
    }

    public function testGetDailyTaskAwardSuccess_Days3(){
        $this->enableNationOperationMiddleware();
        $tasksData = $this->prepareTasks();
        $user = factory(\SingPlus\Domains\Users\Models\User::class)->create();
        $userProfile = factory(\SingPlus\Domains\Users\Models\UserProfile::class)->create([
            'user_id' => $user->id,
            'last_dailytask_at' => '2018-01-10 11:20:19'
        ]);
        $date = Carbon::create(2018, 01, 10, 11, 30, 30);
        Carbon::setTestNow($date);

        $commentTaskHistory = factory(\SingPlus\Domains\DailyTask\Models\DailyTaskHistory::class)
            ->create([
                'type' => DailyTaskConstant::TYPE_COMMENT,
                'user_id' => $user->id,
                'finished_status' => DailyTask::NEED_AWARD,
                'finished_at' => '2018-01-10 11:20:19',
            ]);

        $commentTask = factory(\SingPlus\Domains\DailyTask\Models\DailyTask::class)
            ->create([
                'type' => DailyTaskConstant::TYPE_COMMENT,
                'user_id' => $user->id,
                'finished_status' => DailyTask::NEED_AWARD,
                'finished_at' => '2018-01-10 11:20:19',
                'days'        => 2,
                'history_id'  => $commentTaskHistory->id
            ]);

        $commentTaskHistory->task_id = $commentTask->id;
        $commentTaskHistory->save();

        $accountService = $this->mockAccountService();
        $accountService->shouldReceive('getUserBalance')
            ->once()
            ->with($user->id)
            ->andReturn(1000);
        $accountService->shouldReceive('deposit')
            ->once()
            ->with($user->id,
                DailyTaskConstant::getDailyTaskValue(DailyTaskConstant::TYPE_COMMENT,
                    $tasksData->comment->value, $tasksData->comment->value_step,
                    $tasksData->comment->maximum_value, 3),
                Mockery::any(),
                Mockery::any(), Mockery::on(function ($data) use($commentTaskHistory) {
                    return $data instanceof \stdClass &&
                        $data->history_id == $commentTaskHistory->id;
                }))
            ->andReturn(DailyTaskConstant::getDailyTaskValue(DailyTaskConstant::TYPE_COMMENT,
                    $tasksData->comment->value, $tasksData->comment->value_step,
                    $tasksData->comment->maximum_value,3));

        $response = $this->actingAs($user)
            ->postJson('v3/dailyTask/dailyTaskAward',[
                'taskId' => $commentTask->id,
                'type'  => $commentTask->type
            ],['X-CountryAbbr' => 'CN'])
            ->assertJson(['code' => 0]);
        $response = json_decode($response->getContent());
        self::assertEquals(3, $response->data->status);
        self::assertEquals(1000, $response->data->totalCoins);
        $this->assertDatabaseHas('daily_task',[
            'user_id' => $user->id,
            'type' => DailyTaskConstant::TYPE_COMMENT,
            'finished_status' => DailyTask::FINISHED,
            'finished_at' => '2018-01-10 11:30:30',
            '_id'         => $commentTask->id,
            'history_id'  => $commentTaskHistory->id
        ]);

        $this->assertDatabaseHas('daily_task_history',[
            'user_id' => $user->id,
            'type' => DailyTaskConstant::TYPE_COMMENT,
            'finished_status' => DailyTask::FINISHED,
            'finished_at' => '2018-01-10 11:30:30',
            '_id'         => $commentTaskHistory->id,
            'task_id'     => $commentTask->id
        ]);
    }

    public function testGetDailyTaskAwardFaild_TaskNeedFinished(){
        $this->enableNationOperationMiddleware();
        $tasksData = $this->prepareTasks();
        $user = factory(\SingPlus\Domains\Users\Models\User::class)->create();
        $userProfile = factory(\SingPlus\Domains\Users\Models\UserProfile::class)->create([
            'user_id' => $user->id,
            'last_dailytask_at' => '2018-01-10 11:20:19'
        ]);
        $date = Carbon::create(2018, 01, 10, 11, 30, 30);
        Carbon::setTestNow($date);
        $commentTask = factory(\SingPlus\Domains\DailyTask\Models\DailyTask::class)
            ->create([
                'type' => DailyTaskConstant::TYPE_COMMENT,
                'user_id' => $user->id,
                'finished_status' => DailyTask::NEED_FINISH,
                'finished_at' => '2018-01-10 11:20:19',
            ]);
        $accountService = $this->mockAccountService();
        $accountService->shouldReceive('getUserBalance')
            ->once()
            ->with($user->id)
            ->andReturn(1000);
        $accountService->shouldReceive('deposit')
            ->never();
        $response = $this->actingAs($user)
            ->postJson('v3/dailyTask/dailyTaskAward',[
               'taskId' => $commentTask->id,
                'type'  => $commentTask->type
            ],['X-CountryAbbr' => 'CN'])->assertJson(['code' => 0]);
        $response = json_decode($response->getContent());
        self::assertEquals(1, $response->data->status);
        self::assertEquals(1000, $response->data->totalCoins);
        $this->assertDatabaseHas('daily_task',[
           'user_id' =>$user->id,
            'type' => DailyTaskConstant::TYPE_COMMENT,
            'finished_status' => DailyTask::NEED_FINISH,
            'finished_at' => '2018-01-10 11:20:19',
        ]);
    }


    public function testGetDailyTaskAwardFailed_TaskFinished(){
        $this->enableNationOperationMiddleware();
        $tasksData = $this->prepareTasks();
        $user = factory(\SingPlus\Domains\Users\Models\User::class)->create();
        $userProfile = factory(\SingPlus\Domains\Users\Models\UserProfile::class)->create([
            'user_id' => $user->id,
            'last_dailytask_at' => '2018-01-10 11:20:19'
        ]);
        $date = Carbon::create(2018, 01, 10, 11, 30, 30);
        Carbon::setTestNow($date);
        $commentTask = factory(\SingPlus\Domains\DailyTask\Models\DailyTask::class)
            ->create([
                'type' => DailyTaskConstant::TYPE_COMMENT,
                'user_id' => $user->id,
                'finished_status' => DailyTask::FINISHED,
                'finished_at' => '2018-01-10 11:20:19',
            ]);
        $accountService = $this->mockAccountService();
        $accountService->shouldReceive('getUserBalance')
            ->once()
            ->with($user->id)
            ->andReturn(1000);
        $accountService->shouldReceive('deposit')
            ->never();
        $response = $this->actingAs($user)
            ->postJson('v3/dailyTask/dailyTaskAward',[
                'taskId' => $commentTask->id,
                'type'  => $commentTask->type
            ],['X-CountryAbbr' => 'CN'])
            ->assertJson(['code' => 0]);
        $response = json_decode($response->getContent());
        self::assertEquals(3, $response->data->status);
        self::assertEquals(1000, $response->data->totalCoins);
        $this->assertDatabaseHas('daily_task',[
            'user_id' =>$user->id,
            'type' => DailyTaskConstant::TYPE_COMMENT,
            'finished_status' => DailyTask::FINISHED,
            'finished_at' => '2018-01-10 11:20:19',
        ]);
    }

    public function testGetDailyTaskAwardSuccess_TaskNotFinishedForSignUp(){
        $this->enableNationOperationMiddleware();
        $tasksData = $this->prepareTasks();
        $user = factory(\SingPlus\Domains\Users\Models\User::class)->create();
        $userProfile = factory(\SingPlus\Domains\Users\Models\UserProfile::class)->create([
            'user_id' => $user->id,
            'last_dailytask_at' => '2018-01-10 11:20:19'
        ]);
        $date = Carbon::create(2018, 01, 10, 11, 30, 30);
        Carbon::setTestNow($date);

        $commentTaskHistory = factory(\SingPlus\Domains\DailyTask\Models\DailyTaskHistory::class)
            ->create([
                'type' => DailyTaskConstant::TYPE_COMMENT,
                'user_id' => $user->id,
                'finished_status' => DailyTask::NEED_AWARD,
                'finished_at' => '2018-01-10 11:20:19',
            ]);

        $commentTask = factory(\SingPlus\Domains\DailyTask\Models\DailyTask::class)
            ->create([
                'type' => DailyTaskConstant::TYPE_SIGN_UP,
                'user_id' => $user->id,
                'finished_status' => DailyTask::NEED_FINISH,
                'finished_at' => '2018-01-10 11:20:19',
                'history_id'  => $commentTaskHistory->id,
            ]);
        $commentTaskHistory->task_id = $commentTask->id;
        $commentTaskHistory->save();

        $accountService = $this->mockAccountService();
        $accountService->shouldReceive('getUserBalance')
            ->once()
            ->with($user->id)
            ->andReturn(1000);
        $accountService->shouldReceive('deposit')
            ->once()
            ->with($user->id,
                DailyTaskConstant::getDailyTaskValue(DailyTaskConstant::TYPE_SIGN_UP,
                    $tasksData->signup->value, $tasksData->signup->value_step,
                    $tasksData->signup->maximum_value, 1),
                Trans::SOURCE_DEPOSIT_TASK_DAILY,
                $user->id, Mockery::on(function ($data) use($commentTaskHistory) {
                    return $data instanceof \stdClass &&
                        $data->history_id == $commentTaskHistory->id;
                }))
            ->andReturn(DailyTaskConstant::getDailyTaskValue(DailyTaskConstant::TYPE_SIGN_UP,
                    $tasksData->signup->value, $tasksData->signup->value_step,
                    $tasksData->signup->maximum_value, 1));
        $response = $this->actingAs($user)
            ->postJson('v3/dailyTask/dailyTaskAward',[
                'taskId' => $commentTask->id,
                'type'  => $commentTask->type
            ],['X-CountryAbbr' => 'CN'])->assertJson(['code' => 0]);
        $response = json_decode($response->getContent());
        self::assertEquals(3, $response->data->status);
        self::assertEquals(1000, $response->data->totalCoins);
        $this->assertDatabaseHas('daily_task',[
            'user_id' =>$user->id,
            'type' => DailyTaskConstant::TYPE_SIGN_UP,
            'finished_status' => DailyTask::FINISHED,
            'finished_at' => '2018-01-10 11:30:30',
        ]);
    }

    public function testGetDailyTaskAwardFailed_TaskFinishedForSignUp(){
        $this->enableNationOperationMiddleware();
        $tasksData = $this->prepareTasks();
        $user = factory(\SingPlus\Domains\Users\Models\User::class)->create();
        $userProfile = factory(\SingPlus\Domains\Users\Models\UserProfile::class)->create([
            'user_id' => $user->id,
            'last_dailytask_at' => '2018-01-10 11:20:19'
        ]);
        $date = Carbon::create(2018, 01, 10, 11, 30, 30);
        Carbon::setTestNow($date);
        $commentTask = factory(\SingPlus\Domains\DailyTask\Models\DailyTask::class)
            ->create([
                'type' => DailyTaskConstant::TYPE_SIGN_UP,
                'user_id' => $user->id,
                'finished_status' => DailyTask::FINISHED,
                'finished_at' => '2018-01-10 11:20:19',
            ]);
        $accountService = $this->mockAccountService();
        $accountService->shouldReceive('getUserBalance')
            ->once()
            ->with($user->id)
            ->andReturn(1000);
        $accountService->shouldReceive('deposit')
            ->never()
            ->with($user->id,
                DailyTaskConstant::getDailyTaskValue(DailyTaskConstant::TYPE_SIGN_UP,
                    $tasksData->signup->value, $tasksData->signup->value_step,
                    $tasksData->signup->maximum_value, 1),
                Trans::SOURCE_DEPOSIT_TASK_DAILY,
                $user->id,null)
            ->andReturn(DailyTaskConstant::getDailyTaskValue(DailyTaskConstant::TYPE_SIGN_UP,
                    $tasksData->signup->value, $tasksData->signup->value_step,
                     $tasksData->signup->maximum_value,1));
        $response = $this->actingAs($user)
            ->postJson('v3/dailyTask/dailyTaskAward',[
                'taskId' => $commentTask->id,
                'type'  => $commentTask->type
            ],['X-CountryAbbr' => 'CN'])
            ->assertJson(['code' => 0]);
        $response = json_decode($response->getContent());
        self::assertEquals(3, $response->data->status);
        self::assertEquals(1000, $response->data->totalCoins);
        $this->assertDatabaseHas('daily_task',[
            'user_id' =>$user->id,
            'type' => DailyTaskConstant::TYPE_SIGN_UP,
            'finished_status' => DailyTask::FINISHED,
            'finished_at' => '2018-01-10 11:20:19',
        ]);
    }

    public function testGetDailyTaskAwardFailed_TaskBeReset(){
        $this->enableNationOperationMiddleware();
        $tasksData = $this->prepareTasks();
        $user = factory(\SingPlus\Domains\Users\Models\User::class)->create();
        $userProfile = factory(\SingPlus\Domains\Users\Models\UserProfile::class)->create([
            'user_id' => $user->id,
            'last_dailytask_at' => '2018-01-10 11:20:19'
        ]);
        $date = Carbon::create(2018, 01, 10, 16, 30, 30);
        Carbon::setTestNow($date);

        $commentTaskHistory = factory(\SingPlus\Domains\DailyTask\Models\DailyTaskHistory::class)
            ->create([
                'type' => DailyTaskConstant::TYPE_COMMENT,
                'user_id' => $user->id,
                'finished_status' => DailyTask::NEED_AWARD,
                'finished_at' => '2018-01-10 11:20:19',
                'days' => 1,
            ]);

        $commentTask = factory(\SingPlus\Domains\DailyTask\Models\DailyTask::class)
            ->create([
                'type' => DailyTaskConstant::TYPE_COMMENT,
                'user_id' => $user->id,
                'finished_status' => DailyTask::NEED_AWARD,
                'finished_at' => '2018-01-10 11:20:19',
                'days' => 1,
                'history_id' => $commentTaskHistory->id,
            ]);

        $commentTaskHistory->task_id = $commentTask->id;
        $commentTaskHistory->save();

        $counterMock = Mockery::mock(\Illuminate\Contracts\Cache\Store::class);
        $counterMock->shouldReceive('increment')
            ->times(6)
            ->with('dailyTaskHistory', 100)
            ->andReturn(100);
        Cache::shouldReceive('driver')
            ->times(6)
            ->with('counter')
            ->andReturn($counterMock);

        $accountService = $this->mockAccountService();
        $accountService->shouldReceive('getUserBalance')
            ->once()
            ->with($user->id)
            ->andReturn(1000);
        $accountService->shouldReceive('deposit')
            ->never()
            ->with($user->id,
                DailyTaskConstant::getDailyTaskValue(DailyTaskConstant::TYPE_COMMENT,
                    $tasksData->comment->value, $tasksData->comment->value_step,
                    $tasksData->comment->maximum_value,1),
                Trans::SOURCE_DEPOSIT_TASK_DAILY,
                $user->id, null)
            ->andReturn(DailyTaskConstant::getDailyTaskValue(DailyTaskConstant::TYPE_COMMENT,
                $tasksData->comment->value, $tasksData->comment->value_step,
                $tasksData->comment->maximum_value,1));
        $response = $this->actingAs($user)
            ->postJson('v3/dailyTask/dailyTaskAward',[
                'taskId' => $commentTask->id,
                'type'  => $commentTask->type
            ],['X-CountryAbbr' => 'CN'])
            ->assertJson(['code' => 0]);
        $response = json_decode($response->getContent());
        self::assertEquals(1, $response->data->status);
        self::assertEquals(1000, $response->data->totalCoins);
        $this->assertDatabaseHas('daily_task',[
            'user_id' =>$user->id,
            'type' => DailyTaskConstant::TYPE_COMMENT,
            'finished_status' => DailyTask::NEED_FINISH,
            'finished_at' => '2018-01-10 11:20:19',
            'days' => 2,
        ]);

        $this->assertDatabaseHas('daily_task_history',[
            'user_id' =>$user->id,
            'type' => DailyTaskConstant::TYPE_COMMENT,
            'finished_status' => DailyTask::NEED_FINISH,
            'finished_at' => '2018-01-10 16:30:30',
            'days' => 2,
            'task_id' => $commentTask->id,
        ]);
    }

    public function testFinishedInviteSuccess(){
        $this->enableNationOperationMiddleware();
        $tasksData = $this->prepareTasks();
        $user = factory(\SingPlus\Domains\Users\Models\User::class)->create();
        $userProfile = factory(\SingPlus\Domains\Users\Models\UserProfile::class)->create([
            'user_id' => $user->id,
            'last_dailytask_at' => '2018-01-10 11:20:19',
        ]);
        $date = Carbon::create(2018, 01, 10, 11, 30, 30);
        Carbon::setTestNow($date);

        $inviteTaskHistory = factory(\SingPlus\Domains\DailyTask\Models\DailyTaskHistory::class)
            ->create([
                'type' => DailyTaskConstant::TYPE_INVITE,
                'user_id' => $user->id,
                'finished_status' => DailyTask::NEED_FINISH,
                'finished_at'     => '2018-01-10 11:20:19',
                'days'            => 1,
            ]);

        $inviteTask = factory(\SingPlus\Domains\DailyTask\Models\DailyTask::class)
            ->create([
                'type' => DailyTaskConstant::TYPE_INVITE,
                'user_id' => $user->id,
                'finished_status' => DailyTask::NEED_FINISH,
                'finished_at'     => '2018-01-10 11:20:19',
                'days'            => 1,
                'history_id'      => $inviteTaskHistory->id
            ]);

        $inviteTaskHistory->task_id = $inviteTask->id;
        $inviteTaskHistory->save();

        $accountService = $this->mockAccountService();
        $accountService->shouldReceive('getUserBalance')
            ->once()
            ->with($user->id)
            ->andReturn(1000);
        $response = $this->actingAs($user)
            ->postJson('v3/dailyTask/dailyTaskInvite',[], ['X-CountryAbbr' => 'CN'])
            ->assertJson(['code' => 0]);
        $response = json_decode($response->getContent());
        self::assertEquals(2, $response->data->status);
        self::assertEquals(1000, $response->data->totalCoins);
        $this->assertDatabaseHas('daily_task',[
           'user_id' => $user->id,
           'type'    => DailyTaskConstant::TYPE_INVITE,
           'finished_status'  => DailyTask::NEED_AWARD,
           'finished_at'      => '2018-01-10 11:30:30',
           'history_id'       => $inviteTaskHistory->id,
        ]);

        $this->assertDatabaseHas('daily_task_history',[
            'user_id' => $user->id,
            'type'    => DailyTaskConstant::TYPE_INVITE,
            'finished_status'  => DailyTask::NEED_AWARD,
            'finished_at'      => '2018-01-10 11:30:30',
            'task_id'       => $inviteTask->id,
        ]);
    }

    public function testFinishedInviteFailed_NEED_AWARD(){
        $this->enableNationOperationMiddleware();
        $tasksData = $this->prepareTasks();
        $user = factory(\SingPlus\Domains\Users\Models\User::class)->create();
        $userProfile = factory(\SingPlus\Domains\Users\Models\UserProfile::class)->create([
            'user_id' => $user->id,
            'last_dailytask_at' => '2018-01-10 11:20:19'
        ]);
        $date = Carbon::create(2018, 01, 10, 11, 30, 30);
        Carbon::setTestNow($date);
        $inviteTask = factory(\SingPlus\Domains\DailyTask\Models\DailyTask::class)
            ->create([
                'type' => DailyTaskConstant::TYPE_INVITE,
                'user_id' => $user->id,
                'finished_status' => DailyTask::NEED_AWARD,
                'finished_at'     => '2018-01-10 11:20:19',
                'days'            => 1,
            ]);
        $accountService = $this->mockAccountService();
        $accountService->shouldReceive('getUserBalance')
            ->once()
            ->with($user->id)
            ->andReturn(1000);
        $response = $this->actingAs($user)
            ->postJson('v3/dailyTask/dailyTaskInvite', [],['X-CountryAbbr' => 'CN'])
            ->assertJson(['code' => 0]);
        $response = json_decode($response->getContent());
        self::assertEquals(2, $response->data->status);
        self::assertEquals(1000, $response->data->totalCoins);
        $this->assertDatabaseHas('daily_task',[
            'user_id' => $user->id,
            'type'    => DailyTaskConstant::TYPE_INVITE,
            'finished_status'  => DailyTask::NEED_AWARD,
            'finished_at'      => '2018-01-10 11:20:19',
        ]);
    }

    public function testFinishedInviteFailed_FINISHED(){
        $this->enableNationOperationMiddleware();
        $tasksData = $this->prepareTasks();
        $user = factory(\SingPlus\Domains\Users\Models\User::class)->create();
        $userProfile = factory(\SingPlus\Domains\Users\Models\UserProfile::class)->create([
            'user_id' => $user->id,
            'last_dailytask_at' => '2018-01-10 11:20:19'
        ]);
        $date = Carbon::create(2018, 01, 10, 11, 30, 30);
        Carbon::setTestNow($date);
        $inviteTask = factory(\SingPlus\Domains\DailyTask\Models\DailyTask::class)
            ->create([
                'type' => DailyTaskConstant::TYPE_INVITE,
                'user_id' => $user->id,
                'finished_status' => DailyTask::FINISHED,
                'finished_at'     => '2018-01-10 11:20:19',
                'days'            => 1,
            ]);
        $accountService = $this->mockAccountService();
        $accountService->shouldReceive('getUserBalance')
            ->once()
            ->with($user->id)
            ->andReturn(1000);
        $response = $this->actingAs($user)
            ->postJson('v3/dailyTask/dailyTaskInvite',[] ,['X-CountryAbbr' => 'CN'])
            ->assertJson(['code' => 0]);
        $response = json_decode($response->getContent());
        self::assertEquals(3, $response->data->status);
        self::assertEquals(1000, $response->data->totalCoins);
        $this->assertDatabaseHas('daily_task',[
            'user_id' => $user->id,
            'type'    => DailyTaskConstant::TYPE_INVITE,
            'finished_status'  => DailyTask::FINISHED,
            'finished_at'      => '2018-01-10 11:20:19',
        ]);
    }

    public function tetsFinishedInviteSuccess_ResetTask(){
        $this->enableNationOperationMiddleware();
        $tasksData = $this->prepareTasks();
        $user = factory(\SingPlus\Domains\Users\Models\User::class)->create();
        $userProfile = factory(\SingPlus\Domains\Users\Models\UserProfile::class)->create([
            'user_id' => $user->id,
            'last_dailytask_at' => '2018-01-10 11:20:19'
        ]);
        $date = Carbon::create(2018, 01, 10, 16, 30, 30);
        Carbon::setTestNow($date);
        $inviteTask = factory(\SingPlus\Domains\DailyTask\Models\DailyTask::class)
            ->create([
                'type' => DailyTaskConstant::TYPE_INVITE,
                'user_id' => $user->id,
                'finished_status' => DailyTask::FINISHED,
                'finished_at'     => '2018-01-10 11:20:19',
                'days'            => 1,
            ]);

        $counterMock = Mockery::mock(\Illuminate\Contracts\Cache\Store::class);
        $counterMock->shouldReceive('increment')
            ->times(6)
            ->with('dailyTaskHistory', 100)
            ->andReturn(100);
        Cache::shouldReceive('driver')
            ->times(6)
            ->with('counter')
            ->andReturn($counterMock);


        $accountService = $this->mockAccountService();
        $accountService->shouldReceive('getUserBalance')
            ->once()
            ->with($user->id)
            ->andReturn(1000);
        $response = $this->actingAs($user)
            ->postJson('v3/dailyTask/dailyTaskInvite',[] ,['X-CountryAbbr' => 'CN'])
            ->assertJson(['code' => 0]);
        $response = json_decode($response->getContent());
        self::assertEquals(3, $response->data->status);
        self::assertEquals(1000, $response->data->totalCoins);
        $this->assertDatabaseHas('daily_task',[
            'user_id' => $user->id,
            'type'    => DailyTaskConstant::TYPE_INVITE,
            'finished_status'  => DailyTask::NEED_AWARD,
            'finished_at'      => '2018-01-10 16:30:30',
            'days'             => 2,
        ]);

        $this->assertDatabaseHas('daily_task_history',[
            'user_id' => $user->id,
            'type'    => DailyTaskConstant::TYPE_INVITE,
            'finished_status'  => DailyTask::NEED_AWARD,
            'finished_at'      => '2018-01-10 16:30:30',
            'days'             => 2,
            'task_id'          => $inviteTask->id
        ]);
    }


    private function prepareDailyTasks(){
        $user = factory(\SingPlus\Domains\Users\Models\User::class)->create();
        $userProfile = factory(\SingPlus\Domains\Users\Models\UserProfile::class)->create([
           'user_id'           => $user->id,
           'last_dailytask_at'  => '2018-01-01 11:01:02',
        ]);

        $signUpTask = factory(\SingPlus\Domains\DailyTask\Models\DailyTask::class)
            ->create([
                'type' => DailyTaskConstant::TYPE_SIGN_UP,
                'user_id' => $user->id,
                'finished_at' => '2018-01-01 11:01:02',
            ]);

        $commentTask = factory(\SingPlus\Domains\DailyTask\Models\DailyTask::class)
            ->create([
                'type' => DailyTaskConstant::TYPE_COMMENT,
                'user_id' => $user->id,
                'finished_at' => '2018-01-01 11:01:02',
            ]);

        $publishTask = factory(\SingPlus\Domains\DailyTask\Models\DailyTask::class)
            ->create([
                'type' => DailyTaskConstant::TYPE_PUBLISH,
                'user_id' => $user->id,
                'finished_at' => '2018-01-01 11:01:02',
            ]);

        $shareTask = factory(\SingPlus\Domains\DailyTask\Models\DailyTask::class)
            ->create([
                'type' => DailyTaskConstant::TYPE_SHARE,
                'user_id' => $user->id,
                'finished_at' => '2018-01-01 11:01:02',
            ]);

        $inviteTask = factory(\SingPlus\Domains\DailyTask\Models\DailyTask::class)
            ->create([
                'type' => DailyTaskConstant::TYPE_INVITE,
                'user_id' => $user->id,
                'finished_at' => '2018-01-01 11:01:02',
            ]);

        $giftTask = factory(\SingPlus\Domains\DailyTask\Models\DailyTask::class)
            ->create([
                'type' => DailyTaskConstant::TYPE_GIFT,
                'user_id' => $user->id,
                'finished_at' => '2018-01-01 11:01:02',
            ]);

        return (object)[
            'user' => $user,
            'signup' => $signUpTask,
            'comment' => $commentTask,
            'publish' => $publishTask,
            'share'   => $shareTask,
            'invite'  => $inviteTask,
            'gift'  => $giftTask,
        ];
    }


    private function prepareTasks(){
        $taskOne = factory(\SingPlus\Domains\DailyTask\Models\Task::class)->create([
            'category' => Task::CATEGORY_DAILY_TASK,
            'type'     => DailyTaskConstant::TYPE_SIGN_UP,
            'value'    => 5,
            'value_step' => 1,
            'title'  => 'Day %u',
            'desc'   => 'Consecutive daily check-ins will get you extra coins',
            'maximum_value' => 11,
            'status'   => 1
        ]);

        $taskTwo = factory(\SingPlus\Domains\DailyTask\Models\Task::class)->create([
            'category' => Task::CATEGORY_DAILY_TASK,
            'type'     => DailyTaskConstant::TYPE_PUBLISH,
            'value'    => 10,
            'value_step' => 0,
            'title'  => 'Post',
            'desc'   => 'Sing and post at least 1 song publicly every day',
            'maximum_value' => 10,
            'status'   => 1
        ]);

        $taskThree = factory(\SingPlus\Domains\DailyTask\Models\Task::class)->create([
            'category' => Task::CATEGORY_DAILY_TASK,
            'type'     => DailyTaskConstant::TYPE_COMMENT,
            'value'    => 2,
            'value_step' => 0,
            'title'  => 'Comment',
            'desc'   => 'Comment on your favourite songs',
            'maximum_value' => 2,
            'status'   => 1
        ]);

        $taskFour = factory(\SingPlus\Domains\DailyTask\Models\Task::class)->create([
            'category' => Task::CATEGORY_DAILY_TASK,
            'type'     => DailyTaskConstant::TYPE_INVITE,
            'value'    => 2,
            'value_step' => 0,
            'title'  => 'Invite',
            'desc'   => 'Invite your friends to join you in Sing+ community',
            'maximum_value' => 2,
            'status'   => 1
        ]);

        $taskFive = factory(\SingPlus\Domains\DailyTask\Models\Task::class)->create([
            'category' => Task::CATEGORY_DAILY_TASK,
            'type'     => DailyTaskConstant::TYPE_SHARE,
            'value'    => 6,
            'value_step' => 0,
            'title'  => 'Share',
            'desc'   => 'Share your favourite songs to Facebook or WhatsApp',
            'maximum_value' => 6,
            'status'   => 1
        ]);

        $taskSix = factory(\SingPlus\Domains\DailyTask\Models\Task::class)->create([
            'category' => Task::CATEGORY_DAILY_TASK,
            'type'     => DailyTaskConstant::TYPE_GIFT,
            'value'    => 2,
            'value_step' => 0,
            'title'  => 'Gift',
            'desc'   => 'Send gifts to your favourite songs',
            'maximum_value' => 11,
            'status'   => 1
        ]);

        $taskSeven = factory(\SingPlus\Domains\DailyTask\Models\Task::class)->create([
            'category' => Task::CATEGORY_DAILY_TASK,
            'type'     => DailyTaskConstant::TYPE_GIFT,
            'value'    => 2,
            'value_step' => 0,
            'title'  => 'Gift',
            'desc'   => 'Send gifts to your favourite songs',
            'maximum_value' => 11,
            'status'   => 0
        ]);

        return (object)[
            'signup' => $taskOne,
            'publish'   => $taskTwo,
            'comment' => $taskThree,
            'invite'  => $taskFour,
            'share'  => $taskFive,
            'gift'   => $taskSix,
        ];
    }

    private function mockAccountService(){
        $accountService = Mockery::mock(\SingPlus\Contracts\Coins\Services\AccountService::class);
        $this->app[\SingPlus\Contracts\Coins\Services\AccountService::class ] = $accountService;
        return $accountService;
    }
}