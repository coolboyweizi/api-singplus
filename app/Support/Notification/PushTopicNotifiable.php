<?php

namespace SingPlus\Support\Notification;

use LaravelFCM\Message\Topics;
use SingPlus\Support\Notification\Notifiable;

class PushTopicNotifiable extends Notifiable
{
  /**
   * @var Topics
   */
  private $topic;

  /**
   * @var mixed
   */
  private $origTopic;

  public function __construct(string $topic)
  {
    $this->origTopic = $topic;
    $topicObj = (new Topics())->topic($topic);
    $this->topic = $topicObj;
  }

  /**
   * Route notification for the fcm channel.
   *
   * @return string
   */
  public function routeNotificationForFcm()
  {
    return $this->topic;
  }

  public function getTarget()
  {
    return $this->origTopic;
  }
}
