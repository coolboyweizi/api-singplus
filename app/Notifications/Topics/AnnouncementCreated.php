<?php

namespace SingPlus\Notifications\Topics;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use NotificationChannels\FCM\FCMMessage;
use NotificationChannels\FCM\FCMMessageTopic;
use SingPlus\Notifications\BaseNotification;
use SingPlus\Support\Notification\Notifiable;
use SingPlus\Contracts\Notifications\Constants\Notification as NotificationConstant;

class AnnouncementCreated extends BaseNotification implements ShouldQueue
{
    use Queueable;

    public function __construct(array $data = [])
    {
      parent::__construct($data);
      $this->type = NotificationConstant::TOPIC_TYPE_ANNOUNCEMENT;
    }

    /**
     * Get the notification's delivery channels.
     *
     * @param  mixed  $notifiable
     * @return array
     */
    public function via($notifiable)
    {
        return ['fcm'];
    }

    /**
     * Get the mail representation of the notification.
     *
     * @param  Notifiable  $notifiable
     */
    public function toFCM(Notifiable $notifiable)
    {
      $this->fcmMessage = new FCMMessageTopic();
      // this notification should not display in user's device, cause notification missed
      return $this->fcmMessage->data([
          'type'        => NotificationConstant::TOPIC_TYPE_ANNOUNCEMENT,
          'redirectTo'  => $this->redirectTo ?: 'singplus://announcement',
        ]);
    }
}
