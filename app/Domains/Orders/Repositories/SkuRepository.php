<?php

namespace SingPlus\Domains\Orders\Repositories;

use Illuminate\Support\Collection;
use SingPlus\Domains\Orders\Models\CoinSku;

class SkuRepository
{
    /**
     * @param string $skuId
     *
     * @return ?CoinSku
     */
    public function findOneBySkuid(string $skuId) : ?CoinSku
    {
        return CoinSku::where('sku_id', $skuId)
                      ->where('status', CoinSku::STATUS_SHELVE)
                      ->first();
    }

    /**
     * Get all valid skus
     *
     * @return Collection       elements are CoinSku
     */
    public function getAll() : Collection
    {
        return CoinSku::where('status', CoinSku::STATUS_SHELVE)->get();
    }
}
