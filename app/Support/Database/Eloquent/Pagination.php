<?php

namespace SingPlus\Support\Database\Eloquent;

use Illuminate\Database\Eloquent\Builder;

class Pagination
{
  /**
   * Build paginate query
   *
   * @param Builder $query
   * @param ?array    $order      order info, key as below:
   *                              - name string   order field name
   *                              - base int      order base sequence number
   *                              - desc bool     indicate whether order by desc or not
   *                              - size int      records should be fetch
   * @param bool $isNext          fetch next page if true, or fetch prev page
   * @param int $size             fetched number per page
   *
   * @return ?Builder             if there is needn't pagination, return null.
   *                              eg: user in first page and search prev page, in this
   *                                  scenario, we should not issue a database query
   */
  public static function paginate(Builder $query, ?array $order, bool $isNext, int $size) : ?Builder
  {
    $orderName = array_get($order, 'name', 'display_order');
    $orderBase = array_get($order, 'base', null);
    $desc = (bool) array_get($order, 'desc', true);

    if (is_null($orderBase) && ! $isNext) {
      return null;
    }

    if ($desc) {
      $nextOperator = '<';
      $prevOperator = '>';
      $orderDirection = 'desc';
    } else {
      $nextOperator = '>';
      $prevOperator = '<';
      $orderDirection = 'asc';
    }

    if ($orderBase) {
      if ($isNext) {
        $query->where($orderName, $nextOperator, $orderBase);
      } else {
        $query->where($orderName, $prevOperator, $orderBase);
      }
    }
    $query->orderBy($orderName, $orderDirection)->take($size);

    return $query;
  }
}
