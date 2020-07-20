<?php

namespace SingPlus\Domains\Notifications\Models;

use SingPlus\Support\Database\Eloquent\MongodbModel;
use SingPlus\Support\Notification\UserPushInboxFinder;

class PushMessage extends MongodbModel
{
  const STATUS_NORMAL = 1;

  protected $collection = 'user_push_inbox';

  /**
   * The attributes that are mass assignable.
   *
   * @var array
   */
  protected $fillable = [
    'user_id',
    'country_abbr',
    'type',     // please to see: SingPlus\Contracts\Notifications\Constants\PushMessage::TYPE_XXX
    'payload',  // dict
    'status',
    'display_order',
  ];

  //=======================
  //      Logic
  //=======================
  public function selectTable(string $userId)
  {
    $this->collection = app()->make(UserPushInboxFinder::class)
                             ->getCollection($userId);

    return $this;
  }
}
