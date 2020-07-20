<?php

namespace SingPlus\Events\Users;

use Illuminate\Broadcasting\Channel;
use Illuminate\Queue\SerializesModels;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Broadcasting\PresenceChannel;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;

class PushAliasBound
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    /**
     * @var string
     */
    public $appChannel;

    /**
     * @var string
     */
    public $userId;

    /**
     * @var string
     */
    public $alias;

    /**
     * Create a new event instance.
     *
     * @return void
     */
    public function __construct(string $appChannel, string $userId, string $alias)
    {
      $this->appChannel = $appChannel;
      $this->userId = $userId;
      $this->alias = $alias;
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
