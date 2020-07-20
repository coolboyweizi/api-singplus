<?php

namespace SingPlus\Jobs;

use Carbon\Carbon;
use Illuminate\Bus\Queueable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use SingPlus\Contracts\Supports\Constants\Queue as QueueConstant;
use SingPlus\Contracts\Users\Services\UserProfileService as UserProfileServiceContract;

class SaveAuthUserLastVisitInfo implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * @var string
     */
    public $userId;

    /**
     * @var \stdClass
     */
    public $info;

    /**
     * Create a new job instance.
     *
     * @param string $userId
     * @param ?\stdClass $info    elements as below:
     *                              - version ?string     client version
     *
     * @return void
     */
    public function __construct(string $userId, \stdClass $info)
    {
      $this->queue = QueueConstant::CHANNEL_API_LAST_VISIT;

      $this->userId = $userId;
      $this->info = $info;
      $this->info->lastVisitedAt = Carbon::now();
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle(UserProfileServiceContract $userProfileService)
    {
      $userProfileService->updateUserLastVisitInfo($this->userId, $this->info);
    }
}
