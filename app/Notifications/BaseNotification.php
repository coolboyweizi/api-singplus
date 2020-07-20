<?php

namespace SingPlus\Notifications;

use Illuminate\Notifications\Notification;

class BaseNotification extends Notification
{
    /**
     * @var ?string
     */
    public $taskId;

    /**
     * @var string
     */
    protected $type;

    /**
     * @var array
     */
    protected $data;

    /**
     * @var string
     */
    protected $customBody;

    /**
     * @var string
     */
    protected $customIcon;

    /**
     * @var string
     */
    protected $redirectTo;

    /**
     * @var \NotificationChannels\FCM\FCMMessage;
     */
    protected $fcmMessage;

    /**
     * Create a new notification instance.
     *
     * @return void
     */
    public function __construct(array $data = [])
    {
      $this->data = $data;
    }

    public function setCustomBody(?string $body) : BaseNotification
    {
      $this->customBody = $body;
      return $this;
    }

    public function setCustomIcon(?string $icon) : BaseNotification
    {
      $this->customIcon = $icon;
      return $this;
    }

    public function setRedirectTo(?string $url) : BaseNotification
    {
      $this->redirectTo = $url;
      return $this;
    }

    public function setTaskId(?string $taskId) : BaseNotification
    {
      $this->taskId = $taskId;
      return $this;
    }

    public function getType() : ?string
    {
      return $this->type;
    }

    /**
     * Get the array representation of the notification.
     *
     * @param  mixed  $notifiable
     * @return array
     */
    public function toArray($notifiable)
    {
      $res = [];

      if ($this->fcmMessage) {
        $res['fcm'] = [
          'option'  => $this->fcmMessage->getOptions()
                        ? $this->fcmMessage->getOptions()->toArray()
                        : null,
          'notification'  => $this->fcmMessage->getNotification()
                              ? $this->fcmMessage->getNotification()->toArray()
                              : null,
          'data'          => $this->fcmMessage->getData()
                              ? $this->fcmMessage->getData()->toArray()
                              : null,
        ];
      }

      return $res;
    }
}
