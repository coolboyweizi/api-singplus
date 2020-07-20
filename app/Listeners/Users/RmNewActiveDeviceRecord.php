<?php

namespace SingPlus\Listeners\Users;

use SingPlus\Events\Users\PushAliasBound;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;
use SingPlus\Contracts\ClientSupports\Services\DeviceService as DeviceServiceContract;

class RmNewActiveDeviceRecord implements ShouldQueue
{
    /**
     * @var DeviceServiceContract
     */
    private $deviceService;

    /**
     * Create the event listener.
     *
     * @return void
     */
    public function __construct(DeviceServiceContract $deviceService)
    {
      $this->deviceService = $deviceService;
    }

    /**
     * Handle the event.
     *
     * @param  PushAliasBound  $event
     * @return ?bool
     */
    public function handle(PushAliasBound $event) : ?bool
    {
      return $this->deviceService->removeAlias($event->appChannel, $event->alias);
    }
}
