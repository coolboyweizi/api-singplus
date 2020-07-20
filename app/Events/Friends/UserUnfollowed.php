<?php

namespace SingPlus\Events\Friends;

use Illuminate\Broadcasting\Channel;
use Illuminate\Queue\SerializesModels;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Broadcasting\PresenceChannel;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;

class UserUnfollowed
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    /**
     * User who trigger follow action
     *
     * @var string
     */
    public $userId;

    /**
     * User who was unfollowed
     */
    public $unfollowedUserId;

    /**
     * Create a new event instance.
     *
     * @return void
     */
    public function __construct(string $userId, string $unfollowedUserId)
    {
      $this->userId = $userId;
      $this->unfollowedUserId = $unfollowedUserId;
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
