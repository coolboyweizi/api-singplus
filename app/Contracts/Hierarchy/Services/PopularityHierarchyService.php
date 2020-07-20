<?php

/**
 * Created by PhpStorm.
 * User: karl
 * Date: 2018/2/26
 * Time: 下午6:31
 */
namespace SingPlus\Contracts\Hierarchy\Services;
use Illuminate\Support\Collection;

interface PopularityHierarchyService
{

    /**
     * @return Collection   elements are \stdclass  properties are belows:
     *                      - name  string
     *                      - popularity    int
     *                      - icon      string  the url of icon
     *                      - iconSmall string
     *                      - alias     string alias name
     */
    public function getHierarchyLists() : Collection;


    /**
     * calculate the popularity of work and user
     * @param $workId
     * @return mixed
     */
    public function updatePopularity($workId);


    /**
     * get the hierarchy of a user
     * @param $userId
     * @return \stdClass
     *                      - name  string
     *                      - popularity    int
     *                      - icon      string  the url of icon
     *                      - iconSmall string
     *                      - alias     string alias name
     *                      - gapPopularity    int the gap to next hierarchy
     */
    public function getUserPopularityHierarchy($userId) : \stdClass;
}