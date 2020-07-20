<?php
/**
 * Created by PhpStorm.
 * User: karl
 * Date: 2018/2/28
 * Time: 下午6:30
 */

namespace SingPlus\Domains\Hierarchy\Services;
use Illuminate\Support\Collection;
use SingPlus\Contracts\Hierarchy\Constants\WealthCoefs;
use SingPlus\Contracts\Hierarchy\Services\WealthHierarchyService as WealthHierarchyServiceContract;
use SingPlus\Domains\Hierarchy\Models\Hierarchy;
use SingPlus\Domains\Users\Repositories\UserProfileRepository;
use SingPlus\Exceptions\Users\UserNotExistsException;
use SingPlus\Domains\Hierarchy\Repositories\WealthRankRepository;
use SingPlus\Domains\Hierarchy\Repositories\HierarchyRepository;

class WealthHierarchyService implements WealthHierarchyServiceContract
{


    /**
     * @var UserProfileRepository
     */
    private $userProfileRepo;

    /**
     * @var WealthRankRepository
     */
    private $wealthRankRepo;

    /**
     * @var HierarchyRepository
     */
    private $hierarchyRepo;

    public function __construct(
        UserProfileRepository $userProfileRepository,
        WealthRankRepository $wealthRankRepository,
        HierarchyRepository $hierarchyRepository
    ) {
        $this->userProfileRepo = $userProfileRepository;
        $this->wealthRankRepo = $wealthRankRepository;
        $this->hierarchyRepo = $hierarchyRepository;
    }

    /**
     * @return Collection   elements are \stdclass  properties are belows:
     *                      - name  string
     *                      - consumeCoins    int
     *                      - icon      string  the url of icon
     *                      - iconSmall string
     *                      - alias string
     */
    public function getHierarchyLists(): Collection
    {
        // get list from configuration file
        $wealthHierarchys = $this->hierarchyRepo->findAllByType(Hierarchy::TYPE_WEALTH);

        return $wealthHierarchys->map(function($wealthHierarchy, $__){
            return (object)[
                'name' => $wealthHierarchy->name,
                'consumeCoins' => $wealthHierarchy->amount,
                'icon' => $wealthHierarchy->icon,
                'iconSmall' => $wealthHierarchy->icon_small,
                'alias' => $wealthHierarchy->alias
            ];
        });
    }

    /**
     * update wealth hierarchy of user
     * @param $userId
     * @return mixed
     */
    public function updateWealthHierarchy($userId)
    {
        $profile = $this->userProfileRepo->findOneByUserId($userId);
        if (!$profile){
            throw new UserNotExistsException();
        }
        $wealthHierarchys = $this->hierarchyRepo->findAllByType(Hierarchy::TYPE_WEALTH);

        $consumeCoins = object_get($profile, 'consume_coins_info')  ? array_get($profile->consume_coins_info, 'consume_coins', 0) : 0;
        $newHierarchys = WealthCoefs::calculateWealthHierarchy($consumeCoins, $wealthHierarchys->toArray());
        $consumeInfo = [
            'consume_coins'     => $newHierarchys->consumeCoins,
            'hierarchy_id'      => $newHierarchys->hierarchyId,
            'hierarchy_gap'   => $newHierarchys->gapCoins,
        ];
        $profile->consume_coins_info = $consumeInfo;
        $profile->save();
        return $consumeInfo;
    }

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
    public function getUserWealthHierarchy($userId): \stdClass
    {
        $profile = $this->userProfileRepo->findOneByUserId($userId);
        if (!$profile){
            throw new UserNotExistsException();
        }
        $wealthHierarchys = $this->hierarchyRepo->findAllByType(Hierarchy::TYPE_WEALTH);
        $userWealthInfo = WealthCoefs::checkWealthHierarchyInfo($profile, $wealthHierarchys->toArray());
        $hierarchyDetail = $wealthHierarchys->where('_id',$userWealthInfo->hierarchyId)->first();
        return (object)[
            'name' => $hierarchyDetail ? $hierarchyDetail->name : '',
            'icon' => $hierarchyDetail ? $hierarchyDetail->icon : '',
            'iconSmall' => $hierarchyDetail ? $hierarchyDetail->icon_small : '',
            'alias' => $hierarchyDetail ? $hierarchyDetail->alias : '',
            'gapCoins' => $userWealthInfo->gapCoins,
            'consumeCoins' => $userWealthInfo->consumeCoins
        ];

    }

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
    public function getWealthRank(string $type, ?string $rankId, bool $isNext, int $size): Collection
    {
        $displayOrder = null;
        if ($rankId) {
            $rank = $this->wealthRankRepo->findOneById($rankId, ['display_order']);
            $displayOrder = $rank ? $rank->display_order : null;
        }
        $ranks = $this->wealthRankRepo->findByTypeForPagination($type, $displayOrder, $isNext, $size);
        $userIds = [];
        $ranks->each(function ($rank, $_) use (&$userIds) {
            $userIds[] = $rank->user_id;
        });

        $userProfiles = $this->userProfileRepo->findAllByUserIds($userIds);

        $wealthHierarchys = $this->hierarchyRepo->findAllByType(Hierarchy::TYPE_WEALTH);

        return $ranks->map(function ($rank, $__) use($userProfiles, $wealthHierarchys){
            $profile = $userProfiles->where('user_id', $rank->user_id)->first();

            if (!$profile){
                return null;
            }
            $userHierarchyInfo = WealthCoefs::checkWealthHierarchyInfo($profile, $wealthHierarchys->toArray());
            $hierarchyDetail = $wealthHierarchys->where('_id', $userHierarchyInfo->hierarchyId)->first();

            return (object)[
                'userId' => $profile->user_id,
                'nickname' => $profile->nickname,
                'avatar' => $profile->avatar,
                'rankId' =>$rank->id,
                'wealthHierarchy' => (object)[
                    'name' => $hierarchyDetail ? $hierarchyDetail->name : '',
                    'icon' => $hierarchyDetail ? $hierarchyDetail->icon : '',
                    'iconSmall' => $hierarchyDetail ? $hierarchyDetail->icon_small : '',
                    'alias' => $hierarchyDetail ? $hierarchyDetail->alias : '',
                    'gapCoins' => $userHierarchyInfo->gapCoins,
                    'consumeCoins' => $userHierarchyInfo->consumeCoins
                ]
            ];

        })->filter(function ($rank, $__){
            return !is_null($rank);
        });
    }

}