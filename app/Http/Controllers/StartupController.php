<?php

namespace SingPlus\Http\Controllers;

use Auth;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use SingPlus\Http\Controllers\Controller;
use SingPlus\Services\StartupService;
use SingPlus\Exceptions\ExceptionCode;
use SingPlus\Contracts\Notifications\Constants\Notification as NotificationConstant;

class StartupController extends Controller
{
  /**
   * App startup
   */
  public function startup(
    Request $request,
    StartupService $startupService
  ) {
    $this->validate($request, [
      'alias'         => 'nullable|string|max:256',
      'mobile'        => 'mobile',
      'countryCode'   => 'countrycode',
      'abbreviation'  => 'nullable|string|max:10',
      'latitude'      => 'nullable|string|max:64',
      'longitude'     => 'nullable|string|max:64',
    ]);

    $out = $startupService->startup(
      Auth::check() ? $request->user()->id : null,
      $request->headers->get('X-Version'),
      $request->request->get('alias') ? (object) [
        'alias'         => $request->request->get('alias'),
        'mobile'        => $request->request->get('mobile'),
        'countryCode'   => $request->request->get('countryCode'),
        'abbreviation'  => $request->request->get('abbreviation'),
        'latitude'      => $request->request->get('latitude'),
        'longitude'     => $request->request->get('longitude'),
      ] : null,
      trim(strtolower($request->headers->get('X-AppName', '')))
    );

    return $this->render('startups.startup', [
      'logged'  => $out->logged,
      'user'    => $out->user,
      'update'  => $out->update,
      'payPal'  => $out->payPal,
      'ads'     => $out->ads,
      'supportLangs'    => config('lang.langs'),
    ]);

/*
    return $this->renderInfo('', [
      'logged'  => $out->logged,
      'user'    => $out->user ? [
        'isNewUser' => $out->user->isNewUser,
      ] : null,
      'update'  => [
        'recommendUpdate' => $out->update->recommendUpdate, 
        'forceUpdate'     => $out->update->forceUpdate,
        'updateTips'      => $out->update->updateTips,
      ],
    ]);
    */
  }

  /**
   * Get user common info
   *
   * Client should invoke this api once on app start up and user logedin
   */
  public function getUserCommonInfo(
    Request $request,
    StartupService $startupService
  ) {
    $this->validate($request, [
      'abbreviation'  => 'nullable|string|max:10',
    ]);

    $countryAbbr = $request->headers->get('X-RealCountryAbbr');
    $res = $startupService->getUserCommonInfo(
      $request->user()->id,
      $countryAbbr
    );

    return $this->render('startsups.userCommonInfo', [
          'feedCounts'  => $res->feedCounts,
          'bindTopics'  => $res->bindTopics,
        ]);
  }
}
