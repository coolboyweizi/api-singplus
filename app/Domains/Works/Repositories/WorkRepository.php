<?php
/**
 * Docs please to see: https://github.com/jenssegers/laravel-mongodb/wiki/Complexe-aggragate-call
 */

namespace SingPlus\Domains\Works\Repositories;

use Illuminate\Support\Collection;
use SingPlus\Contracts\Works\Constants\WorkConstant;
use SingPlus\Support\Database\Eloquent\Pagination;
use SingPlus\Domains\Works\Models\Work;

class WorkRepository
{
  static $normalStatus = [
    Work::STATUS_NORMAL,
    Work::STATUS_CHORUS_ACCOMPANIMENT_PREPARE,
  ];

  /**
   * @param array $ids
   *
   * @return Collection   elements are Work
   */
  public function findAllByIds(array $ids, bool $force = false, array $fields = ['*'], bool $withPrivate = false) : Collection
  {
    if (empty($ids)) {
      return collect();
    }

    $query =  Work::select(...$fields)
                  ->whereIn('_id', $ids);
    if (!$withPrivate){
        $query->where(function($query) {
            $query->whereNull('is_private')
                ->orWhere('is_private', Work::IS_PRIVATE_NO);
        });
    }
    if ( ! $force) {
      $query->whereIn('status', self::$normalStatus);
    }

    return $query->orderBy('display_order', 'desc')->get();
  }

  /**
   * @param string $id      work id
   * @param array $fields   specify which fields should be return
   * @param bool $force     trashed record will be fetch if true
   */
  public function findOneById(string $id, array $fields = ['*'], bool $force = false) : ?Work
  {
    if ($force) {
      return Work::withTrashed()->select(...$fields)->find($id);
    } else {
      return Work::select(...$fields)->find($id);
    }
  }

  /**
   * @param string $clientId
   * @param array $fields       fields you want fetch
   *
   * @return ?Work
   */
  public function findOneByClientId(string $clientId, array $fields = ['*']) : ?Work
  {
    return Work::select(...$fields)->where('client_id', $clientId)->first();
  }

  /**
   * @param string $userId
   * @param ?int $displayOrder  used for pagination
   * @param bool $isNext        used for pagination
   * @param int $size           used for pagination
   *
   * @return Collection         elements are Work
   */
  public function findAllByUserForPagination(
    string $userId,
    ?int $displayOrder,
    bool $isNext,
    int $size,
    bool $includePrivate = false
  ) : Collection {
    $fields = [
      'user_id', 'music_id', 'name', 'cover', 'description', 'is_private',
      'listen_count', 'comment_count', 'favourite_count', 'transmit_count',
      'created_at', 'chorus_type', 'chorus_start_info.chorus_count',
      'chorus_join_info.origin_work_id','gift_info','popularity'
    ];
    $query = Work::select(...$fields)
                 ->where('user_id', $userId)
                 ->where(function ($query) {
                    $query->whereNull('chorus_type')
                          ->orWhere('chorus_type', WorkConstant::CHORUS_TYPE_JOIN);
                 })
                 ->whereIn('status', self::$normalStatus);
    if ( ! $includePrivate) {
      $query->where(function($query) {
        $query->whereNull('is_private')
              ->orWhere('is_private', Work::IS_PRIVATE_NO);
      });
    }

    $query = Pagination::paginate($query, ['base' => $displayOrder], $isNext, $size);
    if ( ! $query) {
      return collect();
    }
    return $query->get();
  }

  /**
   * @param string $userId
   * @param ?int $displayOrder  used for pagination
   * @param bool $isNext        used for pagination
   * @param int $size           used for pagination
   *
   * @return Collection         elements are Work
   */
  public function findAllChorusStartByUserForPagination(
    string $userId,
    ?int $displayOrder,
    bool $isNext,
    int $size,
    bool $includePrivate = false
  ) : Collection {
    $fields = [
      'user_id', 'music_id', 'name', 'cover', 'is_private',
      'created_at', 'chorus_type', 'chorus_start_info.chorus_count',
    ];
    $query = Work::select(...$fields)
                 ->where('chorus_type', WorkConstant::CHORUS_TYPE_START)
                 ->where('user_id', $userId)
                 ->whereIn('status', self::$normalStatus);
    if ( ! $includePrivate) {
      $query->where(function($query) {
        $query->whereNull('is_private')
              ->orWhere('is_private', Work::IS_PRIVATE_NO);
      });
    }

    $query = Pagination::paginate($query, ['base' => $displayOrder], $isNext, $size);
    if ( ! $query) {
      return collect();
    }
    return $query->get();
  }

  /**
   * @param string $originWorkId  origin chorus start work id
   * @param ?int $displayOrder    used for pagination
   * @param bool $isNext          used for pagination
   * @param int $size             used for pagination
   *
   * @return Collection           elements are Work
   */
  public function findAllChorusJoinByChorusStartWorkIdForPagination(
    string $originWorkId,
    ?int $displayOrder,
    bool $isNext,
    int $size
  ) {
    $fields = [
      'user_id', 'music_id', 'name', 'created_at'
    ];
    $query = Work::select(...$fields)
                 ->where('chorus_type', WorkConstant::CHORUS_TYPE_JOIN)
                 ->where('chorus_join_info.origin_work_id', $originWorkId)
                 ->whereIn('status', self::$normalStatus);

    $query = Pagination::paginate($query, ['base' => $displayOrder], $isNext, $size);
    if ( ! $query) {
      return collect();
    }
    return $query->get();
  }

  /**
   * Increment/Decrement work favourite_count
   *
   * @param string $workId
   * @param int $count        positive number stands increment
   *                          negative number stands decrement
   */
  public function updateWorkFavouriteCount(string $workId, int $count)
  {
    return Work::where('_id', $workId)->increment('favourite_count', $count);
  }

  /**
   * Increment work transmit count
   *
   * @param string $workId
   */
  public function incrWorkTransmitCount(string $workId)
  {
    return Work::where('_id', $workId)->increment('transmit_count');
  }

  /**
   * @param ?int $displayOrder  used for pagination
   * @param bool $isNext        used for pagination
   * @param int $size           used for pagination
   *
   * @return Collection         elements are Work
   */
  public function findAllForPagination(?int $displayOrder, bool $isNext, int $size) : Collection
  {
    $fields = [
      'user_id', 'music_id', 'name', 'cover', 'description',
      'listen_count', 'comment_count', 'favourite_count', 'transmit_count',
      'created_at', 'resource', 'chorus_type', 'chorus_start_info.chorus_count',
      'chorus_join_info.origin_work_id','gift_info','popularity'
    ];
    $query = Work::select(...$fields)
                 ->whereIn('status', self::$normalStatus)
                 ->where(function($query) {
                    $query->whereNull('is_private')
                          ->orWhere('is_private', Work::IS_PRIVATE_NO);
                 });
    $query = Pagination::paginate($query, ['base' => $displayOrder], $isNext, $size);
    if ( ! $query) {
      return collect();
    }
    return $query->get();
  /*
    return Work::raw(function ($collection) use ($displayOrder, $isNext, $size){
      $wheres = [
        'status'  => Work::STATUS_NORMAL,
      ];
      $orders = [
        'display_order' => -1,
      ];
      if ($workId) {
        if ($isNext) {
          $wheres['display_order'] = ['$gt' => $displayOrder];
        } else {
          $wheres['display_order'] = ['$lt' => $displayOrder];
          $orders = ['display_order' => 1];
        }
      }
      $aggregates = [
        [
          '$match'  => $wheres,
        ], [
          '$sort'   => $orders,
        ], [
          '$limit' => $size,
        ], [
          '$project' => [
            'workId'          => '$_id',
            'userId'          => '$user_id',
            'musicId'         => '$music_id',
            'cover'           => '$cover',
            'description'     => '$description',
            'listenCount'     => ['$size' => '$listens'],
            'commentCount'    => ['$size' => '$commens'],
            'favouriteCount'  => ['$size' => '$favourites'],
            'transmitCount'   => ['$size' => '$transmits'],
            'createdAt'       => '$created_at',
          ],
        ]
      ];
      return $collection->aggregate($aggregates);
    });
    */
  }

  /**
   * @param array $userIds      elements are user id
   * @param ?int $displayOrder  used for pagination
   * @param bool $isNext        used for pagination
   * @param int $size           used for pagination
   *
   * @return Collection         elements are Work
   */
  public function findAllByUserIdsForPagination(
    array $userIds,
    ?int $displayOrder,
    bool $isNext,
    int $size
  ) : Collection {
    $fields = [
      'user_id', 'music_id', 'name', 'cover', 'description', 'resource',
      'listen_count', 'comment_count', 'favourite_count', 'transmit_count',
      'created_at', 'chorus_type', 'chorus_start_info.chorus_count',
      'chorus_join_info.origin_work_id','gift_info','popularity'
    ];
    $query = Work::select(...$fields)
                 ->whereIn('user_id', $userIds)
                 ->where(function($query) {
                    $query->whereNull('is_private')
                          ->orWhere('is_private', Work::IS_PRIVATE_NO);
                 })
                 ->whereIn('status', self::$normalStatus);
    $query = Pagination::paginate($query, ['base' => $displayOrder], $isNext, $size);
    if ( ! $query) {
      return collect();
    }
    return $query->get();
  }

  /**
   * Get music's chorus work default ranking list
   *
   * @param string $musicId
   *
   * @return Collection       elements are Work
   */
  public function findAllChorusByMusicId(string $musicId) : Collection
  {
    $selectFields = [
      'user_id',
      'chorus_start_info.chorus_count'
    ];
    return Work::select(...$selectFields)
               ->where('music_id', $musicId)
               ->where('chorus_type', WorkConstant::CHORUS_TYPE_START)
               ->whereIn('status', self::$normalStatus)
               ->orderBy('chorus_start_info.chorus_count', 'desc')
               ->take(100)
               ->get();
  }

  /**
   * Get music's solo work default ranking list
   */
  public function findAllSoloByMusicId(string $musicId) : Collection
  {
    $selectFields = [
      'user_id',
      'listen_count',
    ];

    return Work::select(...$selectFields)
               ->where('music_id', $musicId)
               ->whereNull('chorus_type')
               ->whereIn('status', [
                  Work::STATUS_NORMAL,
                  Work::STATUS_CHORUS_ACCOMPANIMENT_PREPARE,
               ])
               ->orderBy('listen_count', 'desc')
               ->take(100)
               ->get();
  }

  /**
   * @param string $workId
   *
   * @return bool
   */
  public function incrWorkChorusCount(string $workId) : bool
  {
    return Work::where('_id', $workId)
               ->where('chorus_type', WorkConstant::CHORUS_TYPE_START)
               ->increment('chorus_start_info.chorus_count') > 0;
  }

    /*
     * @param string $workId
     *
     * @return bool
     */
    public function decrWorkChorusCount(string $workId) : bool
    {
        return Work::where('_id', $workId)
                   ->where('chorus_type', WorkConstant::CHORUS_TYPE_START)
                   ->decrement('chorus_start_info.chorus_count') > 0;
    }

  /**
   * @param string $musicId
   */
  public function findOneChorusStartByMusicId(string $musicId) : ?Work
  {
    return Work::where('music_id', $musicId)
               ->where('chorus_type', WorkConstant::CHORUS_TYPE_START)
               ->take(1)
               ->first();
  }

    /**
     * @param string $workId
     * @param int $incrGifts
     * @return mixed
     */
  public function incrWorkGiftAmount(string $workId, int $incrGifts){
      return Work::where('_id', $workId)
              ->increment('gift_info.gift_amount', $incrGifts) ;
  }

    /**
     * @param string $workId
     * @param int $incrCoins
     * @return mixed
     */
  public function incrWorkGiftCoinAmount(string $workId, int $incrCoins){
      return Work::where('_id', $workId)
          ->increment('gift_info.gift_coin_amount', $incrCoins) ;
  }

    /**
     * @param string $workId
     * @param int $incrPopularity
     * @return mixed
     */
  public function incrWorkGiftPopularity(string $workId, int $incrPopularity){
      return Work::where('_id', $workId)
          ->increment('gift_info.gift_popularity_amount', $incrPopularity) ;
  }

    /**
     * @param string $workId
     * @param int $workPopularity
     */
  public function updateWorkPopularity(string $workId, int $incrPopularity){
      return Work::where('_id', $workId)
          ->increment('popularity', $incrPopularity);
  }

    /**
     * @param string $workTag
     * @param ?int $displayOrder  used for pagination
     * @param bool $isNext        used for pagination
     * @param int $size           used for pagination
     *
     * @return Collection         elements are Work
     */
    public function findAllByWorkTagForPagination(
        string $workTag,
        ?int $displayOrder,
        bool $isNext,
        int $size
    ) : Collection {
        $fields = [
            'user_id', 'music_id', 'name', 'cover', 'description',
            'listen_count', 'comment_count', 'favourite_count', 'transmit_count',
            'created_at', 'resource', 'chorus_type', 'chorus_start_info.chorus_count',
            'chorus_join_info.origin_work_id','gift_info','popularity','work_tags'
        ];

        $query = Work::select(...$fields)
            ->whereIn('status', self::$normalStatus)
            ->where(function($query) {
                $query->whereNull('is_private')
                    ->orWhere('is_private', Work::IS_PRIVATE_NO);
            });
        $query->where('work_tags', 'elemmatch', ['$regex' => '^'.$workTag.'$', '$options' => 'i']);
        $query = Pagination::paginate($query, ['base' => $displayOrder], $isNext, $size);
        if ( ! $query) {
            return collect();
        }
        return $query->get();
    }
}
