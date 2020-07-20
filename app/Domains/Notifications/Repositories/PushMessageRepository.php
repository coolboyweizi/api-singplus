<?php

namespace SingPlus\Domains\Notifications\Repositories;

use Illuminate\Support\Collection;
use SingPlus\Support\Database\Eloquent\Pagination;
use SingPlus\Domains\Notifications\Models\PushMessage;

class PushMessageRepository
{
  // 本userId表示一个虚拟用户，场景为，有些消息是要发给所有用户的
  // ，如果为每一个用户创建一个消息体保存，太浪费空间，也不现实
  // 我们虚拟一个这样的用户，代表全部用户，即该消息属于所有的用户
  // 这样在依照userId筛选时，只需要把虚拟用户的userId包含进去就
  // 可以了，节省了空间。（每个collection都会插入一条该记录），
  // 这样，把为所有用户创建同一条消息改为了为每个collection创建
  // 一条虚拟用户的消息
  private $allUserId = '64ca9d1697fe4ca69494b8ba9633b62c';

  /**
   * @param string $id      work id
   * @param array $fields   specify which fields should be return
   */
  public function findOneById(string $userId, string $id, array $fields = ['*']) : ?PushMessage
  {
    return (new PushMessage)->selectTable($userId)
                            ->select(...$fields)
                            ->find($id);
  }

  /**
   * @param string $userId
   * @param ?int $displayOrder    used for pagination
   * @param int $size             used for pagination
   *
   * @return Collection           elements are PushMessage
   */
  public function findAllByUserIdForPagination(
    string $userId,
    ?int $displayOrder,
    int $size,
    array $wheres = []
  ) : Collection {
    $allUserId = $this->allUserId;
    $query = (new PushMessage)->selectTable($userId)
                              ->where(function ($query) use ($userId, $allUserId, $wheres) {
                                $query->where(function ($query) use ($userId) {
                                        $query->where('user_id', $userId)
                                              ->where('status', PushMessage::STATUS_NORMAL);
                                      })
                                      ->orWhere(function ($query) use ($allUserId, $wheres) {
                                        $query->where('user_id', $allUserId)
                                              ->where('created_at', '>=', $wheres['userRegisterAt']->format('Y-m-d H:i:s'))
                                              ->where('status', PushMessage::STATUS_NORMAL);
                                        if ($countryAbbr = array_get($wheres, 'countryAbbr')) {
                                          $query->where('country_abbr', $countryAbbr);
                                        }
                                      });
                              });

    $query = Pagination::paginate($query, ['base' => $displayOrder], true, $size);
    if ( ! $query) {
      return collect();
    }
    return $query->get();
  }
}
