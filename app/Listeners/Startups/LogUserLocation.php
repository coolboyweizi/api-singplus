<?php

namespace SingPlus\Listeners\Startups;

use SingPlus\Events\Startups\CommonInfoFetched;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;
use SingPlus\Services\StartupService;

class LogUserLocation implements ShouldQueue
{
    /**
     * @var StartupService
     */
    private $startupService;

    /**
     * Create the event listener.
     *
     * @return void
     */
    public function __construct(StartupService $startupService)
    {
      $this->startupService = $startupService;
    }

    /**
     * Handle the event.
     *
     * @param  CommonInfoFetched  $event
     * @return void
     */
    public function handle(CommonInfoFetched $event)
    {
      $ip = object_get($event->info, 'ip');
      $this->startupService->logUserIpLocation($event->userId, $ip);
    }
}
