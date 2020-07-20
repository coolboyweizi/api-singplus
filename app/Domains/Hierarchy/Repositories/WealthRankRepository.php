<?php
/**
 * Created by PhpStorm.
 * User: karl
 * Date: 2018/3/1
 * Time: 上午9:49
 */

namespace SingPlus\Domains\Hierarchy\Repositories;


use Illuminate\Support\Collection;
use SingPlus\Domains\Hierarchy\Models\WealthRank;
use SingPlus\Support\Database\Eloquent\Pagination;

class WealthRankRepository
{

    /**
     * @param string $type
     * @param int|null $displayOrder
     * @param bool $isNext
     * @param int $size
     * @return Collection   elements are WealthRank
     */
    public function findByTypeForPagination(
        string $type,
        ?int $displayOrder,
        bool $isNext,
        int $size
    ) : Collection {
        $query = WealthRank::where('type', $type);
        $query = Pagination::paginate($query, ['base' => $displayOrder, 'desc' => false], $isNext, $size);
        if ( ! $query) {
            return collect();
        }
        return $query->get();
    }

    public function findOneById(string $id, array $fields = ['*']) : ? WealthRank{
        return WealthRank::select(...$fields)->find($id);
    }

}