<?php

/**
 * Created by PhpStorm.
 * User: karl
 * Date: 2018/1/31
 * Time: 下午12:18
 */
namespace SingPlus\Events\Gifts;

use Illuminate\Broadcasting\Channel;
use Illuminate\Queue\SerializesModels;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Broadcasting\PresenceChannel;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use SingPlus\Exceptions\AppException;

class UserSendGiftForWork
{
    use Dispatchable, InteractsWithSockets, SerializesModels;


    /**
     * @var string
     */
    public $giftHistoryId;


    /**
     * Create a new event instance.
     *
     * @return void
     */
    public function __construct(string $giftHistoryId)
    {
        $this->giftHistoryId = $giftHistoryId;
    }

    /**
     * Get the channels the event should broadcast on.
     *
     * @return Channel|array
     */
    public function broadcastOn()
    {
        return new PrivateChannel('channel-name');
    }

}