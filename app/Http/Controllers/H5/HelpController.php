<?php

namespace SingPlus\Http\Controllers\H5;

use Illuminate\Http\Request;
use SingPlus\Http\Controllers\Controller;
use SingPlus\Contracts\Helps\Services\HelpService as HelpServiceContract;

class HelpController extends Controller
{
  /**
   * User commit feedback
   */
  public function commitFeedback(
    Request $request,
    HelpServiceContract $helpService
  ) {
    $this->validate($request, [
      'message' => 'required|string|max:200',
    ]);

    $countryAbbr = $request->headers->get('X-CountryAbbr');
    $helpService->commitFeedback(
      null,
      $request->request->get('message'),
      $countryAbbr
    );

    return  $this->renderInfo('success');
  }
}
