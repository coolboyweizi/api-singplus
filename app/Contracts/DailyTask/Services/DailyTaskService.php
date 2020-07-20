<?php
/**
 * Created by PhpStorm.
 * User: karl
 * Date: 2018/1/24
 * Time: 下午3:10
 */
namespace SingPlus\Contracts\DailyTask\Services;
use Carbon\Carbon;
use Illuminate\Support\Collection;

interface DailyTaskService
{

    /**
     * @param string $userId
     * @param string $type
     * @param int $status
     * @return null|\stdClass
     *                  - values    int   the award coins after finished the task
     *                  - status    int   the status of task
     *                  - historyId  null / string    the history id of daily task
     */
    public function updateDailyTask(string $userId, string $type, int $status, string $taskId = null) : ?\stdClass;

    /**
     * @param string $userId
     * @return Collection
     *              - taskId    string   the id of task
     *              - type      string  the type of task
     *              - status    int     the status of the task finish status
     *              - finishedAt    string the time the task been finished
     *              - value     int     the award coins after the task been finished
     *              - days      int     the days one have continuous finishied a task
     *              - title     string  the title of task
     *              - desc      string  the desc of task
     *              - recentDays     array
     *                      - day    int
     *                      - value  int    award coins after finished
     */
    public function getDailyTaskLists(string $userId) : Collection;

    /**
     * @param string $userId
     * @param null|string $countryAbbr
     * @return mixed
     */
    public function resetDailyTaskLists(string $userId, ?string $countryAbbr);

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
    public function getDailyTaskDetail(string $userId, $type, string $taskId = null) : ?\stdClass;

    /**
     * @param string $userId
     * @param $type     string  type of a task
     * @return null|\stdClass
     *                  - status    int    the task's finished status
     *                  - value     int    the award coins after the task been finished
     */
    public function finisheDailyTask(string $userId, $type) : ?\stdClass;

    /*
     * @param string $category
     * @param string $type
     * @return null|\stdClass
     *                  - type  string
     *                  - title    string
     *                  - desc      string
     *                  - value     int
     *                  - valueStep     int
     *                  - maximumValue  int
     *                  - category      string
     */
    public function getTaskDetail(string $category, string $type) : ?\stdClass;

}