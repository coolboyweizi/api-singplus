<?php

namespace SingPlus\Listeners\Users;

use SingPlus\Events\UserImageUploaded;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;
use SingPlus\Services\ImageService;

class RequestTailorUserImage implements ShouldQueue
{
  /**
   * @var ImageService
   */
  private $imageService;

    /**
     * Create the event listener.
     *
     * @return void
     */
    public function __construct(ImageService $imageService)
    {
      $this->imageService = $imageService;
    }

    /**
     * Handle the event.
     *
     * @param  UserImageUploaded  $event
     * @return void
     */
    public function handle(UserImageUploaded $event)
    {
      $this->imageService->requestTailorUserImaage($event->imageOrigUrl);
    }
}
