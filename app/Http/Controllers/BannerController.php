<?php

namespace SingPlus\Http\Controllers;

use Illuminate\Http\Request;
use SingPlus\Http\Controllers\Controller;
use SingPlus\Services\BannerService;

class BannerController extends Controller
{
  /**
   * Fetch banner list
   */
  public function listBanners(
    Request $request,
    BannerService $bannerService
  ) {
    $countryAbbr = $request->headers->get('X-CountryAbbr');
    $banners = $bannerService->listEffectiveBanners($countryAbbr);

    return $this->render('banners.list', [
      'banners' => $banners,
    ]);
  }
}
