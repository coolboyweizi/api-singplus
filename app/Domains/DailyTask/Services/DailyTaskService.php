<?php

/**
 * Created by PhpStorm.
 * User: karl
 * Date: 2018/1/24
 * Time: 下午4:20
 */
namespace SingPlus\Domains\DailyTask\Services;

use Cache;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use SingPlus\Contracts\DailyTask\Services\DailyTaskService as DailyTaskServiceContract;
use SingPlus\Domains\DailyTask\Models\DailyTask;
use SingPlus\Contracts\DailyTask\Constants\DailyTask as DailyTaskConstant;
use SingPlus\Domains\DailyTask\Models\DailyTaskHistory;
use SingPlus\Domains\DailyTask\Models\Task;
use SingPlus\Domains\DailyTask\Repositories\DailyTaskRepository;
use SingPlus\Domains\DailyTask\Repositories\DailyTaskHistoryRepository;
use SingPlus\Domains\Users\Repositories\UserProfileRepository;
use SingPlus\Domains\DailyTask\Repositories\TaskRepository;

class DailyTaskService implements DailyTaskServiceContract
{
    /**
     * @var DailyTaskRepository
     */
    private $dailyTaskRepo;

    /**
     * @var UserProfileRepository
     */
    private $userProfileRepo;

    /**
     * @var DailyTaskHistoryRepository
     */
    private $dailyTaskHistoryRepo;

    /**
     * @var TaskRepository
     */
    private $taskRepo;

    public function __construct(
        DailyTaskRepository $dailyTaskRepo,
        UserProfileRepository $userProfileRepository,
        DailyTaskHistoryRepository $dailyTaskHistoryRepository,
        TaskRepository $taskRepository
    ) {
        $this->dailyTaskRepo = $dailyTaskRepo;
        $this->userProfileRepo = $userProfileRepository;
        $this->dailyTaskHistoryRepo = $dailyTaskHistoryRepository;
        $this->taskRepo = $taskRepository;
    }

    /**
     * @param string $userId
     * @param string $type
     * @param int $status
     * @param string|null $taskId
     * @return null|\stdClass
     */
    public function updateDailyTask(string $userId, string $type, int $status, string $taskId = null): ?\stdClass
    {
        $now = Carbon::now();
        if ($taskId){
            $dailyTask = $this->dailyTaskRepo->updateOneByTaskId($taskId, $status, $now);
        }else {
            $dailyTask = $this->dailyTaskRepo->updateOne($userId, $type, $status, $now);
        }

        if ($dailyTask){
            $historyId = $dailyTask->history_id;
            if ($historyId){
                $this->dailyTaskHistoryRepo->updateFinishedStatus($historyId, $status,
                    $now->format('Y-m-d H:i:s'));
            }
            $task = $this->taskRepo->findOneByCategoryAndType(Task::CATEGORY_DAILY_TASK, $dailyTask->type);

            $value = DailyTaskConstant::getDailyTaskValue($dailyTask->type, $task->value, $task->value_step,
                                                $task->maximum_value,$dailyTask->days);
            $status = $dailyTask->finished_status;

        }else {
            $value = 0;
            $status = DailyTask::NEED_FINISH;
            $historyId = null;
        }

        return (object)[
          'value' => $value,
          'status' => $status,
          'historyId' => $historyId
        ];
    }

    /**
     * @param string $userId
     * @return Collection
     */
    public function getDailyTaskLists(string $userId): Collection
    {
        // 获取当前所以normal的任务
        $tasks = $this->taskRepo->findAllByCategory(Task::CATEGORY_DAILY_TASK);

        $dailyTasks = $this->dailyTaskRepo->findAllByUserId($userId);

        return $tasks->map(function ($task, $__) use($dailyTasks, $userId){
            $type = $task->type;

            $dailyTask = $dailyTasks->where('type', $type)->first();
            if (!$dailyTask){
                $now = Carbon::now();
                $dailyTask = $this->createDailyTask($userId, $type, $now);
                $dailyTaskHistory = $this->createDailyTaskHistory($userId, $type,
                    $dailyTask->days, $dailyTask->id, $now);
                // update dailytask's history id
                DailyTask::where('_id', $dailyTask->id)
                    ->update(['history_id' => $dailyTaskHistory->id]);

            }
            $days = $dailyTask->days;
            $value = DailyTaskConstant::getDailyTaskValue($type,$task->value, $task->value_step, $task->maximum_value, $days);
            $recentDays = [];
            if ($days > 1){
                array_push($recentDays, [
                    'day' => $days-1,
                    'value' => DailyTaskConstant::getDailyTaskValue($type,$task->value, $task->value_step, $task->maximum_value, $days-1),
                ] );
            }

            array_push($recentDays, [
                'day' => $days,
                'value' => DailyTaskConstant::getDailyTaskValue($type,$task->value, $task->value_step, $task->maximum_value, $days),
            ]);

            array_push($recentDays,[
                'day' => $days+1,
                'value' => DailyTaskConstant::getDailyTaskValue($type,$task->value, $task->value_step, $task->maximum_value, $days+1),
            ]);

            return (object)[
                'taskId' => $dailyTask->id,
                'type' => $dailyTask->type,
                'status' => $dailyTask->finished_status,
                'finishedAt'  => $dailyTask->finished_at,
                'value'      => $value,
                'days'       => $dailyTask->days,
                'title'      => DailyTaskConstant::getDailyTaskTitle($type, $task->title, $days, $value),
                'desc'       => DailyTaskConstant::getDailyTaskDesc($type, $task->desc, $days, $value),
                'recentDays' => $recentDays,
            ];
        });
    }


    /**
     * @param string $userId
     * @param null|string $countryAbbr
     */
    public function resetDailyTaskLists(string $userId, ?string $countryAbbr)
    {
        $abbr = $countryAbbr ? $countryAbbr : "";
        $now = Carbon::now();
        if ($this->checkIsNewDay($userId, $abbr, $now)){
            $this->userProfileRepo->updateLastDailyTaskAt($userId, $now);

            $validTasks = $this->taskRepo->findAllByCategory(Task::CATEGORY_DAILY_TASK);

            $validTasks->each(function ($task, $__) use($now, $userId, $abbr){
                $type = $task->type;
                $dailyTask = $this->dailyTaskRepo->findOneByUserIdAndType($userId, $type);
                if (!$dailyTask){
                    $dailyTask = $this->createDailyTask($userId, $type, $now);
                }
                $daysInternal = $this->checkFinishedDaysInterval($userId, $dailyTask->finished_at, $abbr,  $now);

                if ($daysInternal == 1 && $dailyTask->finished_status != DailyTask::NEED_FINISH ){
                    $dailyTask->days = $dailyTask->days + 1;
                }else {
                    $dailyTask->days = 1;
                }
                $dailyTask->finished_status= DailyTask::NEED_FINISH;
                //创建history
                $dailyTaskHistory = $this->createDailyTaskHistory($userId, $type,
                    $dailyTask->days, $dailyTask->id, $now);
                $dailyTask->history_id = $dailyTaskHistory->id;
                $dailyTask->save();
            });

        }
    }

    /**
     *  create a dailytask for user
     *
     * @param $userId
     * @param $type
     * @param Carbon|null $now
     * @return DailyTask
     */
    private function createDailyTask($userId, $type, Carbon $now = null): DailyTask{
        $dailyTask = new DailyTask([
            'type' => $type,
            'status' => DailyTask::STATUS_NORMAL,
            'user_id' => $userId,
            'days'   => 1,
            'finished_at' => $now ? $now->format('Y-m-d H:i:s') : Carbon::now()->format('Y-m-d H:i:s'),
            'finished_status' => DailyTask::NEED_FINISH,
        ]);

        $dailyTask->save();
        return $dailyTask;
    }

    private function createDailyTaskHistory($userId, $type, $days, $taskId, Carbon $now = null) :DailyTaskHistory{
        return $this->dailyTaskHistoryRepo->createOne($userId, $taskId, $type,
            $days, DailyTask::NEED_FINISH,
            $now ? $now->format('Y-m-d H:i:s') : Carbon::now()->format('Y-m-d H:i:s'));
    }

    /**
     * check if now is in a new day
     *
     * @param string $userId
     * @param string $countryAbbr
     * @param Carbon $now
     * @return bool
     */
    private function checkIsNewDay(string $userId, string $countryAbbr, Carbon $now): bool {

        $userProfile = $this->userProfileRepo->findOneByUserId($userId);
        $lastTaskAt = $userProfile->last_dailytask_at;
        if (!$lastTaskAt){
            return true;
        }
        $daysInterval = DailyTaskConstant::compareDays($countryAbbr, 'Y-m-d H:i:s', $lastTaskAt, $now);

        return $daysInterval > 0;
    }

    /**
     * check the interval days
     *
     * @param string $userId
     * @param string $lastFinishedAt
     * @param string $countryAbbr
     * @param Carbon $now
     * @return int       days interval between now and lastfinished day
     */
    private function checkFinishedDaysInterval(string $userId, string $lastFinishedAt, string $countryAbbr, Carbon $now): int{
        $daysInterval = DailyTaskConstant::compareDays($countryAbbr, 'Y-m-d H:i:s', $lastFinishedAt, $now);
        return $daysInterval;
    }

    /**
     * @param string $userId
     * @param $type
     * @param null|string $taskId
     * @return null|\stdClass
     *                  - status    int     the finished status of task
     *                  - type       string the type of a task
     *                  - finishedAt    string the date when the task finished
     *                  - days      int   the days of task been finished continuously
     */
    public function getDailyTaskDetail(string $userId, $type, string $taskId = null): ?\stdClass
    {
        if ($taskId){
            $dailyTask = $this->dailyTaskRepo->findOneById($taskId);
        }else {
            $dailyTask = $this->dailyTaskRepo->findOneByUserIdAndType($userId, $type);
        }
        if ($dailyTask){
            return (object)[
              'status' => $dailyTask->finished_status,
              'type'   => $dailyTask->type,
              'finishedAt'  => $dailyTask->finished_at,
              'days'        => $dailyTask->days,
            ];
        }else {
            return null;
        }
    }

    /**
     * @param string $userId
     * @param $type     string  type of a task
     * @return null|\stdClass
     *                  - status    int    the task's finished status
     *                  - value     int    the award coins after the task been finished
     */
    public function finisheDailyTask(string $userId, $type): ?\stdClass
    {
        $dailyTask = $this->getDailyTaskDetail($userId, $type);
        if ($dailyTask){
            $task = $this->taskRepo->findOneByCategoryAndType(Task::CATEGORY_DAILY_TASK, $dailyTask->type);
            $needUpdate = $dailyTask->status == DailyTask::NEED_FINISH;
            $result = (object)[
                'status' => $dailyTask->status,
                'value' => DailyTaskConstant::getDailyTaskValue($dailyTask->type, $task->value,
                                    $task->value_step, $task->maximum_value,
                                    $dailyTask->days),
            ];
        }else {
            $needUpdate = false;
            $result = (object)[
                'status' => DailyTask::NEED_FINISH,
                'value' => 0,
            ];
        }

        if ($needUpdate){
            $result = $this->updateDailyTask($userId,
                $type, DailyTask::NEED_AWARD);

        }
        return $result;
    }

    /**
     * @param string $category
     * @param string $type
     * @return null|\stdClass
     */
    public function getTaskDetail(string $category, string $type): ?\stdClass
    {
        $task = $this->taskRepo->findOneByCategoryAndType($category, $type);
        if ($task){
            return (object)[
                'type' => $task->type,
                'title' => $task->title,
                'desc'  => $task->desc,
                'value' => $task->value,
                'valueStep' => $task->value_step,
                'maximumValue' => $task->maximum_value,
                'category'  =>$task->category,
            ];
        }else {
            return null;
        }
    }
}