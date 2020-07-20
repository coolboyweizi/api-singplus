<?php

namespace SingPlus\Domains\Orders\Services;

use Illuminate\Support\Collection;
use SingPlus\Contracts\Orders\Services\SkuService as SkuServiceContract;
use SingPlus\Domains\Orders\Repositories\SkuRepository;

class SkuService implements SkuServiceContract
{
    /**
     * @var SkuRepository
     */
    private $skuRepo;

    public function __construct(
        SkuRepository $skuRepo
    ) {
        $this->skuRepo = $skuRepo;
    }


    /**
     * {@inheritdoc}
     */
    public function getSku(string $skuId) : ?\stdClass
    {
        $sku = $this->skuRepo->findOneBySkuid($skuId);

        return $sku ? (object) [
            'skuId'     => $sku->sku_id,
            'coins'     => $sku->coins,
            'title'     => $sku->title,
            'price'     => $sku->price,
        ] : null;
    }

    /**
     * {@inheritdoc}
     */
    public function getAllSkus() : Collection
    {
        $skus = $this->skuRepo->getAll();

        return $skus->map(function ($sku, $_) {
            return (object) [
                'id'    => $sku->id,
                'skuId' => $sku->sku_id,
                'coins' => $sku->coins,
                'title' => (string) $sku->title,
                'price' => (string) $sku->price,
            ];
        });
    }
}
