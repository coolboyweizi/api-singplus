<?php

namespace SingPlus\Http\Controllers;

use Validator;
use Illuminate\Http\Request;
use SingPlus\Http\Controllers\Controller;
use SingPlus\Services\WorkRankService;
use SingPlus\Exceptions\ExceptionCode;

class WorkRankController extends Controller
{
  /**
   * Get global work rank list
   */
  public function getGlobal(
    Request $request,
    WorkRankService $workRankService
  ) {
    $ranks = $workRankService->getGlobal();

    return $this->render('works.rankGlobal', [
      'ranks' => $ranks,
    ]);
  }

  /**
   * Get country work rank list
   */
  public function getCountry(
    Request $request,
    WorkRankService $workRankService
  ) {
    $countryAbbr = $request->headers->get('X-CountryAbbr');
    $ranks = $workRankService->getCountry($countryAbbr);

    return $this->render('works.rankCountry', [
      'ranks' => $ranks,
    ]);
  }

  /**
   * Get rookie work rank list
   */
  public function getRookie(
    Request $request,
    WorkRankService $workRankService
  ) {
    $ranks = $workRankService->getRookie();

    return $this->render('works.rankRookie', [
      'ranks' => $ranks,
    ]);
  }
}
