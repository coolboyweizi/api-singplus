<?php

namespace SingPlus\Aop\Aspects;

use Go\Aop\Aspect;
use Go\Aop\Intercept\MethodInvocation;
use Go\Lang\Annotation\Around;
use SingPlus\Services\MusicService as MusicService;
use SingPlus\Contracts\Helps\Services\HelpService as HelpServiceContract;

class FeedbackAspect implements Aspect
{
  /**
   * @var HelpServiceContract
   */
  private $helpService;

  public function __construct(HelpServiceContract $helpService)
  {
    $this->helpService = $helpService;
  }

  /**
   * @todo
   * Disabled.
   * Cause aop encounter some problem in staging server.
   *
   * System auto log music search if no result
   *
   * @param MethodInvocation $invocation
   * Around("execution(public SingPlus\Services\MusicService->getMusics(*))")
   */
  public function logMusicSearchIfNoResult(MethodInvocation $invocation)
  {
    list($userId, $querys, $musicId) = $invocation->getArguments();
    $search = array_get($querys, 'search');
    $res = $invocation->proceed();
    if ($search && ! $musicId && $res->isEmpty()) {
      $this->helpService->commitMusicSearchAutoFeedback($userId, $search);
    }

    return $res;
  }
}
