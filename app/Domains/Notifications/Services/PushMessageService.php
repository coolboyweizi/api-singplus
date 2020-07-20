<?php

namespace SingPlus\Domains\Notifications\Services;

use Illuminate\Support\Collection;
use SingPlus\Contracts\Notifications\Constants\PushMessage as PushMessageConstant;
use SingPlus\Contracts\Notifications\Services\PushMessageService as PushMessageServiceContract;
use SingPlus\Domains\Notifications\Repositories\PushMessageRepository;

class PushMessageService implements PushMessageServiceContract
{
  /**
   * @var PushMessageRepository
   */
  private $pushMessageRepo;

  public function __construct(
    PushMessageRepository $pushMessageRepo
  ) {
    $this->pushMessageRepo = $pushMessageRepo;
  }

  /**
   * {@inheritdoc}
   */
  public function getUserMessages(
    string $userId,
    array $wheres = [],
    ?string $id,
    int $size
  ) : Collection {
    $displayOrder = null;
    if ($id) {
      $message = $this->pushMessageRepo
                      ->findOneById($userId, $id, ['display_order']);
      $displayOrder = $message ? $message->display_order : null;
    }

    return $this->pushMessageRepo
                ->findAllByUserIdForPagination($userId, $displayOrder, $size, $wheres)
                ->map(function ($message, $_) {
                  $res = (object) [
                    'id'        => $message->id, 
                    'type'      => $message->type,
                    'createdAt' => $message->created_at,
                  ];
                  switch ($message->type) {
                    case PushMessageConstant::TYPE_MUSIC_SHEET :
                      $res->payload = (object) [
                        'musicSheetId'  => array_get($message->payload, 'music_sheet_id'),
                        'title'         => array_get($message->payload, 'title'),
                        'cover'         => array_get($message->payload, 'cover'),
                        'text'          => array_get($message->payload, 'text'),
                      ];
                      break;
                    case PushMessageConstant::TYPE_WORK_SHEET :
                      $res->payload = (object) [
                        'workSheetId'   => array_get($message->payload, 'work_sheet_id'),
                        'title'         => array_get($message->payload, 'title'),
                        'cover'         => array_get($message->payload, 'cover'),
                        'text'          => array_get($message->payload, 'text'),
                      ];
                      break;
                    case PushMessageConstant::TYPE_NEW_MUSIC :
                      $res->payload = (object) [
                        'musicId' => array_get($message->payload, 'music_id'),
                        'text'    => array_get($message->payload, 'text'),
                      ];
                      break;
                    case PushMessageConstant::TYPE_NEW_WORK :
                      $res->payload = (object) [
                        'workId'  => array_get($message->payload, 'work_id'),
                        'text'    => array_get($message->payload, 'text'),
                      ];
                      break;
                  }

                  return $res;
                });
  }
}
