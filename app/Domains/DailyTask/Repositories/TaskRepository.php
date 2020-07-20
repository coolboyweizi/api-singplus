<?php
/**
 * Created by PhpStorm.
 * User: karl
 * Date: 2018/2/3
 * Time: 下午1:59
 */

namespace SingPlus\Domains\DailyTask\Repositories;

use Illuminate\Support\Collection;
use SingPlus\Domains\DailyTask\Models\Task;

class TaskRepository
{

    /**
     * @param string $category
     * @return Collection
     */
    public function findAllByCategory(string $category):Collection{
        $query =  Task::where('status', Task::STATUS_NORMAL)
            ->where('category', $category);
        return $query->orderBy('type','asc')->get();
    }

    /**
     * @param string $category
     * @param string $type
     * @return null|Task
     */
    public function findOneByCategoryAndType(string $category, string $type): ?Task{
        return Task::where('category', $category)
            ->where('type', $type)->first();
    }

}