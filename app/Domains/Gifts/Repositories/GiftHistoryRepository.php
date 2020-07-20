<?php
/**
 * Created by PhpStorm.
 * User: karl
 * Date: 2018/1/29
 * Time: 下午6:14
 */

namespace SingPlus\Domains\Gifts\Repositories;


use Illuminate\Support\Collection;
use SingPlus\Domains\Gifts\Models\GiftHistory;
use SingPlus\Support\Database\Eloquent\Pagination;
use SingPlus\Support\Database\SeqCounter;

class GiftHistoryRepository
{

    /**
     * @param string $senderId
     * @param string $receiverId
     * @param string $workId
     * @param int $amount
     * @param array $giftInfo
     * @return GiftHistory
     */
    public function createOne(string $senderId,
                              string $receiverId,
                              string $workId, int $amount, array $giftInfo) : GiftHistory{

        $giftHistory = new GiftHistory([
            'sender_id' => $senderId,
            'receiver_id' => $receiverId,
            'work_id' => $workId,
            'amount' => $amount,
            'gift_info' => $giftInfo,
            'display_order' => SeqCounter::getNext('giftHistory'),
            'status'       => GiftHistory::STATUS_NORMAL,
        ]);
        $giftHistory->save();
        return $giftHistory;
    }

    /**
     * @param string $id
     * @param array $fields
     * @return null|GiftHistory
     */
    public function findOneById(string $id, array $fields = ['*'], bool $force = false) :?GiftHistory{
        if ($force) {
            return GiftHistory::withTrashed()->select(...$fields)->find($id);
        } else {
            return GiftHistory::select(...$fields)->find($id);
        }
    }

    /**
     * @param array $ids
     * @return \Illuminate\Support\Collection       elements are GiftHistory
     */
    public function findAllByIds(array $ids, bool $force = false):Collection{
        if (empty($ids)) {
            return collect();
        }
        $query = GiftHistory::whereIn('_id', $ids);
        if (!$force){
            $query->where('stats', GiftHistory::STATUS_NORMAL);
        }
        return GiftHistory::whereIn('_id', $ids)->get();
    }

    /**
     * @param string $workId      work id
     * @param ?int $displayOrder  used for pagination
     * @param bool $isNext        used for pagination
     * @param int $size           used for pagination
     *
     * @return Collection         elements are GiftHistory
     */
    public function findWorkAllForPagination(
        string $workId,
        ?int $displayOrder,
        bool $isNext,
        int $size
    ) : Collection {
        $query = GiftHistory::where('work_id', $workId)->where('status', GiftHistory::STATUS_NORMAL);
        $query = Pagination::paginate($query, ['base' => $displayOrder], $isNext, $size);
        if ( ! $query) {
            return collect();
        }
        return $query->get();
    }


}