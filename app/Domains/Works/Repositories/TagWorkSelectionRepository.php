<?php
/**
 * Created by PhpStorm.
 * User: karl
 * Date: 2018/3/26
 * Time: 下午4:44
 */

namespace SingPlus\Domains\Works\Repositories;


use Illuminate\Support\Collection;
use SingPlus\Domains\Works\Models\TagWorkSelection;
use SingPlus\Support\Database\Eloquent\Pagination;

class TagWorkSelectionRepository
{
    const MAX_SELECTION_COUNTS = 200;

    /**
     * @return Collection       elements are TagWorkSelection
     */
    public function findAll(string $workTag) : Collection
    {
        return TagWorkSelection::where('status', TagWorkSelection::STATUS_NORMAL)
            ->where('work_tag', $workTag)
            ->orderBy('display_order', 'desc')
            ->take(self::MAX_SELECTION_COUNTS)
            ->get();
    }

    /**
     * @param string $workTag
     * @param int|null $displayOrder
     * @param bool $isNext
     * @param int $size
     * @return Collection   elements are TagWorkSelection
     */
    public function findAllByWorkTagForPagination(
        string $workTag,
        ?int $displayOrder,
        bool $isNext,
        int $size
    ):Collection{
        $query = TagWorkSelection::where('status', TagWorkSelection::STATUS_NORMAL)
            ->where('work_tag', $workTag);
        $query = Pagination::paginate($query, ['base' => $displayOrder], $isNext, $size);
        if ( ! $query) {
            return collect();
        }
        return $query->get();
    }

    /**
     * @param string $workId
     * @param string $workTag
     * @param array $fields
     * @param bool $force
     * @return mixed
     */
    public function findOneByWorkIdAndTag(string $workId, string $workTag, array $fields = ['*'], bool $force = false){
        if ($force) {
            return TagWorkSelection::withTrashed()->select(...$fields)
                ->where('work_id', $workId)
                ->where('work_tag', $workTag)->first();
        } else {
            return TagWorkSelection::select(...$fields)
                ->where('work_id', $workId)
                ->where('work_tag', $workTag)->first();
        }
    }
}