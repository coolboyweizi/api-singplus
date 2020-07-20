<?php
/**
 * Created by PhpStorm.
 * User: karl
 * Date: 2018/3/28
 * Time: 下午5:30
 */

namespace SingPlus\Support\Helpers;


class Arr
{
    // 使用键值互换去重，效率比array_unique更快
    public static function quickUnique(array $arr){
        $arr = array_flip($arr);
        $arr = array_flip($arr);
        return array_values($arr);
    }

    // 使用键值互换，然后判断是否value为建被设置了
    public static function quickInArray(array $arr, $value){
        $arr = array_flip($arr);
        return isset($arr[$value]);
    }
}