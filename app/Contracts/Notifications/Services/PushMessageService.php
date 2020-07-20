<?php

namespace SingPlus\Contracts\Notifications\Services;

use Illuminate\Support\Collection;

interface PushMessageService
{
  /**
   * Get user push messages
   *
   * @param string $userId
   * @param array $wheres
   * @param ?string $id       for pagination
   * @param int $size         for pagination
   *
   * @return Collection       elements as below
   *                          - id string     unique id                 
   *                          - type string   please see SingPlus\Contracts\Notifications\Constants\PushMessage
   *                          - payload object
   *                            // for type is PushMessage::TYPE_MUSIC_SHEET
   *                            - musicSheetId string
   *                            - title string
   *                            - cover string    music sheet cover url
   *                            - text string
   *                            // for type is PushMessage::TYPE_WORK_SHEET
   *                            - workSheetId string
   *                            - title string
   *                            - cover string    music sheet cover url
   *                            - text string
   *                            // for type is PushMessage::TYPE_NEW_MUSIC
   *                            - musicId string
   *                            - text string
   *                            // for type is PushMessage::TYPE_NEW_WORK
   *                            - workId string
   *                            - text string
   *                          - createdAt \Carbon\Carbon
   */
  public function getUserMessages(
    string $userId,
    array $wheres = [],
    ?string $id,
    int $size
  ) : Collection;
}
