<?php

/**
 * Created by PhpStorm.
 * User: karl
 * Date: 2018/1/29
 * Time: 下午5:51
 */

namespace SingPlus\Domains\Gifts\Repositories;
use Illuminate\Support\Collection;
use SingPlus\Domains\Gifts\Models\Gift;

class GiftRepository
{

    static $normalStatus = [
        Gift::STATUS_NORMAL
    ];

    /**
     * @param string $giftId
     * @param array $fields
     * @param bool $force
     * @return null|Gift
     */
    public function findOneById(string $giftId, array $fields = ['*'], bool $force = false): ?Gift {
        if ($force) {
            return Gift::withTrashed()->select(...$fields)->find($giftId);
        } else {
            return Gift::select(...$fields)->find($giftId);
        }
    }

    /**
     * @param bool $force
     * @param array $fields
     * @return Collection   elements are Gifts
     */
    public function findAll(bool $force = false, array $fields = ['*']) : Collection{
        $query =  Gift::select(...$fields);
        if ( ! $force) {
            $query->whereIn('status', self::$normalStatus);
        }

        return $query->orderBy('display_order', 'desc')->get();
    }

    /**
     * @param string $giftId
     * @param int $count
     * @return mixed
     */
    public function incrSoldAmount(string $giftId, int $count){
        return Gift::where('_id', $giftId)->increment('sold_amount', $count);
    }

    /**
     * @param string $giftId
     * @param int $count
     * @return mixed
     */
    public function incrSolCoin(string $giftId, int $count){
        return Gift::where('_id', $giftId)->increment('sold_coin_amount', $count);
    }

}