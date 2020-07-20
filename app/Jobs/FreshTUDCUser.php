<?php

namespace SingPlus\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use SingPlus\Services\Auth\TUDCService;

class FreshTUDCUser implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * @var string
     */
    public $userId;

    /**
     * @var string
     */
    public $tudcTicket;

    /**
     * @var ?string
     */
    public $appChannel;

    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct($userId, $tudcTicket, $appChannel)
    {
      $this->userId = $userId;
      $this->tudcTicket = $tudcTicket;
      $this->appChannel = $appChannel;
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle(TUDCService $tudcService)
    {
      return $tudcService->updateUserTUDCInfo(
        $this->appChannel, $this->userId, $this->tudcTicket
      );
    }
}
