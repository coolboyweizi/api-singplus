<?php

/**
 * Created by PhpStorm.
 * User: karl
 * Date: 2018/2/26
 * Time: 下午5:05
 */
namespace SingPlus\Contracts\Hierarchy\Constants;

use SingPlus\Domains\Users\Models\UserProfile;

class PopularityCoefs
{
    const TYPE_LISTEN_COUNT = 'listen_count';
    const TYPE_FAVOURITE_COUNT = 'favourite_count';
    const TYPE_COMMENT_COUNT = 'comment_count';
    const TYPE_DURATION_ONE_MIN = 'duration_1_min';
    const TYPE_DURATION_TWO_MIN = 'duration_2_min';
    const TYPE_DURATION_THREE_MIN = 'duration_3_min';
    const TYPE_DURATION_OTHER_MIN = 'duration_other_min';

    /**
     * @param int $millseconds
     * @return string
     */
    public static function getDurationType(int $millseconds) : string {
        $secs = (int)$millseconds / 1000;
        if ($secs >= 3 * 60){
            return self::TYPE_DURATION_OTHER_MIN;
        }else if ($secs >= 2 * 60 ){
            return self::TYPE_DURATION_THREE_MIN;
        }else if ($secs >= 1* 60){
            return self::TYPE_DURATION_TWO_MIN;
        }else {
            return self::TYPE_DURATION_ONE_MIN;
        }
    }

    /**
     * @param int $durationMills
     * @param int $listenCount
     * @param int $favouriteCount
     * @param int $commentCount
     * @param int $giftPopularity
     * @param array $popularityCoefs
     * @return int
     */
    public static function calculateWorkPopularity(int $durationMills, int $listenCount,
                                                  int $favouriteCount, int $commentCount,
                                                  int $giftPopularity, array $popularityCoefs) : int {
        $durationType = self::getDurationType($durationMills);

        return $popularityCoefs[$durationType] + $listenCount * $popularityCoefs[self::TYPE_LISTEN_COUNT]
            + $favouriteCount * $popularityCoefs[self::TYPE_FAVOURITE_COUNT]
            + $commentCount * $popularityCoefs[self::TYPE_COMMENT_COUNT]
            + $giftPopularity;
    }

    /**
     * @param $workPopularity
     * @param array $popularityHierarchys   elements are Hierarchy
     * @return \stdClass       elements are below:
     *                  - hierarchyId   string
     *                  - popularity    int
     *                  - gapPopularity     int
     */
    public static function calculateUserHierarchy($workPopularity, array $popularityHierarchys) : \stdClass{

        $firstHierarchy = array_first($popularityHierarchys);
        $lastHierarchy = array_last($popularityHierarchys);
        if ($firstHierarchy == null){
            return (object)[
                'hierarchyId' => '',
                'popularity' => (int)$workPopularity,
                'gapPopularity' => 0
            ];
        }
        if ($workPopularity == $firstHierarchy['amount']){
            return (object)[
                'hierarchyId' => $firstHierarchy['_id'],
                'popularity' => (int)$workPopularity,
                'gapPopularity' => $popularityHierarchys[1]['amount'] - $firstHierarchy['amount']
            ];
        }

        foreach ($popularityHierarchys as $key => $hierarchy){
            if ($workPopularity < $hierarchy['amount']){

                $currentHierarchy = $key > 0 ? $popularityHierarchys[$key - 1] : $hierarchy;
                
                return (object)[
                    'hierarchyId' => $currentHierarchy['_id'],
                    'popularity' => $workPopularity,
                    'gapPopularity' => $hierarchy['amount'] - (int)$workPopularity
                ];
            }
        }

        return (object)[
            'hierarchyId' => $lastHierarchy['_id'],
            'popularity' => (int)$workPopularity,
            'gapPopularity' => 0
        ];
    }

    /**
     * @param UserProfile $profile
     * @param array $popularityHierarchys
     * @return \stdClass
     *              - popularity   int
     *              - hierarchyId   string
     *              - gapPopularity     int
     */
    public static function checkPopularityHierarchyInfo(UserProfile $profile, array $popularityHierarchys) : \stdClass{
        $popularity = object_get($profile, 'popularity_info', [
            'work_popularity' => 0,
        ]);

        $workPopularity = (int) array_get($popularity, 'work_popularity', 0);

        if (!array_has($popularity, 'hierarchy_id') && sizeof($popularityHierarchys) > 0 ){
            $newHierarchyInfo = PopularityCoefs::calculateUserHierarchy($workPopularity, $popularityHierarchys);
            $popularityInfo = [
                'work_popularity'     => $newHierarchyInfo->popularity,
                'hierarchy_id'      => $newHierarchyInfo->hierarchyId,
                'hierarchy_gap'   => $newHierarchyInfo->gapPopularity,
            ];
            $profile->popularity_info = $popularityInfo;
            $profile->save();
            $popularity = object_get($profile, 'popularity_info', [
                'work_popularity' => 0,
            ]);
        }

        return (object)[
            'hierarchyId' => array_get($popularity, 'hierarchy_id', ''),
            'popularity' => (int) array_get($popularity, 'work_popularity', 0),
            'gapPopularity' => (int) array_get($popularity, 'hierarchy_gap', 0)
        ];
    }
}