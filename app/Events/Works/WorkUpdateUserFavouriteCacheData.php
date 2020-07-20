<?php
/**
 * Created by PhpStorm.
 * User: karl
 * Date: 2018/4/4
 * Time: 下午4:10
 */

namespace SingPlus\Events\Works;

use Illuminate\Broadcasting\Channel;
use Illuminate\Queue\SerializesModels;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Broadcasting\PresenceChannel;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;

class WorkUpdateUserFavouriteCacheData
{

    use Dispatchable, InteractsWithSockets, SerializesModels;

    /**
     * @var string
     */
    public $workId;

    /**
     * @var string
     */
    public $userId;

    /**
     * @var bool
     */
    public $isFavourite;

    /**
     * Create a new event instance.
     *
     * @return void
     */
    public function __construct(string $workId, string $userId, bool $isFavoutite)
    {
        $this->workId = $workId;
        $this->userId = $userId;
        $this->isFavourite = $isFavoutite;
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