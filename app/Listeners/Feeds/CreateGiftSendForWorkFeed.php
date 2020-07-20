<?php
/**
 * Created by PhpStorm.
 * User: karl
 * Date: 2018/1/31
 * Time: 下午1:56
 */

namespace SingPlus\Listeners\Feeds;

use Illuminate\Contracts\Queue\ShouldQueue;
use SingPlus\Events\Gifts\UserSendGiftForWork;
use SingPlus\Services\FeedService;

class CreateGiftSendForWorkFeed implements ShouldQueue
{
    /**
     * @var FeedService
     */
    private $feedService;

    /**
     * Create the event listener.
     *
     * @return void
     */
    public function __construct(FeedService $feedService)
    {
        $this->feedService = $feedService;
    }

    /**
     * Handle the event.
     *
     * @param  UserSendGiftForWork  $event
     * @return void
     */
    public function handle(UserSendGiftForWork $event)
    {
        return $this->feedService->createGiftSendForWorkFeed($event->giftHistoryId);
    }
}