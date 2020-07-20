<?php

namespace SingPlus\Domains\Coins\Repositories;

use Illuminate\Support\Collection;
use SingPlus\Support\Database\Eloquent\Pagination;
use SingPlus\Domains\Coins\Models\CoinTransaction;

class CoinTransRepository
{
    /**
     * @param string $transId
     * 
     * @return ?CoinTransaction
     */
    public function findOneById(string $transId) : ?CoinTransaction
    {
        return CoinTransaction::find($transId);
    }

    /**
     * @param string $userId
     * @param ?int $source          filter by trans source
     * @param ?string $transId      for pagination
     * @param int $size             for pagination
     *
     * @return Collection           elements are CoinTransaction
     */
    public function findAllForPaginationByUserId(
        string $userId,
        ?int $source,
        ?int $displayOrder,
        int $size
    ) : Collection {
        $query = CoinTransaction::where('user_id', $userId);
        if (! is_null($source)) {
            $query->where('source', $source);
        }

        $query = Pagination::paginate($query, ['base' => $displayOrder], true, $size);

        return $query ? $query->get() : collect();
    }

    /**
     * @param string $taskId        admin task id
     *
     * @return ?CoinTransaction
     */
    public function findOneByTaskId(string $taskId) : ?CoinTransaction
    {
        return CoinTransaction::where('details.taskId', $taskId)->first();
    }
}
