<?php

namespace SingPlus\Contracts\Notifications\Services;

interface NotificationService
{
  /**
   * Send notification to user
   *
   * @param string $receptor
   * @param string $type
   * @param array $data
   */
  public function notifyToUser(string $receptor, string $type, array $data);

  /**
   * Send notification to multi users
   *
   * @param array $receptors        elements are receptor alias
   * @param string $type
   * @param array $data
   * @param array $customizeData    field as below
   *                                - icon ?string
   *                                - content ?string     message content
   *                                - redirectTo ?string  redirect url
   */
  public function notifyToMultiUsers(
    array $receptors,
    string $type,
    array $data,
    array $customizeData = []
  );

  /**
   * Send notification to topic
   *
   * @param string $topic
   * @param string $type
   * @param array $data
   * @param array $customizeData    field as below
   *                                - icon ?string
   *                                - content ?string     message content
   *                                - redirectTo ?string  redirect url
   */
  public function notifyToTopic(
    string $topic,
    string $type,
    array $data = [],
    array $customizeData
  );
}
