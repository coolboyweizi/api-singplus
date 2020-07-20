<?php
/**
 * Created by PhpStorm.
 * User: karl
 * Date: 2018/3/21
 * Time: 下午4:12
 */

namespace SingPlus\Contracts\Works\Services;


use Illuminate\Support\Collection;

interface WorkTagService
{

    /**
     * @param string $str
     * @return Collection   elements are \stdclass
     *                          - title string the title of work tag
     */
    public function searchWorkTags(string $str):Collection;

    /**
     * @param string $workId
     * @return mixed
     */
    public function updateWorkTags(string $workId);

    /**
     * @param string $tagTitle
     * @return null|\stdClass
     *              - title     string title of tag
     *              - desc      string desc of tag
     *              - joinCount     int the joinCount of tag
     *              - cover         string the cover url of tag
     */
    public function getWorkTagDetail(string $tagTitle):?\stdClass;


    public function getWorkTagJoinCountWithoutCaseSensitive(string $tagTitle):int;

}