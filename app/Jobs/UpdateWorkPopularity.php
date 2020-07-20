<?php
/**
 * Created by PhpStorm.
 * User: karl
 * Date: 2018/2/27
 * Time: 下午3:45
 */

namespace SingPlus\Jobs;
use Illuminate\Bus\Queueable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use SingPlus\Contracts\Hierarchy\Services\PopularityHierarchyService as PopularityHierarchyServiceContract;

class UpdateWorkPopularity implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $workId;

    public function __construct($workId)
    {
        $this->workId = $workId;
    }

    public function handle(PopularityHierarchyServiceContract $hierarchyService){
        $hierarchyService->updatePopularity($this->workId);
    }
}