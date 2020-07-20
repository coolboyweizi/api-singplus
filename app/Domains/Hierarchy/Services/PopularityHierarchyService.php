<?php



/**
 * Created by PhpStorm.
 * User: karl
 * Date: 2018/2/26
 * Time: 下午6:39
 */
namespace SingPlus\Domains\Hierarchy\Services;

use Illuminate\Support\Collection;
use SingPlus\Contracts\Hierarchy\Constants\PopularityCoefs;
use SingPlus\Contracts\Hierarchy\Services\PopularityHierarchyService as PopularityHierarchyServiceContract;
use SingPlus\Domains\Hierarchy\Models\Hierarchy;
use SingPlus\Domains\Works\Repositories\WorkRepository;
use SingPlus\Domains\Users\Repositories\UserProfileRepository;
use SingPlus\Domains\Hierarchy\Repositories\HierarchyRepository;
use SingPlus\Exceptions\Users\UserNotExistsException;
use SingPlus\Exceptions\Works\WorkNotExistsException;

class PopularityHierarchyService implements PopularityHierarchyServiceContract
{




    /**
     * @var WorkRepository
     */
    private $workRepo;

    /**
     * @var UserProfileRepository
     */
    private $userProfileRepo;

    /**
     * @var HierarchyRepository
     */
    private $hierarchyRepo;

    public function __construct(
        WorkRepository $workRepository,
        UserProfileRepository $userProfileRepository,
        HierarchyRepository $hierarchyRepository
    ) {
        $this->workRepo = $workRepository;
        $this->userProfileRepo = $userProfileRepository;
        $this->hierarchyRepo = $hierarchyRepository;
    }

    /**
     * @return Collection   elements are \stdclass  properties are belows:
     *                      - name  string
     *                      - popularity    int
     *                      - icon      string  the url of icon
     *                      - iconSmall string
     *                      - alias     string alias name
     */
    public function getHierarchyLists(): Collection
    {
        $popularityHierarchys = $this->hierarchyRepo->findAllByType(Hierarchy::TYPE_USER);

        return $popularityHierarchys->map(function($popularityHierarchy, $__){
                return (object)[
                    'name' => $popularityHierarchy->name,
                    'popularity' => $popularityHierarchy->amount,
                    'icon' => $popularityHierarchy->icon,
                    'iconSmall' => $popularityHierarchy->icon_small,
                    'alias' => $popularityHierarchy->alias
                ];
        });

    }

    /**
     * calculate the popularity of work and user
     * @param $workId
     * @return mixed
     */
    public function updatePopularity($workId)
    {
        $work = $this->workRepo->findOneById($workId);
        if (!$work){
            throw new WorkNotExistsException();
        }

        $userProfile = $this->userProfileRepo->findOneByUserId($work->user_id);
        if (!$userProfile){
            throw new UserNotExistsException();
        }

        $lastPopularity = $work->popularity ? $work->popularity : 0;
        $giftPopularity = object_get($work, 'gift_info')  ? array_get($work->gift_info, 'gift_popularity_amount', 0) : 0;
        $popularityCoefs = config('hierarchy.popularity_coefs');
        $newPopularity = PopularityCoefs::calculateWorkPopularity($work->duration,
            $work->listen_count, $work->favourite_count, $work->comment_count, $giftPopularity, $popularityCoefs);

        $newPopularity = max($lastPopularity, $newPopularity);
        $incrPopularity = $newPopularity - $lastPopularity;
        $this->workRepo->updateWorkPopularity($workId, $incrPopularity);
        // update user popularity hierarchy
        $userWorkPopularity = object_get($userProfile, 'popularity_info') ? array_get($userProfile->popularity_info, 'work_popularity', 0) : 0;
        $newUserWorkPopularity = $incrPopularity + $userWorkPopularity;
        // get list from configuration file
        $popularityHierarchys = $this->hierarchyRepo->findAllByType(Hierarchy::TYPE_USER);
        $newHierarchyInfo = PopularityCoefs::calculateUserHierarchy($newUserWorkPopularity, $popularityHierarchys->toArray());

        $popularityInfo = [
            'work_popularity'     => $newHierarchyInfo->popularity,
            'hierarchy_id'  => $newHierarchyInfo->hierarchyId,
            'hierarchy_gap'   => $newHierarchyInfo->gapPopularity,
        ];
        $userProfile->popularity_info = $popularityInfo;
        $userProfile->save();
        return $newPopularity;
    }

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
    public function getUserPopularityHierarchy($userId): \stdClass
    {
        $profile = $this->userProfileRepo->findOneByUserId($userId);
        if (!$profile){
            throw new UserNotExistsException();
        }
        $popularityHierarchys = $this->hierarchyRepo->findAllByType(Hierarchy::TYPE_USER);
        $userHierarchy = PopularityCoefs::checkPopularityHierarchyInfo($profile, $popularityHierarchys->toArray());
        $hierarchyDetail = $popularityHierarchys->where('_id',$userHierarchy->hierarchyId)->first();

        return (object)[
            'name' => $hierarchyDetail ? $hierarchyDetail->name : '',
            'icon' => $hierarchyDetail ? $hierarchyDetail->icon : '',
            'iconSmall' => $hierarchyDetail ? $hierarchyDetail->icon_small : '',
            'alias' => $hierarchyDetail ? $hierarchyDetail->alias : '',
            'gapPopularity' => $userHierarchy->gapPopularity,
            'popularity' => $userHierarchy->popularity
        ];

    }
}