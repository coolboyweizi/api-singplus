<?php
/**
 * Created by PhpStorm.
 * User: karl
 * Date: 2018/2/3
 * Time: 上午10:04
 */

namespace SingPlus\Domains\DailyTask\Repositories;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Collection;
use SingPlus\Domains\DailyTask\Models\DailyTask;
use SingPlus\Domains\DailyTask\Models\DailyTaskHistory;
use SingPlus\Support\Database\SeqCounter;

class DailyTaskHistoryRepository
{

    /**
     * @param string $userId
     * @param string $taskId
     * @param string $type
     * @param int $days
     * @param int $finished_status
     * @param string $finished_at
     * @return DailyTaskHistory
     */
    public function createOne(string $userId,
                              string $taskId,
                              string $type, int $days,
                              int $finished_status, string $finished_at) : DailyTaskHistory{

        $taskHistory = new DailyTaskHistory([
            'user_id' => $userId,
            'task_id' => $taskId,
            'type'  => $type,
            'days' => $days,
            'finished_status' => $finished_status,
            'finished_at' => $finished_at,
            'display_order' => SeqCounter::getNext('dailyTaskHistory'),

        ]);
        $taskHistory->save();
        return $taskHistory;
    }

    public function updateFinishedStatus(string $id, int $finished_status, string $finished_at){
        $taskHistory = DailyTaskHistory::find($id);
        DailyTaskHistory::where('_id', $id)
            ->update([
                'finished_status'   => $finished_status,
                'finished_at'       => $finished_at
            ]);
    }

}