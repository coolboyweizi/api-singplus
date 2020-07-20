<?php

namespace SingPlus\Notifications\Users;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use NotificationChannels\FCM\FCMMessage;
use SingPlus\Notifications\BaseNotification;
use SingPlus\Support\Notification\Notifiable;
use SingPlus\Contracts\Notifications\Constants\Notification as NotificationConstant;

class UserNew30Day extends BaseNotification implements ShouldQueue
{
    use Queueable;

    public function __construct(array $data = [])
    {
      parent::__construct($data);
      $this->type = NotificationConstant::TYPE_USER_NEW_30DAY;
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
      $this->fcmMessage = new FCMMessage();
      $data = [
        'title'         => NotificationConstant::getPushTitle(),
        'body'          => $this->customBody ?: $this->renderBody($this->data),
        'click_action'  => 'MainActivity',
        'type'        => NotificationConstant::TYPE_USER_NEW_30DAY,
        'redirectTo'  => $this->redirectTo,
      ];
      if ($this->customIcon) {
        $data['icon'] = $this->customIcon;
      }

      return $this->fcmMessage->data($data);
    }

    private function renderBody(array $data) : string
    {
      $res = view('notifications.userNew30Day', $data);

      return trim($res->render());
    }
}
