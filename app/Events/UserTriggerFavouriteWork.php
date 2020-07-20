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

class UserTriggerFavouriteWork
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    const ACTION_ADD = 'add';
    const ACTION_CANCEL = 'cancel';

    /**
     * @var string
     */
    public $favouriteId;

    /**
     * @var string
     */
    public $action;

    /**
     * Create a new event instance.
     *
     * @return void
     */
    public function __construct(string $favouriteId, string $action)
    {
      $this->checkAction($action);
      $this->favouriteId = $favouriteId;
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

    private function checkAction(string $action)
    {
      $validAction = [
        self::ACTION_ADD, self::ACTION_CANCEL
      ];

      if ( ! in_array($action, $validAction)) {
        throw new AppException('the action of favourite feed is invalid');
      }
    }
}
