<?php

namespace SingPlus\Domains\Orders\Repositories;

use SingPlus\Domains\Orders\Models\ChargeOrder;

class ChargeOrderRepository
{
    /**
     * @param string $orderId
     *
     * @return ?ChargeOrder
     */
    public function findOneById(string $orderId) : ?ChargeOrder
    {
        return ChargeOrder::find($orderId);
    }
}
