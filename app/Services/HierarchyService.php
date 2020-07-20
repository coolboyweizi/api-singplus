<?php
/**
 * Created by PhpStorm.
 * User: karl
 * Date: 2018/2/27
 * Time: 下午4:17
 */

namespace SingPlus\Services;
use Illuminate\Support\Collection;
use SingPlus\Contracts\Hierarchy\Services\PopularityHierarchyService as PopularityServiceContract;
use SingPlus\Contracts\Hierarchy\Services\WealthHierarchyService as WealthHierarchyServiceContract;
use SingPlus\Contracts\Users\Services\UserProfileService as UserProfileServiceContract;
use SingPlus\Contracts\Storages\Services\StorageService as StorageServiceContract;

class HierarchyService
{

    /**
     * @var PopularityServiceContract
     */
    private $popularityHierarchyService;

    /**
     * @var WealthHierarchyServiceContract
     */
    private $wealthHierarchyService;

    /**
     * @var StorageServiceContract
     */
    private $storageService;


    /**
     * @var UserProfileServiceContract
     */
    private $userProfileService;

    public function __construct(PopularityServiceContract $popularityHierarchyService,
                                StorageServiceContract $storageService,
                                WealthHierarchyServiceContract $wealthHierarchyService,
                                UserProfileServiceContract $userProfileService)
    {
        $this->popularityHierarchyService = $popularityHierarchyService;
        $this->storageService = $storageService;
        $this->wealthHierarchyService = $wealthHierarchyService;
        $this->userProfileService = $userProfileService;
    }

    /**
     * @return Collection  elements are \stdClass properties are below:
     *                      - name  string
     *                      - popularity    int
     *                      - icon      string  the url of icon
     *                      - iconSmall string
     *                      - alias     string alias name
     */
    public function getPopularityHierarchyList() : Collection {
        $hierarchys = $this->popularityHierarchyService->getHierarchyLists();
        return $hierarchys->map(function ($hierarch, $__){
            return (object)[
                'name' => $hierarch->name,
                'popularity' => $hierarch->popularity,
                'icon' => $this->storageService->toHttpUrl($hierarch->icon),
                'iconSmall' => $this->storageService->toHttpUrl($hierarch->iconSmall),
                'alias' => $hierarch->alias,
            ];
        });
    }

    /**
     * @param $userId
     * @return \stdClass
     *                      - name  string
     *                      - popularity    int
     *                      - icon      string  the url of icon
     *                      - iconSmall string
     *                      - alias     string alias name
     *                      - gapPopularity    int the gap to next hierarchy
     */
    public function getUserPopularityHierarchy($userId) : \stdClass{
        $hierarchy = $this->popularityHierarchyService->getUserPopularityHierarchy($userId);

        return (object)[
            'name' => $hierarchy->name,
            'icon' => $this->storageService->toHttpUrl($hierarchy->icon),
            'iconSmall' => $this->storageService->toHttpUrl($hierarchy->iconSmall),
            'alias' => $hierarchy->alias,
            'popularity' => $hierarchy->popularity,
            'gapPopularity' => $hierarchy->gapPopularity,
        ];
    }

    /**
     * @param $userId
     * @return \stdClass
     *              - name string the name of wealth hierarchy
     *              - icon string the icon of wealth hierarchy
     *              - iconSmall string
     *              - alias string
     *              - consumeCoins  int the amount coins of user
     *              - gapCoins      int the coins gap for up to next wealth hierarchy
     */
    public function getUserWealthHierarchy($userId) : \stdClass {
        $hierarchy = $this->wealthHierarchyService->getUserWealthHierarchy($userId);
        return (object)[
            'name' => $hierarchy->name,
            'icon' => $this->storageService->toHttpUrl($hierarchy->icon),
            'consumeCoins' => $hierarchy->consumeCoins,
            'gapCoins' => $hierarchy->gapCoins,
            'iconSmall' => $this->storageService->toHttpUrl($hierarchy->iconSmall),
            'alias' =>$hierarchy->alias
        ];
    }

    /**
     * @return Collection   element are \stdClass properties as below:
     *          - name  string  name of wealth hierarchy
     *          - icon  string  urr of icon
     *          - consumeCoins    int     coins needed to consume
     *          - iconSmall string
     *          - alias string
     */
    public function getWealthHierarchyList() : Collection {
        $hierarchys = $this->wealthHierarchyService->getHierarchyLists();
        return $hierarchys->map(function ($hierarch, $__){
            return (object)[
                'name' => $hierarch->name,
                'consumeCoins' => $hierarch->consumeCoins,
                'icon' => $this->storageService->toHttpUrl($hierarch->icon),
                'iconSmall' => $this->storageService->toHttpUrl($hierarch->iconSmall),
                'alias' => $hierarch->alias
            ];
        });
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
    public function getWealthRank(
        string $type,
        ?string $rankId,
        bool $isNext,
        int $size
    ) : Collection {
       $ranks = $this->wealthHierarchyService->getWealthRank($type, $rankId, $isNext, $size);
       return $ranks->map(function($rank, $__){
           $rank->avatar = $this->storageService->toHttpUrl($rank->avatar);
           $rank->wealthHierarchy->icon = $this->storageService->toHttpUrl($rank->wealthHierarchy->icon);
           $rank->wealthHierarchy->iconSmall = $this->storageService->toHttpUrl($rank->wealthHierarchy->iconSmall);
           return $rank;
       });
    }

}