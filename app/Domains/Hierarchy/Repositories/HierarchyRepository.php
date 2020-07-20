<?php
/**
 * Created by PhpStorm.
 * User: karl
 * Date: 2018/3/2
 * Time: 上午10:39
 */

namespace SingPlus\Domains\Hierarchy\Repositories;


use Illuminate\Database\Eloquent\Collection;
use SingPlus\Domains\Hierarchy\Models\Hierarchy;

class HierarchyRepository
{

    /**
     * @param $id
     * @param array $fields
     * @return null|Hierarchy
     */
    public function findOneById($id, $fields = ['*']) :?Hierarchy{
        return Hierarchy::select(...$fields)->find($id);
    }

    /**
     * @param $type
     * @return Collection   elements are Hierarchy
     */
    public function findAllByType($type) : Collection{
        $query =  Hierarchy::where('type', $type);
        return $query ? $query->orderBy('rank', 'asc')->get() : collect();
    }

}