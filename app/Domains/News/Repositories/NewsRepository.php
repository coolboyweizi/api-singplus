<?php
namespace SingPlus\Domains\News\Repositories;
use Illuminate\Support\Collection;
use SingPlus\Domains\News\Models\News;
use SingPlus\Support\Database\Eloquent\Pagination;

/**
 * Created by PhpStorm.
 * User: karl
 * Date: 2018/1/18
 * Time: 下午5:32
 */
class NewsRepository
{

    /**
     * @param string $newsId  news id
     * @param array $fields   specify which fields should be return
     * @param bool $force     trashed record will be fetch if true
     * @return null|News
     */
    public function findOneById(string $newsId, array $fields = ['*'], bool $force = false) : ?News{
        if ($force) {
            return News::withTrashed()->select(...$fields)->find($newsId);
        } else {
            return News::select(...$fields)->find($newsId);
        }
    }


    /**
     * @param array $condition
     * @param int|null $displayOrder
     * @param bool $isNext
     * @param int $size
     * @return Collection
     */
    public function findAllForPagination(
        array $condition,
        ?int $displayOrder,
        bool $isNext,
        int $size
    ) : Collection {
        $query = News::where('status', News::STATUS_NORMAL);
        if ($userId = array_get($condition, 'userId')) {
            $query->where('user_id', $userId);
        }
        if ($types = array_get($condition, 'type')) {
            $query->whereIn('type', $types);
        }

        $query = Pagination::paginate($query, ['base' => $displayOrder], $isNext, $size);
        if ( ! $query) {
            return collect();
        }

        return $query->get();
    }

    /**
     * @param array $userIds
     * @param int|null $displayOrder
     * @param bool $isNext
     * @param int $size
     * @return Collection
     */
    public function findAllByUserIdsForPagination(
        array $userIds,
        ?int $displayOrder,
        bool $isNext,
        int $size
    ) : Collection {

        $query = News::where('status', News::STATUS_NORMAL)
            ->whereIn('user_id', $userIds);
        $query = Pagination::paginate($query, ['base' => $displayOrder], $isNext, $size);
        if ( ! $query) {
            return collect();
        }
        return $query->get();
    }


    /**
     * @param array $newsIds
     */
    public function findAllByIds(array $newsIds) : Collection
    {
        return News::whereIn('_id', $newsIds) 
                   ->where('status', News::STATUS_NORMAL)
                   ->orderBy('display_order', 'desc')
                   ->get();
    }
}
