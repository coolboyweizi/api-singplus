<?php
/**
 * Created by PhpStorm.
 * User: karl
 * Date: 2018/3/21
 * Time: 下午4:18
 */

namespace SingPlus\Domains\Works\Services;

use LogTXIM;
use Cache;
use Illuminate\Support\Collection;
use SingPlus\Contracts\Works\Services\WorkTagService as WorkTaskServiceContract;
use SingPlus\Domains\Works\Models\WorkTag;
use SingPlus\Domains\Works\Repositories\WorkRepository;
use SingPlus\Domains\Works\Repositories\WorkTagRepository;

class WorkTagService implements WorkTaskServiceContract
{

    private $defaultSize = 50;

    /**
     * @var WorkTagRepository
     */
    private $workTagRepo;

    /**
     * @var WorkRepository
     */
    private $workRepo;

    public function __construct(WorkTagRepository $workTagRepository, WorkRepository $workRepository)
    {
        $this->workTagRepo = $workTagRepository;
        $this->workRepo = $workRepository;
    }

    /**
     * @param string $str
     * @return Collection   elements are \stdclass
     *                          - title string the title of work tag
     */
    public function searchWorkTags(string $str): Collection
    {
        $regexs = $this->buildWorkTagSearchRegexs(preg_quote($str));
        $result = $this->workTagRepo->searchWorkTagByRegex($regexs->fullMatch, $this->defaultSize, null);
        if ($result->count() < $this->defaultSize){
            $normalResult = $this->workTagRepo->searchWorkTagByRegex($regexs->normalMatch,
                                                $this->defaultSize - $result->count(),
                                                WorkTag::SOURCE_OFFICIAL);
            $result = collect(['fullMatch' => $result->all(), 'normalMatch' => $normalResult->all()])->flatten();
        }
        // 排序的时候不区分大小写
        $sorted = $result->sort(function($a, $b){
            $upperA = strtoupper($a->title);
            $upperB = strtoupper($b->title);
            if ($upperA == $upperB){
                return 0;
            }

            return ($upperA < $upperB) ? -1 : 1;
        });
        $objs = $sorted->map(function($item, $__){
            return (object)[
                'title' => $item->title
            ];
        });
        return $objs;
    }


    private function buildWorkTagSearchRegexs(string $str):\stdClass {

        // 完全匹配str，并且不区分大小写
        $fullMatch = sprintf("/^%s$/i", $str);

        // 匹配str开头不包括str在内，并且不区分大小写
        $regexNormal = sprintf("/^%s+/i", $str);
        return (object)[
            'fullMatch' => $fullMatch,
            'normalMatch'  => $regexNormal
        ];
    }

    /**
     * @param string $workId
     * @return mixed
     */
    public function updateWorkTags(string $workId)
    {
        $work = $this->workRepo->findOneById($workId);
        if (!$work){
            return ;
        }

        $tags = $this->getTagsFromDesc($work->description);
        $work->work_tags = $tags;
        $work->save();
        if (empty($tags)){
            return;
        }
        foreach ($tags as $tag){
            $workTag = WorkTag::firstOrNew(['title'=> $tag]);
            $workTag->join_count = $workTag->join_count + 1;
            if (!$workTag->source){
                $workTag->source = WorkTag::SOURCE_USER;
            }
            if (!$workTag->status){
                $workTag->status = WorkTag::STATUS_NORMAL;
            }
            $workTag->save();

            //increment worktag join count without caseSensitive Cache
            $this->updateJoinCountCacheWithoutCaseSensitive($tag);
        }

    }

    private function getTagsFromDesc(?string $str) : array {
        $arr = array();
        preg_match_all('/#([^\p{P}\p{Z}\s]|_){1,100}/u', $str, $arr);
        $arr = array_unique($arr[0]);
        $result = [];
        foreach ($arr as $item){
            $item = str_replace('#', '', $item);
            $result[] = $item;
        }
        return $result;
    }

    /**
     * @param string $tagTitle
     * @return null|\stdClass
     *              - title     string title of tag
     *              - desc      string desc of tag
     *              - joinCount     int the joinCount of tag
     *              - cover         string the cover url of tag
     */
    public function getWorkTagDetail(string $tagTitle): ?\stdClass
    {
        $tag = $this->workTagRepo->findOneByTitle($tagTitle);
        if ($tag){
            return (object)[
                'title' => $tag->title,
                'desc'  => $tag->description ? $tag->description : null,
                'cover' => $tag->cover ? $tag->cover : null,
                'joinCount' => $tag->join_count ? $tag->join_count : 0
            ];
        }else {
            return null;
        }
    }

    /**
     * @param string $tagTitle
     * @return int
     */
    public function getWorkTagJoinCountWithoutCaseSensitive(string $tagTitle): int
    {
        // 完全匹配str，并且不区分大小写
        $fullMatch = sprintf("/^%s$/i", $tagTitle);

        $query = WorkTag::where('status', WorkTag::STATUS_NORMAL)
            ->where('title', 'regexp', $fullMatch);
        if ( ! $query) {
            return 0;
        }
        $collect =  $query->get();
        return $collect->sum('join_count');
    }

    /**
     * @param string $tagTitle
     */
    private function updateJoinCountCacheWithoutCaseSensitive(string $tagTitle){
        $cacheKey = sprintf("workTag:joincount:%s", strtoupper($tagTitle));
        $cacheData = Cache::get($cacheKey);
        if ($cacheData == null){
            $cacheData = $this->getWorkTagJoinCountWithoutCaseSensitive($tagTitle);
        }else {
            $cacheData = $cacheData + 1;
        }

        // 更新所有参与次数
        $expired = 24*60*2;
        Cache::put($cacheKey, $cacheData,$expired);
    }
}