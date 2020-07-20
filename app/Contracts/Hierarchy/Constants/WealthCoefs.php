<?php
/**
 * Created by PhpStorm.
 * User: karl
 * Date: 2018/2/28
 * Time: 下午5:42
 */

namespace SingPlus\Contracts\Hierarchy\Constants;

use SingPlus\Domains\Users\Models\UserProfile;

class WealthCoefs
{

    /**
     * calculate wealth hierarchy for user
     *
     * @param $consumeCoins
     * @param array $wealthHierarchys
     * @return \stdClass
     *              - hierarchyId   string
     *              - consumeCoins  int  the total consumeCoins
     *              - gapCoins  int the gapCoins to next hierarchy
     */
    public static function calculateWealthHierarchy($consumeCoins, array $wealthHierarchys) : \stdClass
    {

        $firstHierarchy = array_first($wealthHierarchys);
        $lastHierarchy = array_last($wealthHierarchys);
        if ($firstHierarchy == null){
            return (object)[
                'hierarchyId' => '',
                'consumeCoins' => $consumeCoins,
                'gapCoins' => 0
            ];
        }

        if ($consumeCoins == $firstHierarchy['amount']) {
            return (object)[
                'hierarchyId' => $firstHierarchy['_id'],
                'consumeCoins' => $consumeCoins,
                'gapCoins' => $wealthHierarchys[1]['amount'] - $firstHierarchy['amount']
            ];
        }

        foreach ($wealthHierarchys as $key => $hierarchy) {
            if ($consumeCoins < $hierarchy['amount']) {

                $currentHierarchy = $key > 0 ? $wealthHierarchys[$key - 1] : $hierarchy;

                return (object)[
                    'hierarchyId' => $currentHierarchy['_id'],
                    'consumeCoins' => $consumeCoins,
                    'gapCoins' => $hierarchy['amount'] - $consumeCoins
                ];
            }
        }

        return (object)[
            'hierarchyId' => $lastHierarchy['_id'],
            'consumeCoins' => $consumeCoins,
            'gapCoins' => 0
        ];
    }

    /**
     * @param UserProfile $profile
     * @param array $wealthHierarchys
     * @return \stdClass
     *              - hierarchyId   string
     *              - consumeCoins    int   the consume coins
     *              - gapCoins      int the gap coins to next hierarchy
     */
    public static function checkWealthHierarchyInfo(UserProfile $profile, array $wealthHierarchys) : \stdClass{
        $consumeInfo = object_get($profile, 'consume_coins_info', [
            'consume_coins' => 0,
        ]);

        $consumeCoins = (int) array_get($consumeInfo, 'consume_coins', 0);

        if (!array_has($consumeInfo, 'hierarchy_name') && sizeof($wealthHierarchys) > 0){
            $newHierarchyInfo = WealthCoefs::calculateWealthHierarchy($consumeCoins, $wealthHierarchys);
            $consumeCoinsInfo = [
                'consume_coins'     => $newHierarchyInfo->consumeCoins,
                'hierarchy_id'      => $newHierarchyInfo->hierarchyId,
                'hierarchy_gap'   => $newHierarchyInfo->gapCoins,
            ];
            $profile->consume_coins_info = $consumeCoinsInfo;
            $profile->save();
            $consumeInfo = object_get($profile, 'consume_coins_info', [
                'consume_coins' => 0,
            ]);
        }

        return (object)[
            'hierarchyId' => array_get($consumeInfo, 'hierarchy_id', ''),
            'consumeCoins' => (int) array_get($consumeInfo, 'consume_coins', 0),
            'gapCoins' => (int) array_get($consumeInfo, 'hierarchy_gap', 0)
        ];
    }

}