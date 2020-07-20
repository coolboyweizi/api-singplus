<?php
/**
 * Created by PhpStorm.
 * User: karl
 * Date: 2018/4/8
 * Time: 上午10:53
 */

namespace SingPlus\Domains\Boomcoin\Repositories;


use SingPlus\Domains\Boomcoin\Models\Order;

class OrderRepository
{

    /**
     * @param string $id
     * @param array $fields
     * @return null|Order
     */
    public function findOneById(string $id, array $fields = ['*']):?Order{
        return Order::select(...$fields)->find($id);
    }

    /**
     * @param string $userId
     * @return null|Order
     */
    public function findLatestOneByUserId(string $userId, int $status = Order::STATUS_PENDING):?Order{
        $query = Order::where('user_id', $userId)
                ->where('status',$status);
        return $query->orderBy('display_order', 'desc')->first();
    }

}