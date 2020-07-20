<?php

namespace SingPlus\Http\Middleware;

use Closure;
use Auth;
use Cache;
use Location;
use SingPlus\Contracts\Nationalities\Constants\Nationality as NationalityConstant;
use SingPlus\Contracts\Users\Services\UserProfileService as UserProfileServiceContract;
use SingPlus\Contracts\Users\Constants\User as UserConstant;

class Nationality
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
        if ( ! $this->enable()) {
          return $next($request);
        }

        $realCountryAbbr = $this->getCountryAbbr($request);
        $realCountryAbbr = strtoupper($realCountryAbbr) ?: null;
        $countryAbbr = $this->getOperationCountryAbbr($realCountryAbbr);
        $request->headers->set('X-CountryAbbr', $countryAbbr);
        $request->headers->set('X-RealCountryAbbr', $realCountryAbbr);

        $response = $next($request);

        $response->header('X-RealCountryAbbr', $realCountryAbbr);
        $response->header('X-CountryAbbr', $countryAbbr);

        return $response;
    }

    private function getOperationCountryAbbr($realCountryAbbr) : string
    {
      return in_array($realCountryAbbr, config('nationality.operation_country_abbr'))
                ? $realCountryAbbr
                : NationalityConstant::OTHER_COUNTRY_ABBR;
    }

    private function getCountryAbbr($request) : ?string
    {
      if ( ! $this->isExcept($request)) {
        if ($countryAbbr = $request->headers->get('X-CountryAbbr')) {
          return $countryAbbr;
        }
        if ($countryAbbr = $request->input('countryAbbr')) {
          return $countryAbbr;
        }
      }

      // check authentication
      if ($user = $request->user()) {
        $countryCode = object_get($user, 'country_code');
        $source = object_get($user, 'source');
        // 只有手机注册的用户才查找注册国家
        if ($source == UserConstant::SOURCE_MOBILE && $countryCode) {
          $countryInfo = collect(config('countrycode'))
                          ->filter(function ($info, $_) use ($countryCode) {
                              return $info[2] == $countryCode;
                          })->first();
          $countryAbbr = is_array($countryInfo) ? $countryInfo[0] : null;
          if ($countryAbbr) {
            return $countryAbbr;
          }
        }

        $userProfileService = app()->make(UserProfileServiceContract::class);
        $location = $userProfileService->getUserLocation($user->id);

        $countryAbbr = object_get($location, 'abbreviation');
        if ($countryAbbr) {
          return $countryAbbr;
        }
      }

      if ($this->isExcept($request)) {
        if ($countryAbbr = $request->headers->get('X-CountryAbbr')) {
          return $countryAbbr;
        }
      }

      // check ip
      $ip = $request->ip();
      if ($countryAbbr = Cache::get($this->getIp2NationCacheKey($ip))) {
        return $countryAbbr;
      }
      $location = Location::get($request->ip());
      $countryAbbr = object_get($location, 'countryCode');
      Cache::put($this->getIp2NationCacheKey($ip), $countryAbbr, 30);   // 10 minutes

      return $countryAbbr;
    }

    private function getIp2NationCacheKey(string $ip) : string
    {
      return sprintf('ip2nation:%s', $ip);
    }

    private function isExcept($request) : bool
    {
      return $request->is('v3/user/common-info');
    }

    private function enable()
    {
      return ! app()->bound('middleware.nationality.disable') ||
             app()->make('middleware.nationality.disable') === false;
    }
}
