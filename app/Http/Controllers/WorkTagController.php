<?php
/**
 * Created by PhpStorm.
 * User: karl
 * Date: 2018/3/22
 * Time: 下午5:30
 */

namespace SingPlus\Http\Controllers;


use Illuminate\Http\Request;
use SingPlus\Services\WorkTagService;

class WorkTagController extends Controller
{


    /**
     * @param Request $request
     * @param WorkTagService $workTagService
     * @return \Illuminate\Http\Response
     */
    public function searchWorkTags(
        Request $request,
        WorkTagService $workTagService
    ){
        $this->validate($request, [
            'tag' => 'required|string|max:100'
        ]);

        $tags = $workTagService->searchWorkTags(
            $request->query->get('tag'),
            (bool)$request->query->get('force', false)
        );

        return $this->render('workTags.tagsList', [
            'tags' => $tags
        ]);
    }


    /**
     * get workTag's detail info
     * @param Request $request
     * @param WorkTagService $workTagService
     * @return \Illuminate\Http\Response
     */
    public function workTagDetail(
        Request $request,
        WorkTagService $workTagService
    ){
        $this->validate($request, [
            'tag' => 'required|string|max:100'
        ]);

        $detail = $workTagService->getWorkTagDetail(
            $request->query->get('tag')
        );

        return $this->render('workTags.tagDetail',[
            'tag' => $detail
        ]);

    }

    /**
     * @param Request $request
     * @param WorkTagService $workTagService
     * @param string $type
     * @return \Illuminate\Http\Response
     */
    public function tagWorkList(Request $request,
                                WorkTagService $workTagService,
                                string $type){
        $request->query->set('type', $type);
        $this->validate($request, [
            'tag' => 'required|string|max:100',
            'id'      => 'uuid',
            'isNext'  => 'boolean',
            'size'    => 'integer|min:1|max:50',
        ]);

        $works = $workTagService->getTagWorkList(
            $request->query->get('tag'),
            $request->query->get('id'),
            (bool) $request->query->get('isNext', true),
            (int) $request->query->get('size', $this->defaultPageSize),
            $request->query->get('type')
        );
        return $this->render('workTags.tagWorkList', [
            'latests' => $works,
        ]);
    }

}