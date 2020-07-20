<?php
/**
 * Created by PhpStorm.
 * User: karl
 * Date: 2018/5/9
 * Time: 下午11:05
 */

namespace SingPlus\Activities;
use \Illuminate\Http\Request;

interface BaseActivity
{

    /**
     * check if the activity is expired
     * @return bool
     */
    public function isExpired():bool ;

    /**
     * check if the activity is available
     * @param Request $request
     * @return bool
     */
    public function isAvaliable(Request $request) :bool ;

    /**
     * do what the activity need to do
     * @param Request $request
     * @return mixed
     */
    public function performActivity(Request $request);

}