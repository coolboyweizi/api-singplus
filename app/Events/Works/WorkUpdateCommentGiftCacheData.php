<?php
/**
 * Created by PhpStorm.
 * User: karl
 * Date: 2018/4/2
 * Time: 上午11:43
 */

namespace SingPlus\Events\Works;

use Illuminate\Broadcasting\Channel;
use Illuminate\Queue\SerializesModels;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Broadcasting\PresenceChannel;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;

class WorkUpdateCommentGiftCacheData
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    /**
     * @var string
     */
    public $workId;

    /**
     * @var null|string
     */
    public $senderId;

    /**
     * @var null|string
     */
    public $commentId;

    /**
     * Create a new event instance.
     *
     * @return void
     */
    public function __construct(string $workId, ?string $senderId, ?string $commentId)
    {
        $this->workId = $workId;
        $this->senderId = $senderId;
        $this->commentId = $commentId;
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