<?php

namespace SingPlus\Http\Controllers;

use Auth;
use Validator;
use Illuminate\Http\Request;
use SingPlus\Http\Controllers\Controller;
use SingPlus\Contracts\Coins\Constants\Trans as TransConst;
use SingPlus\Services\CoinService;

class CoinController extends Controller
{
    /**
     * Get all bills
     */
    public function getBill(
        Request $request,
        CoinService $coinService
    ) {
        $this->validate($request, [
            'billId'    => 'uuid',
            'size'      => 'integer|min:1|max:50',
        ]);

        $bills = $coinService->getUserBills(
            $request->user()->id,
            $request->query->get('billId'),
            $request->query->get('size', $this->defaultPageSize)
        );

        return $this->renderInfo('success', [
            'bills' => $bills->map(function ($bill, $_) {
                            return (object) [
                                'billId'    => $bill->id,
                                'value'     => $bill->amount,
                                'name'      => TransConst::source2Name($bill->source),
                                'time'      => $bill->transTime->timestamp,
                            ];
                        }),
        ]);
    }
}
