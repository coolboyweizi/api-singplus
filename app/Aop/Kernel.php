<?php

namespace SingPlus\Aop;

use Go\Core\AspectKernel;
use Go\Core\AspectContainer;

/**
 * Application Aspect Kernel
 */
class Kernel extends AspectKernel
{

  /**
   * Configure an AspectContainer with advisors, aspects and pointcuts
   *
   * @param AspectContainer $container
   *
   * @return void
   */
  protected function configureAop(AspectContainer $container)
  {
  }
}
