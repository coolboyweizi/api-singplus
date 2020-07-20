<?php
/**
 * Created by PhpStorm.
 * User: karl
 * Date: 2018/3/2
 * Time: 上午10:36
 */

namespace SingPlus\Domains\Hierarchy\Models;
use SingPlus\Support\Database\Eloquent\MongodbModel;

class Hierarchy  extends MongodbModel
{

    const TYPE_USER = 'user';
    const TYPE_WEALTH = 'wealth';

    protected $collection = 'hierarchy';

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'name',  // string   name of hierarchy
        'alias',  // string   alias name of hierarchy
        'amount', // int    amount value for a hierarchy
        'icon',     // string icon of hierarchy
        'icon_small', // string small icon of hierarchy
        'type',        // string type of hierarchy see TYPE_USER TYPE_WEALTH
        'display_order' , // int    display order
    ];

}