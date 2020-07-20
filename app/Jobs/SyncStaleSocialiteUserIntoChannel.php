<?php

namespace SingPlus\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use SingPlus\Services\SocialiteService;

class SyncStaleSocialiteUserIntoChannel implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * @var string
     */
    public $userId;

    /**
     * @var string
     */
    public $socialiteUserId;

    /**
     * @var string
     */
    public $userAccessToken;

    /**
     * @var string
     */
    public $unionToken;

    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct(
      string $userId,
      string $socialiteUserId,
      string $userAccessToken,
      ?string $unionToken
    ) {
      $this->userId = $userId;
      $this->socialiteUserId = $socialiteUserId;
      $this->userAccessToken = $userAccessToken;
      $this->unionToken = $unionToken;
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle(SocialiteService $socialiteService)
    {
      return $socialiteService->syncStaleSocialiteUserIntoChannel(
        $this->userId, $this->socialiteUserId, $this->userAccessToken, $this->unionToken
      );
    }
}
