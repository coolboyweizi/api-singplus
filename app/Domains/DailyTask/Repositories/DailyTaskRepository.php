<?php
namespace SingPlus\Domains\DailyTask\Repositories;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Collection;
use SingPlus\Domains\DailyTask\Models\DailyTask;

/**
 * Created by PhpStorm.
 * User: karl
 * Date: 2018/1/24
 * Time: 下午5:36
 */
class DailyTaskRepository
{

    /**
     * @param string $newsId  news id
     * @param array $fields   specify which fields should be return
     * @param bool $force     trashed record will be fetch if true
     * @return null|DailyTask
     */
    public function findOneById(string $taskId, array $fields = ['*'], bool $force = false) : ?DailyTask{
        if ($force) {
            return DailyTask::withTrashed()->select(...$fields)->find($taskId);
        } else {
            return DailyTask::select(...$fields)->find($taskId);
        }
    }

    /**
     * @param string $userId
     * @param string $type
     * @return null|DailyTask
     */
    public function findOneByUserIdAndType(string $userId, string $type) : ?DailyTask{
        return DailyTask::where('user_id', $userId)
            ->where('type', $type)->first();
    }

    /**
     * @param string $userId
     * @param string $type
     * @param int $status
     * @return null|DailyTask
     */
    public function updateOne(string $userId, string $type, int $status, Carbon $now = null) :?DailyTask{
        $dailyTask = $this->findOneByUserIdAndType($userId, $type);
        if ($dailyTask){
            $dailyTask->finished_status = $status;
            $dailyTask->finished_at = $now ? $now->format('Y-m-d H:i:s') : Carbon::now()->format('Y-m-d H:i:s');
            $dailyTask->save();
        }
        return $dailyTask;

    }

    /**
     * @param string $userId
     * @return Collection
     */
    public function findAllByUserId(string $userId) : Collection{
        $query =  DailyTask::where('status', DailyTask::STATUS_NORMAL)
            ->where('user_id', $userId);
        return $query->orderBy('type','asc')->get();
    }

    /**
     * @param string $taskId
     * @param int $status
     * @return null|DailyTask
     */
    public function updateOneByTaskId(string $taskId, int $status, Carbon $now = null):?DailyTask{
        $dailyTask = $this->findOneById($taskId);
        if ($dailyTask){
            $dailyTask->finished_status = $status;
            $dailyTask->finished_at = $now ? $now->format('Y-m-d H:i:s') : Carbon::now()->format('Y-m-d H:i:s');
            $dailyTask->save();
        }
        return $dailyTask;
    }
}