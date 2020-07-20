<?php

/**
 * Created by PhpStorm.
 * User: karl
 * Date: 2018/5/9
 * Time: 下午11:42
 */
namespace SingPlus\Jobs\Activities;
use Illuminate\Bus\Queueable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use SingPlus\Services\ActivityService;

class BoomsingCoinActivityJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * @var string
     */
    public $userId;

    public function __construct(string $userId)
    {
        $this->userId = $userId;
    }

    public function handle(ActivityService $activityService){
        $activityService->sendCoinForBoomsingUser($this->userId);
    }
}