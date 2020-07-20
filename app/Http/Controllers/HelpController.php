<?php

namespace SingPlus\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
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
    $actingUser = $request->user();
    $actingUserId = $actingUser ? $actingUser->id : "";
    $actingUserId = Auth::check() ? $actingUserId : "";
    $helpService->commitFeedback(
      $actingUserId,
      $request->request->get('message'),
      $countryAbbr
    );

    return  $this->renderInfo('success');
  }

  /**
   * User commit music search feedback
   */
  public function commitMusicSearchFeedback(
    Request $request,
    HelpServiceContract $helpService
  ) {
    $this->validate($request, [
      'musicName'   => 'required|string|max:20',
      'artistName'  => 'nullable|string|max:20',
      'language'    => 'nullable|string|max:20',
      'other'       => 'nullable|string|max:200',
    ]);

    $countryAbbr = $request->headers->get('X-CountryAbbr');
    $actingUser = $request->user();
    $actingUserId = $actingUser ? $actingUser->id : "";
    $actingUserId = Auth::check() ? $actingUserId : "";
    $helpService->commitMusicSearchFeedback(
      $actingUserId,
      $request->request->get('musicName'),
      (string) $request->request->get('artistName'),
      (string) $request->request->get('language'),
      (string) $request->request->get('other'),
      $countryAbbr
    );

    return $this->renderInfo('success');
  }

  /**
   * User commit music accompaniment feedback
   */
  public function commitAccompanimentFeedback(
    Request $request,
    HelpServiceContract $helpService
  ) {
    $this->validate($request, [
      'musicId'     => 'required|uuid',
      'musicName'   => 'required|string|max:20',
      'artistName'  => 'nullable|string|max:20',
      'accompanimentVersion'  => 'nullable|string|max:64',
      'type'        => 'integer|in:0,1,2',
    ]);

    $countryAbbr = $request->headers->get('X-CountryAbbr');
    $actingUser = $request->user();
    $actingUserId = $actingUser ? $actingUser->id : "";
    $actingUserId = Auth::check() ? $actingUserId : "";
    $helpService->commitAccompanimentFeedback(
      $actingUserId,
      $request->request->get('musicId'),
      $request->request->get('musicName'),
      (string) $request->request->get('artistName'),
      (string) $request->request->get('accompanimentVersion'),
      (int) $request->request->get('type'),
      $countryAbbr
    );

    return $this->renderInfo('success');
  }
}
