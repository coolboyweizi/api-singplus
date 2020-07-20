<?php
/**
 * Created by PhpStorm.
 * User: karl
 * Date: 2018/3/1
 * Time: 下午4:38
 */

namespace SingPlus\Jobs;
use Illuminate\Bus\Queueable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use SingPlus\Contracts\Hierarchy\Services\WealthHierarchyService as WealthHierarchyServiceContract;

class UpdateWealthHierarchy implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $userId;

    public function __construct($userId)
    {
        $this->userId = $userId;
    }

    public function handle(WealthHierarchyServiceContract $hierarchyService){
        $hierarchyService->updateWealthHierarchy($this->userId);
    }
}