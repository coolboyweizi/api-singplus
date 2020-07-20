<?php
namespace SingPlus\Domains\Boomcoin\Repositories;

/**
 * Created by PhpStorm.
 * User: karl
 * Date: 2018/4/8
 * Time: 上午10:41
 */

use Illuminate\Support\Collection;
use SingPlus\Domains\Boomcoin\Models\Product;

class ProductRepository
{


    /**
     * @return Collection
     */
    public function findAll():Collection{
        $query = Product::where('status', Product::STATUS_NORMAL);
        return $query->orderBy('display_order', 'asc')
               ->get();
    }

    /**
     *  Increment product soldAmount
     * @param string $productId
     */
    public function incrementSoldAmount(string $productId){
        return Product::where('_id', $productId)->increment('sold_amount');
    }

    /**
     * @param string $productId
     * @param array $fields
     * @return null|Product
     */
    public function findOneById(string $productId, array $fields = ['*']):?Product{
        return Product::select(...$fields)->find($productId);
    }


}