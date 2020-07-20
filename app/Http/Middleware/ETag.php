<?php

namespace SingPlus\Http\Middleware;

use Closure;

class ETag
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
    $response = $next($request);

    // if this was a GET request
    if ($request->isMethod('get')) {
      // Generate Etag
      $etag = md5($response->getContent());
      $requestEtag = str_replace('"', '', $request->getETags());

      // Check to see if Etag has changed
      if ($requestEtag && $requestEtag[0] == $etag) {
        $response->setNotModified();
      }

      // set Etag
      $response->setEtag($etag);
    }

    return $response;
  }
}
