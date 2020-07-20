<?php
/**
 * Created by PhpStorm.
 * User: karl
 * Date: 2018/1/29
 * Time: 下午7:39
 */

namespace SingPlus\Domains\Gifts\Repositories;


use Illuminate\Support\Collection;
use SingPlus\Domains\Gifts\Models\GiftContribution;
use SingPlus\Support\Database\Eloquent\Pagination;

class GiftContributionRepository
{
    /**
     * @param string $workId
     * @param int|null $coinAmount
     * @param bool $isNext
     * @param int $size
     * @return Collection       elements are GiftContribution
     */
    public function findAllByWorkIdForPagination(
        string $workId,
        ?int $coinAmount,
        bool $isNext,
        int $size
    ) : Collection{
        $query = GiftContribution::where('work_id', $workId);
        $query = Pagination::paginate($query, ['name' => 'coin_amount','base' => $coinAmount], $isNext, $size);
        if ( ! $query) {
            return collect();
        }
        return $query->get();
    }

    /**
     * @param string $workId
     * @param $displayOrder
     * @param bool $isNext
     * @param int $size
     * @return Collection           elements are GiftContribution
     */
    public function findAllByWorkIdForPaginationLatest(
        string $workId,
        $displayOrder,
        bool $isNext,
        int $size
    ) : Collection{
        $query = GiftContribution::where('work_id', $workId);
        $query = Pagination::paginate($query,
            [
                'name'  => 'updated_at',
                'base'  => $displayOrder
            ],
            $isNext,
            $size);
        if ( ! $query) {
            return collect();
        }
        return $query->get();
    }

    /**
     * @param string $senderId
     * @param string $workId
     * @param string $receiverId
     * @return GiftContribution
     */
    public function findOrCreateBySenderIdAndWorkId(string $senderId, string $workId, string $receiverId):GiftContribution{
        $giftContribution = GiftContribution::where('sender_id', $senderId)
            ->where('work_id', $workId)
            ->where('receiver_id', $receiverId)->first();
        if (!$giftContribution){
            $giftContribution = new GiftContribution([
                'sender_id' => $senderId,
                'work_id'   => $workId,
                'receiver_id'  => $receiverId,
                'coin_amount' => 0,
                'gift_amount' => 0,
                'gift_ids' => [],
                'gift_detail' => [],
            ]);
            $giftContribution->save();
        }

        return $giftContribution;
    }

    /**
     * @param string $id
     * @param array $fields
     * @return null|GiftContribution
     */
    public function findOneById(string $id, array $fields = ['*']) :?GiftContribution{
        return GiftContribution::select(...$fields)->find($id);
    }

    /**
     * @param string $workId
     * @param string $senderId
     * @return null|GiftContribution
     */
    public function findOneByWorkIdAndUserId(string $workId, string $senderId) :?GiftContribution{
        $giftContribution = GiftContribution::where('sender_id', $senderId)
            ->where('work_id', $workId)
            ->first();
        return $giftContribution;
    }

    /**
     * @param string $id
     * @param string $giftId
     * @param int $incrCoins
     * @param int $incrAmount
     */
    public function incrementCoinsAndAmountById(string $id, string $giftId, int $incrCoins, int $incrAmount){
            $giftContribution = $this->findOneById($id);

            $coin_amount = $giftContribution->coin_amount + $incrCoins;
            $gift_amount = $giftContribution->gift_amount + $incrAmount;

            if (in_array($giftId, $giftContribution->gift_ids )){
                $collect = collect($giftContribution->gift_detail);

                $detail = $collect->where('gift_id', $giftId)->first();
                $coins = array_get($detail, 'gift_coins', 0) + $incrCoins;
                $amount = array_get($detail, 'gift_amount', 0) + $incrAmount;

                $newDetail = $this->buildGiftDetail($giftId, $coins, $amount);

                GiftContribution::where('_id', $id)
                    ->pull('gift_detail', [
                        'gift_id' => $giftId
                    ]);

                GiftContribution::where('_id', $id)
                    ->push('gift_detail', $newDetail);


            }else {

                GiftContribution::where('_id', $id)
                    ->push('gift_ids', $giftId, true);

                $newDetail = $this->buildGiftDetail($giftId, $incrCoins, $incrAmount);

                GiftContribution::where('_id', $id)
                    ->push('gift_detail', $newDetail);

            }

            GiftContribution::where('_id', $id)
                ->update([
                    'coin_amount'  => $coin_amount,
                    'gift_amount'      => $gift_amount,
                ]);
    }

    /**
     * @param string $giftId
     * @param int $coins
     * @param int $amount
     * @return object
     */
    private function buildGiftDetail(string $giftId, int $coins, int $amount ){
        return (object)[
            'gift_id' => $giftId,
            'gift_coins' => $coins,
            'gift_amount' => $amount,
        ];
    }


}