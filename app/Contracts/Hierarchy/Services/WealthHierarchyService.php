<?php
/**
 * Created by PhpStorm.
 * User: karl
 * Date: 2018/2/28
 * Time: 下午6:30
 */

namespace SingPlus\Contracts\Hierarchy\Services;


use Illuminate\Support\Collection;

interface WealthHierarchyService
{

    /**
     * @return Collection   elements are \stdclass  properties are belows:
     *                      - name  string
     *                      - consumeCoins    int
     *                      - icon      string  the url of icon
     *                      - iconSmall string
     *                      - alias string
     */
    public function getHierarchyLists() : Collection;

    /**
     * update wealth hierarchy of user
     * @param $userId
     * @return mixed
     */
    public function updateWealthHierarchy($userId);

    /**
     * get the wealth hierarchy of a user
     * @param $userId
     * @return \stdClass
     *                      - name  string  the name of wealth hierarchy
     *                      - icon      string  the url of icon
     *                      - iconSmall string
     *                      - alias     string alias name
     *                      - consumeCoins    int   the consume coins
     *                      - gapCoins      int the gap coins to next hierarchy
     *
     */
    public function getUserWealthHierarchy($userId) : \stdClass;


    /**
     * @param string $type
     * @param ?string $rankId
     * @param bool $isNext
     * @param int $size
     * @return Collection   elements are \stdClass properties are:
     *                      - userId
     *                      - nickname
     *                      - avatar
     *                      - rankId
     *                      - wealthHierarchy stdClass
     *                          - name
     *                          - icon
     *                          - iconSmall
     *                          - alias
     *                          - consumeCoins
     *                          - gapCoins
     */
    public function getWealthRank(string $type, ?string $rankId, bool $isNext, int $size) :Collection;

}