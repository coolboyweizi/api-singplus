<?php

namespace SingPlus\Support\Notification;

use NotificationChannels\FCM\FCMChannel as BaseFCMChannel;
use NotificationChannels\FCM\FCMMessageTopic;
use NotificationChannels\FCM\FCMMessageGroup;
use NotificationChannels\FCM\MessageWasSended;
use NotificationChannels\FCM\Exceptions\CouldNotSendNotification;
use Illuminate\Notifications\Notification;
use Illuminate\Support\Facades\Event;

class FCMChannel extends BaseFCMChannel
{
  /**
   * @override
   *
   * 重写parent send方法，全部逻辑保持不变，唯一改变的地方是最后
   * 返回send response，以便laravel框架能够获得response，参见:
   * vendor/laravel/framework/src/Illuminate/Notifications/NotificationSender.php
   * 该文件中fire NotificationSent event时能够得到response
   *
   * Send the given notification.
   *
   * @param mixed $notifiable
   * @param \Illuminate\Notifications\Notification $notification
   *
   * @throws \NotificationChannels\FCM\Exceptions\CouldNotSendNotification
   */
  public function send($notifiable, Notification $notification)
  {
    //=============================
    //      Original logic begin
    //=============================
    $message = $notification->toFCM($notifiable);
    if ($message->recipientNotGiven()) {
        if (! $to = $notifiable->routeNotificationFor('FCM')) {
            throw CouldNotSendNotification::missingRecipient();
        }
        $message->to($to);
    }
    $method = 'sendTo';
    if ($message instanceof FCMMessageTopic) {
        $method .= 'Topic';
    } elseif ($message instanceof FCMMessageGroup) {
        $method .= 'Group';
    }

    $response = $this->sender->{$method}(...$message->getArgs());

    Event::fire(new MessageWasSended($response, $notifiable));

    //=============================
    //      Original logic end
    //=============================

    return $response;
  }
}
