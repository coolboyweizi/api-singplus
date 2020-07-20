<?php

namespace SingPlus\Domains\Notifications\Services;

use Illuminate\Notifications\Notification;
use SingPlus\Support\Notification\PushDeviceNotifiable;
use SingPlus\Support\Notification\PushTopicNotifiable;
use SingPlus\Contracts\Supports\Constants\Queue as QueueConstant;
use SingPlus\Contracts\Notifications\Services\NotificationService as NotificationServiceContract;
use SingPlus\Contracts\Notifications\Constants\Notification as NotificationConstant;

class NotificationService implements NotificationServiceContract
{
  private static $typeNotificationMaps = [
    NotificationConstant::TYPE_WORK_COMMENT   => \SingPlus\Notifications\WorkCommented::class,
    NotificationConstant::TYPE_WORK_TRANSMIT  => \SingPlus\Notifications\WorkTransmited::class,
    NotificationConstant::TYPE_WORK_FAVOURITE => \SingPlus\Notifications\WorkFavourited::class,
    NotificationConstant::TYPE_FRIEND_FOLLOW  => \SingPlus\Notifications\FriendFollow::class,
    NotificationConstant::TYPE_WORK_CHORUS_JOIN => \SingPlus\Notifications\WorkChorusJoined::class,
    NotificationConstant::TYPE_USER_UNREGISTER_ACTIVE => \SingPlus\Notifications\Users\UserUnRegisterActive::class,
    NotificationConstant::TYPE_USER_NEW_NEXTDAY       => \SingPlus\Notifications\Users\UserNewNextDay::class,
    NotificationConstant::TYPE_USER_NEW_7DAY          => \SingPlus\Notifications\Users\UserNew7Day::class,
    NotificationConstant::TYPE_USER_NEW_30DAY         => \SingPlus\Notifications\Users\UserNew30Day::class,
    NotificationConstant::TYPE_USER_NEW_CONVERSION    => \SingPlus\Notifications\Users\UserNewConversion::class,
    NotificationConstant::TYPE_USER_ACTIVE_1ST        => \SingPlus\Notifications\Users\UserActive1st::class,
    NotificationConstant::TYPE_USER_ACTIVE_SING       => \SingPlus\Notifications\Users\UserActiveSing::class,
    NotificationConstant::TYPE_USER_ACTIVE_LISTEN     => \SingPlus\Notifications\Users\UserActiveListen::class,
    NotificationConstant::TYPE_GIFT_SEND_FOR_WORK     => \SingPlus\Notifications\WorkSendGift::class,
    NotificationConstant::TYPE_PRIVATE_MSG            => \SingPlus\Notifications\PrivateMsg::class,
  ];

  private static $typeTopicMaps = [
    NotificationConstant::TOPIC_TYPE_ANNOUNCEMENT => \SingPlus\Notifications\Topics\AnnouncementCreated::class,
    NotificationConstant::TOPIC_TYPE_ACTIVITY => \SingPlus\Notifications\Topics\ActivityCreated::class,
    NotificationConstant::TOPIC_TYPE_COVER_OF_DAY => \SingPlus\Notifications\Topics\CoverOfDay::class,
    NotificationConstant::TOPIC_TYPE_NEW_SONG     => \SingPlus\Notifications\Topics\NewSong::class,
  ];

  /**
   * {@inheritdoc}
   */
  public function notifyToUser(string $receptor, string $type, array $data)
  {
    $notifiable = new PushDeviceNotifiable($receptor);
    $notification = $this->parseNotification($type, $data);
    if ( ! $notification) {
      return null;
    }

    return $notifiable->notify($notification);
  }

  /**
   * {@inheritdoc}
   */
  public function notifyToMultiUsers(
    array $receptors,
    string $type,
    array $data,
    array $customizeData = [],
    ?string $taskId = null
  ) {
    $notification = $this->parseNotification($type, $data);
    if ( ! $notification) {
      return null;
    }
    
    // 接收者数量分组，防止单次发送量太大
    $receptorGroups = array_chunk($receptors, 250);
    foreach ($receptorGroups as $subReceptors) {
        $notification->setCustomIcon(array_get($customizeData, 'icon'))
                     ->setCustomBody(array_get($customizeData, 'content'))
                     ->setRedirectTo(array_get($customizeData, 'redirectTo'))
                     ->setTaskId($taskId)
                     ->onQueue(QueueConstant::CHANNEL_API_PUSH);
        $notifiable = new PushDeviceNotifiable($subReceptors);

        $notifiable->notify($notification);
    }
  }

  /**
   * {@inheritdoc}
   */
  public function notifyToTopic(
    string $topic,
    string $type,
    array $data = [],
    array $customizeData = [],
    ?string $taskId = null
  ) {
    $notifiable = new PushTopicNotifiable($topic);
    $notification = $this->parseTopic($type, $data);
    if ( ! $notification) {
      return null;
    }

    $notification->setCustomIcon(array_get($customizeData, 'icon'))
                 ->setCustomBody(array_get($customizeData, 'content'))
                 ->setRedirectTo(array_get($customizeData, 'redirectTo'))
                 ->setTaskId($taskId)
                 ->onQueue(QueueConstant::CHANNEL_API_PUSH);

    return $notifiable->notify($notification);
  }

  private function parseNotification(string $type, array $data) : ?Notification
  {
    $notificationClass = array_get(self::$typeNotificationMaps, $type);

    return $notificationClass ? new $notificationClass($data) : null;
  }

  private function parseTopic(string $type, array $data) : ?Notification
  {
    $notificationClass = array_get(self::$typeTopicMaps, $type);

    return $notificationClass ? new $notificationClass($data) : null;
  }
}
