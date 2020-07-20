<?php
/**
 * Created by PhpStorm.
 * User: karl
 * Date: 2018/5/8
 * Time: 下午10:31
 */

namespace SingPlus\Http\Middleware;

use Closure;
use SingPlus\Activities\ActivityManager;

class CheckActivity
{

    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @return mixed
     */
    public function handle($request, Closure $next)
    {
        if (!$this->shouldSkipMiddleware()){
            ActivityManager::handleActivity($request);
        }
        return $next($request);
    }

    private function shouldSkipMiddleware()
    {
        return app()->bound('middleware.activity.check.disable') &&
            app()->make('middleware.activity.check.disable') === true;
    }

}