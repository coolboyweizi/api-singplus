<?php

namespace SingPlus\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use LogReq;

class LogRequestResponse
{
  /**
   * Handle an incoming request.
   *
   * @param  \Illuminate\Http\Request  $request
   * @param  \Closure  $next
   * @param  string|null  $guard
   * @return mixed
   */
  public function handle(Request $request, Closure $next)
  {
    // let all request go
      $response = $next($request);
      // 将API当前的渠道设置在response的headers里面
      $response->header('X-ApiChannel', config('apiChannel.channel', 'singplus'));
      return $response;
  }

  public function terminate(Request $request, Response $response)
  {
    if (config('app.debug') == false) {
      return;
    }

    $context = [
      'querys'      => $request->query->all(),
      'reqBody'     => $request->getContent(),
      'respJson'    => str_contains($response->headers->get('Content-Type'), 'json'),
      'response'    => $response->getContent(),
    ];

    LogReq::debug(
      sprintf('[%s] %s %s', $request->ip(), $request->method(), $request->getPathInfo()),
      $context
    );
  }
}
