<?php

namespace SingPlus\Events;

use Illuminate\Broadcasting\Channel;
use Illuminate\Queue\SerializesModels;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Broadcasting\PresenceChannel;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use SingPlus\Exceptions\AppException;

class UserCommentWork
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    const ACTION_NEW = 'new';
    const ACTION_DELETE = 'del';

    /**
     * @var string
     */
    public $commentId;

    /**
     * @var string
     */
    public $action;

    /**
     * Create a new event instance.
     *
     * @return void
     */
    public function __construct(string $commentId, string $action)
    {
      $this->checkAction($action);

      $this->commentId = $commentId;
      $this->action = $action;
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

    private function checkAction($action)
    {
      $validAction = [
        self::ACTION_NEW, self::ACTION_DELETE,
      ];

      if ( ! in_array($action, $validAction)) {
        throw new AppException('the action of comment feed is invalid');
      }
    }
}
