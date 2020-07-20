<?php

namespace SingPlus\Http\Controllers;

use Illuminate\Foundation\Bus\DispatchesJobs;
use Illuminate\Routing\Controller as BaseController;
use Illuminate\Foundation\Validation\ValidatesRequests;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use SingPlus\Http\Responses\ResponseFormatTrait;

class Controller extends BaseController
{
  use AuthorizesRequests, DispatchesJobs, ValidatesRequests;
  use ResponseFormatTrait;

  /**
   * Default list page size
   *
   * @var int
   */
  protected $defaultPageSize = 10;
}
