<?php
/**
 * Created by PhpStorm.
 * User: karl
 * Date: 2018/1/9
 * Time: 下午6:46
 */

namespace SingPlus\Http\Controllers;


use Illuminate\Http\Request;
use SingPlus\Services\UserService;

class IMController extends Controller
{

    /**
     * update the im status of a user
     *
     * @param Request $request
     * @param UserService $userService
     * @return \Illuminate\Http\Response
     */
    public function updateUserImStatus(
        Request $request,
        UserService $userService
    )
    {
        $this->validate($request, [
            'status' => 'required|int',
        ]);

        $userId = $request->user()->id;
        $status = $request->request->get('status');

        $result = $userService->updateUserImStatus($userId, $status == 1 ? true : false);


        return $this->render('im.updateUserImStatus', [
            'status'    => $result,
        ]);
    }

}