<?php

namespace SingPlus\Events\Feeds;

use Illuminate\Broadcasting\Channel;
use Illuminate\Queue\SerializesModels;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Broadcasting\PresenceChannel;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;

class FeedReaded
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    /**
     * @var string
     */
    public $userId;

    /**
     * @var array
     */
    public $feedTypes;

    /**
     * Create a new event instance.
     *
     * @param string $userId
     * @param array|string $feedTypes
     *
     * @return void
     */
    public function __construct(string $userId, $feedTypes)
    {
      $this->userId = $userId;
      $this->feedTypes = (array) $feedTypes;
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
