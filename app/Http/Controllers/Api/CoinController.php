<?php

namespace SingPlus\Http\Controllers\Api;

use Auth;
use Validator;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use SingPlus\Http\Controllers\Controller;
use SingPlus\Contracts\Coins\Constants\Trans as TransConst;
use SingPlus\Services\CoinService;

class CoinController extends Controller
{
    /**
     * Admin make trans
     */
    public function makeTrans(
        Request $request,
        CoinService $coinService
    ) {
        $this->validate($request, [
            'taskId'    => 'required|uuid',
            'userId'    => 'required|uuid',
            'operator'  => 'required|string|max:128',
            'amount'    => 'required|int',
            'type'      => [
                    'required',
                    Rule::in(TransConst::validAdminSource()),
                ],
            'details'   => 'array',
        ]);

        $coinService->makeTransByAdmin(
            $request->request->get('taskId'),
            $request->request->get('userId'),
            $request->request->get('operator'),
            $request->request->get('amount'), 
            $request->request->get('type'),
            $request->request->get('details', [])
        );

        return $this->renderInfo('success');
    }
}
