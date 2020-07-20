<?php
/**
 * Created by PhpStorm.
 * User: karl
 * Date: 2018/3/21
 * Time: 下午4:18
 */

namespace SingPlus\Domains\Works\Repositories;


use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use SingPlus\Domains\Works\Models\WorkTag;

class WorkTagRepository
{

    /**
     * get work tag by title
     *
     * @param string $title
     * @return null|WorkTag
     */
    public function findOneByTitle(string $title):?WorkTag{
        return WorkTag::where('title', $title)->first();
    }

    /**
     * increment the work tag's join_count by title
     *
     * @param string $title
     * @return mixed
     */
    public function incrWorkTagJoinCount(string $title){
        return WorkTag::where('title', $title)
            ->increment('join_count');
    }

    /**
     * @param string $regex
     * @param int $size
     * @param null|string $source
     * @return Collection
     */
    public function searchWorkTagByRegex(string $regex, int $size, ?string $source):Collection{

        $query = WorkTag::where('status', WorkTag::STATUS_NORMAL);
        if ($source){
            $query = $query->where('source', $source);
        }
        return $query->where('title', 'regexp', $regex)->take($size)->get();
    }

}