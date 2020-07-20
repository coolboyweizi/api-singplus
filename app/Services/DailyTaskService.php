<?php
/**
 * Created by PhpStorm.
 * User: karl
 * Date: 2018/1/25
 * Time: 下午6:12
 */

namespace SingPlus\Services;

use SingPlus\Contracts\Coins\Constants\Trans;
use SingPlus\Contracts\Coins\Services\AccountService as AccountServiceContract;
use SingPlus\Contracts\DailyTask\Services\DailyTaskService as DailyTaskServiceContract;
use SingPlus\Contracts\DailyTask\Constants\DailyTask as DailyTaskConstant;
use SingPlus\Domains\DailyTask\Models\DailyTask;
use SingPlus\Domains\DailyTask\Models\Task;

class DailyTaskService
{

    /**
     * @var DailyTaskServiceContract
     */
    private $dailyTaskService;

    /**
     * @var AccountServiceContract
     */
    private $accountService;

    public function __construct(DailyTaskServiceContract $dailyTaskService,
                                AccountServiceContract $accountService)
    {
        $this->dailyTaskService = $dailyTaskService;
        $this->accountService = $accountService;
    }

    /**
     * @param string $userId
     * @return \stdClass
     *              - tasks Collection
     *              - totalCoins    int the coin balance of a user
     */
    public function getDailyTaskLists(string $userId):\stdClass{
        $tasks = $this->dailyTaskService->getDailyTaskLists($userId);
        $coins = $this->accountService->getUserBalance($userId);
        return (object)[
            'tasks' => $tasks,
            'totalCoins' => $coins
        ];
    }

    /**
     * @param string $userId
     * @param string $type
     * @param string $taskId
     * @return null|\stdClass
     *                  - totalCoins    int  the user's coin balance
     *                  - status        int  the task's status
     *                  - value         int  the award coins after finished the task
     */
    public function getDailyTaskAward(string $userId, string $type, string $taskId) :?\stdClass{

        $dailyTask = $this->dailyTaskService->getDailyTaskDetail($userId, $type, $taskId);

        if ($dailyTask){
            if ($type == DailyTaskConstant::TYPE_SIGN_UP ){
                $needUpdate = $dailyTask->status == DailyTask::NEED_FINISH;
            }else {
                $needUpdate = $dailyTask->status == DailyTask::NEED_AWARD;
            }

            $taskDetail = $this->dailyTaskService->getTaskDetail(Task::CATEGORY_DAILY_TASK, $type);

            if (!$taskDetail){
                $needUpdate = false;
                $result = (object)[
                    'status' => $dailyTask->status,
                    'value' => 0
                ];
            }else {
                $result = (object)[
                    'status' => $dailyTask->status,
                    'value' => DailyTaskConstant::getDailyTaskValue($dailyTask->type, $taskDetail->value,
                        $taskDetail->valueStep, $taskDetail->maximumValue, $dailyTask->days),
                ];
            }

        }else {
            $needUpdate = false;
            $result = (object)[
              'status' => DailyTask::NEED_FINISH,
              'value' => 0,
            ];
        }

        if ($needUpdate){
            $result = $this->dailyTaskService->updateDailyTask($userId, $type, DailyTask::FINISHED, $taskId);
            if ($result->status == DailyTask::FINISHED){
                $coin = $this->accountService->deposit($userId, $result->value, Trans::SOURCE_DEPOSIT_TASK_DAILY, $userId,
                    (object)[
                    'history_id' => $result->historyId
                    ]);
            }
        }

        $coins = $this->accountService->getUserBalance($userId);
        return (object)[
            'totalCoins' => $coins,
            'status' => $result->status,
            'value'  => $result->value,
        ];
    }

    /**
     * @param string $userId
     * @return null|\stdClass
     *                  - totalCoins    int  the user's coin balance
     *                  - status        int  the task's status
     *                  - value         int  the award coins after finished the task
     */
    public function finishedInviteTask(string $userId) :?\stdClass{

        $result = $this->dailyTaskService->finisheDailyTask($userId, DailyTaskConstant::TYPE_INVITE);
        $coins = $this->accountService->getUserBalance($userId);
        return (object)[
            'totalCoins' => $coins,
            'status'     => $result->status,
            'value'      => $result->value,
        ];
    }

    /**
     * @param string $userId
     * @param null|string $countryAbbr
     */
    public function checkDailyTask(string $userId, ?string $countryAbbr){
        $this->dailyTaskService->resetDailyTaskLists($userId, $countryAbbr);
    }


}