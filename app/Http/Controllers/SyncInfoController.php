<?php
/**
 * Created by PhpStorm.
 * User: karl
 * Date: 2018/3/19
 * Time: 下午2:51
 */

namespace SingPlus\Http\Controllers;


use Illuminate\Http\Request;
use SingPlus\Services\SyncInfoService;

class SyncInfoController extends Controller
{

    /**
     * sync user's accompaniments
     *
     * @param Request $request
     * @param SyncInfoService $syncInfoService
     * @return \Illuminate\Http\Response
     */
    public function accompanimentSync(
        Request $request,
        SyncInfoService $syncInfoService)
    {

        $newSyncInfo = $syncInfoService->updateAccompanimentSyncInfo(
            $request->user()->id,
            $request->getContent()
        );

        return $this->render('syncInfo.accompanimentSync', [
            'syncData' => $newSyncInfo->data
        ]);
    }


    /**
     * remove an item accompaniment sync info according to key 'id' in data
     *
     * @param Request $request
     * @param SyncInfoService $syncInfoService
     * @return \Illuminate\Http\Response
     */
    public function accompanimentRemoveItem(
        Request $request,
        SyncInfoService $syncInfoService)
    {
        $this->validate($request, [
            'id'  => 'required|string',
        ]);

        $syncInfoService->removeAccompanimentSyncInfoItem(
            $request->user()->id,
            (string)$request->query->get('id')
        );

        return $this->renderInfo('success');
    }

}