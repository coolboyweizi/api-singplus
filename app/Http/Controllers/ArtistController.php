<?php

namespace SingPlus\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use SingPlus\Http\Controllers\Controller;
use SingPlus\Services\ArtistService;

class ArtistController extends Controller
{
  /**
   * Get artist categories
   */
  public function getCategories(
    Request $request,
    ArtistService $artistService
  ) {
    $countryAbbr = $request->headers->get('X-CountryAbbr');
    $actingUser = $request->user();
    $actingUserId = $actingUser ? $actingUser->id : "";
    $actingUserId = Auth::check() ? $actingUserId : "";
    $hotArtists = $artistService->getHots(
      $actingUserId, $countryAbbr
    );

    return $this->render('artists.categories', [
      'hotArtists'  => $hotArtists,
    ]);
  }

  /**
   * Get artists by categories
   */
  public function listArtists(
    Request $request,
    ArtistService $artistService
  ) {
    $this->validate($request, [
      'category'  => 'required|integer',
    ]);
    $actingUser = $request->user();
    $actingUserId = $actingUser ? $actingUser->id : "";
    $actingUserId = Auth::check() ? $actingUserId : "";
    $defaultCountryCode = $this->getCountryCodeFromAbbr($request->headers->get('X-CountryAbbr'));
    $artists = $artistService->getCategoryArtists(
      $actingUserId,
      (int) $request->query->get('category'),
        $defaultCountryCode
    );

    return $this->render('artists.listByCategory', [
      'artists' => $artists,
    ]);
  }

  protected function getCountryCodeFromAbbr($countyAbbr) :string
  {
      $collection = collect(config('countrycode'));
      $countryCodeInfo = $collection->first(function ($value) use ($countyAbbr){
         return $value[0] == $countyAbbr;
      });
      return $countryCodeInfo ? (string)$countryCodeInfo[2] : '254';
  }
}
