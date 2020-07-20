<?php

namespace SingPlus\Domains\Feeds\Repositories;

use Illuminate\Support\Collection;
use SingPlus\Support\Database\Eloquent\Pagination;
use SingPlus\Domains\Feeds\Models\Feed;

class FeedRepository
{
  /**
   * @param string $feedId
   *
   * @return ?Feed
   */
  public function findOneById(string $feedId) : ?Feed
  {
    return Feed::find($feedId);
  }

  /**
   * @param array $condition
   * @param ?int $displayOrder    display order,  for pagination
   * @param bool $isNext          for pagination
   * @param int $size
   *
   * @return Collection           elements are Feed
   */
  public function findAllForPagination(
    array $condition,
    ?int $displayOrder,
    bool $isNext,
    int $size
  ) : Collection {
    $query = Feed::where('status', Feed::STATUS_NORMAL);
    if ($userId = array_get($condition, 'userId')) {
      $query->where('user_id', $userId);
    }
    if ($types = array_get($condition, 'type')) {
      $query->whereIn('type', $types);
    }

    $query->where('operator_user_id', '!=', $userId);

    $query = Pagination::paginate($query, ['base' => $displayOrder], $isNext, $size);
    if ( ! $query) {
      return collect();
    }

    return $query->get();
  }

  /**
   * @param string $userId
   * @param array $types
   *
   * @return Collection      properties as below
   *                          - type string       feed type
   *                          - count int
   */
  public function countByUserAndType(string $userId, array $types) : Collection 
  {
    return Feed::raw(function ($collection) use ($userId, $types) {
      return $collection->aggregate([
        [
          '$match' => [
            'user_id' => $userId,
            'status'  => Feed::STATUS_NORMAL,
            'type'    => [
              '$in' => $types,
            ],
            'is_read' => Feed::READ_NO,
          ],
        ],
        [
          '$group'  => [
            '_id'   => '$type',
            'count' => [
              '$sum'  => 1,
            ],
          ]
        ],
      ]);
    })->map(function ($item, $_) {
      return (object) [
        'type'  => $item->_id,
        'count' => $item->count,
      ];
    });
  }

  /**
   * @param string $userId
   * @param array $types
   */
  public function updateAllByUserIdAndTypes(string $userId, array $types)
  {
    return Feed::where('user_id', $userId)
               ->where('status', Feed::STATUS_NORMAL)
               ->whereIn('type', $types)
               ->where('is_read', Feed::READ_NO)
               ->update([
                'is_read' => Feed::READ_YES,
               ]);
  }

    /**
     * @param array $ids
     * @return Collection
     */
  public function findAllByIds(array $ids){
      if (empty($ids)) {
          return collect();
      }
      return Feed::whereIn('_id', $ids)->get();
  }
}
