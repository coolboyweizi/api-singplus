<?php
/**
 * Created by PhpStorm.
 * User: karl
 * Date: 2018/1/12
 * Time: 下午2:40
 */

namespace SingPlus\Events\Friends;

use Illuminate\Broadcasting\Channel;
use Illuminate\Queue\SerializesModels;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Broadcasting\PresenceChannel;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;

class UserTriggerFollowed
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    /**
     * User who trigger follow action
     *
     * @var string
     */
    public $userId;

    /**
     * User who was followed
     */
    public $followedUserId;

    /**
     * Create a new event instance.
     *
     * @return void
     */
    public function __construct(string $userId, string $followedUserId)
    {
        $this->userId = $userId;
        $this->followedUserId = $followedUserId;
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