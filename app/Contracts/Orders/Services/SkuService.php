<?php

namespace SingPlus\Contracts\Orders\Services;

use Illuminate\Support\Collection;

interface SkuService
{
    /**
     * Get sku by sku id
     *
     * @param string $skuId
     *
     * @return ?\stdClass       elements as below:
     *                          - skuId string
     *                          - coins int
     *                          - title string
     *                          - price int         USD unit: micro
     */
    public function getSku(string $skuId) : ?\stdClass;

    /**
     * Get All shelved sku
     *
     * @return Collection       elements as below:
     *                          - id string
     *                          - skuId string
     *                          - coins int
     *                          - title string
     */
    public function getAllSkus() : Collection;
}
