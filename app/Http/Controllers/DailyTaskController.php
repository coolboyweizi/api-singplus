<?php
/**
 * Created by PhpStorm.
 * User: karl
 * Date: 2018/1/25
 * Time: 下午7:40
 */

namespace SingPlus\Http\Controllers;


use Carbon\Carbon;
use Illuminate\Http\Request;
use SingPlus\Services\DailyTaskService;

class DailyTaskController extends Controller
{

    /**
     *  get user's dailytask list
     * @param Request $request
     * @param DailyTaskService $dailyTaskService
     * @return \Illuminate\Http\Response
     */
    public function getDailyTaskList
    (Request $request,
     DailyTaskService $dailyTaskService)
    {

        $countryAbbr = $request->headers->get('X-RealCountryAbbr');
        $dailyTaskService->checkDailyTask($request->user()->id, $countryAbbr);
        $tasksInfo = $dailyTaskService->getDailyTaskLists($request->user()->id);
        return $this->render('dailytask.tasks', [
            'tasks' => $tasksInfo->tasks,
            'totalCoins' => $tasksInfo->totalCoins,
        ]);
    }

    /**
     *  get the award of a dailytask been finished
     *
     * @param Request $request
     * @param DailyTaskService $dailyTaskService
     * @return \Illuminate\Http\Response
     */
    public function getDailyTaskAward
    (Request $request,
     DailyTaskService $dailyTaskService){
        $this->validate($request, [
            'taskId'  => 'uuid|required',
            'type'  => 'string|required',
        ]);

        $countryAbbr = $request->headers->get('X-RealCountryAbbr');
        $dailyTaskService->checkDailyTask($request->user()->id, $countryAbbr);
        $result = $dailyTaskService->getDailyTaskAward(
            $request->user()->id,
            $request->request->get('type'),
            $request->request->get('taskId'));

        return $this->render('dailytask.status',[
            'totalCoins' => $result->totalCoins,
            'status' => $result->status,
            'value'  => $result->value,
        ]);
    }

    /**
     *  finish the invite dailytask
     *
     * @param Request $request
     * @param DailyTaskService $dailyTaskService
     * @return \Illuminate\Http\Response
     */
    public function finishedInviteDailyTask
    (Request $request,
     DailyTaskService $dailyTaskService){
        $countryAbbr = $request->headers->get('X-RealCountryAbbr');
        $dailyTaskService->checkDailyTask($request->user()->id, $countryAbbr);
        $result = $dailyTaskService->finishedInviteTask($request->user()->id);
        return $this->render('dailytask.status',[
            'totalCoins' => $result->totalCoins,
            'status' => $result->status,
            'value'  => $result->value,
        ]);
    }

}