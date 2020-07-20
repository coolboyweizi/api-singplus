<?php
/**
 * Created by PhpStorm.
 * User: karl
 * Date: 2018/2/27
 * Time: 下午4:18
 */

namespace SingPlus\Http\Controllers;

use Illuminate\Http\Request;
use SingPlus\Services\HierarchyService;
class HierarchyController extends Controller
{

    /**
     * get the user hierarchy info with hierarchy list
     *
     * @param Request $request
     * @param HierarchyService $hierarchyService
     * @return \Illuminate\Http\Response
     */
    public function userPopularityHierarchy(Request $request,
                                            HierarchyService $hierarchyService){
        $this->validate($request, [
            'userId' => 'uuid|required',
        ]);
        $hierarchy = $hierarchyService->getUserPopularityHierarchy($request->query->get('userId'));
        $lists = $hierarchyService->getPopularityHierarchyList();
        return $this->render('hierarchy.userHierarchyWithList', [
            'hierarchy' => $hierarchy,
            'lists' =>$lists
        ]);
    }

    /**
     * get the user wealth hierarchy info with hierarchy list
     *
     * @param Request $request
     * @param HierarchyService $hierarchyService
     * @return \Illuminate\Http\Response
     */
    public function userWealthHierarchy(Request $request,
                                        HierarchyService $hierarchyService){
        $this->validate($request, [
            'userId' => 'uuid|required',
        ]);
        $hierarchy = $hierarchyService->getUserWealthHierarchy($request->query->get('userId'));
        $lists = $hierarchyService->getWealthHierarchyList();
        return $this->render('hierarchy.wealthHierarchyWithList',[
            'hierarchy' => $hierarchy,
            'lists' => $lists
        ]);
    }

    public function wealthRank(Request $request,
                               HierarchyService $hierarchyService){
        $this->validate($request, [
            'type' => 'string|required',
            'rankId'  => 'uuid|required_with:isNext',
            'isNext'  => 'boolean',
            'size'    => 'integer|min:1|max:50',
        ]);
        $ranks = $hierarchyService->getWealthRank(
            $request->query->get('type'),
            $request->query->get('rankId'),
            $request->query->get('isNext', true),
            $request->query->get('size', $this->defaultPageSize)
        );
        return $this->render('hierarchy.wealthRank', [
           'ranks' => $ranks
        ]);
    }

}