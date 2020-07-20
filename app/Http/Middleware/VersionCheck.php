<?php

namespace SingPlus\Http\Middleware;

use App;
use Closure;
use SingPlus\Exceptions\Commons\VersionDeprecatedException;
use SingPlus\Contracts\ClientSupports\Services\VersionUpdateService as VersionUpdateServiceContract;

class VersionCheck
{
  /*
   * @var VersionUpdateServiceContract
   */
  private $versionService;

  /**
   * url in this array will be except by version check
   */
  private $except = [
    'v3/startup',
  ];

  public function __construct(
    VersionUpdateServiceContract $versionService
  ) {
    $this->versionService = $versionService;
  }

  public function handle($request, Closure $next)
  {
    if (
      ! $this->inExceptArray($request) &&
      ! $this->shouldSkipMiddleware() &&
      $this->isVersionDeprecated($request)
    ) {
      throw new VersionDeprecatedException();
    }

    $this->determineLocale($request);
    $response = $next($request);
    $response->header('X-Language', App::getLocale());

    return $response;
  }

  private function determineLocale($request)
  {
    $lang = $request->headers->get('X-Language', config('app.locale'));
    if ( ! in_array($lang, config('lang.langs'))) {
        $lang = config('app.fallback_locale');
    }

    // set locale
    App::setLocale($lang);
  }

  private function isVersionDeprecated($request)
  {
    // check path prefix with v\d+
    $version = $request->headers->get('X-Version');
    $agent = $request->headers->get('X-Agent');
    $appName = trim(strtolower($request->headers->get('X-AppName', '')));

    if ( ! $version) {
      // close version check temprary, it will be open after new app version published
      return false;
      return true;
    }

    $version = $this->versionService->getUpdateStatus($version, $appName);

    return $version->force;
  }

  private function shouldSkipMiddleware()
  {
    return app()->bound('middleware.version.check.disable') &&
           app()->make('middleware.version.check.disable') === true;
  }

  /**
   * Determine if the request has a URI that should pass through CSRF verification.
   *
   * @param  \Illuminate\Http\Request  $request
   * @return bool
   */
  protected function inExceptArray($request)
  {
    foreach ($this->except as $except) {
      if ($except !== '/') {
        $except = trim($except, '/');
      }

      if ($request->is($except)) {
        return true;
      }
    }

    return false;
  }
}
