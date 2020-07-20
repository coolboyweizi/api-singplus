<?php
/**
 * Created by PhpStorm.
 * User: karl
 * Date: 2018/3/23
 * Time: 下午4:04
 */

namespace SingPlus\Events\Works;

use Illuminate\Broadcasting\Channel;
use Illuminate\Queue\SerializesModels;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Broadcasting\PresenceChannel;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;

class WorkUpdateTags
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    /**
     * @var string
     */
    public $workId;

    /**
     * Create a new event instance.
     *
     * @return void
     */
    public function __construct(string $workId)
    {
        $this->workId = $workId;
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