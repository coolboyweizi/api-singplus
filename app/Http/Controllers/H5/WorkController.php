<?php

namespace SingPlus\Http\Controllers\H5;

use Illuminate\Support\Facades\Log;
use SingPlus\Services\UserService;
use Validator;
use Illuminate\Http\Request;
use SingPlus\Http\Controllers\Controller;
use SingPlus\Services\WorkService;
use SingPlus\Exceptions\Works\WorkNotExistsException;
use Illuminate\Support\Facades\Config;

class WorkController extends Controller
{
  /**
   * Get user work
   */
  public function getUserWork(
    Request $request,
    WorkService $workService,
    string $workId
  ) {
    $validator = Validator::make([
      'workId'  => $workId,
    ], [
      'workId'  => 'required|uuid',
    ]);

    if ($validator->fails()) {
      throw new WorkNotExistsException();
    }

    $work = $workService->getDetail($workId, true);
    if ( ! $work) {
      throw new WorkNotExistsException();
    }
    $countryAbbr = $request->headers->get('X-CountryAbbr');
    $selections = $workService->getH5Selections($countryAbbr);

    return $this->render('works.detail', [
      'work'        => $work,
      'selections'  => $selections,
    ]);
  }

  /**
   * Get user work page
   */
  public function getUserWorkPage(
    Request $request,
    WorkService $workService,
    UserService $userService,
    string $workId
  ) {
    $validator = Validator::make([
      'workId'  => $workId,
    ], [
      'workId'  => 'required|uuid',
    ]);

    if ($validator->fails()) {
      throw new WorkNotExistsException();
    }

    $work = $workService->getDetail($workId, true);
    if ( ! $work) {
      throw new WorkNotExistsException();
    }
    $countryAbbr = $request->headers->get('X-CountryAbbr');
    $apiChannel = config('apiChannel.channel', 'singplus');
    if ($apiChannel == "gaaoplus"){
      $countryAbbr = 'IN';
    }
    $selections = $workService->getH5Selections($countryAbbr);

    $comments = $workService->getWorkComments(
      $workId, null, true, 10
    );
    $userId = $request->query->get('userId');
    $userInfo = null;
    if ($userId)
    {
        $userInfo = $userService->getUserProfile("",$userId);
        if ($userInfo){
            $userInfo=$userInfo->userProfile;
        }
    }

    return $this->render('works.detailPage', [
      'work'      => $work,
      'comments'  => $comments,
      'selections'  => $selections,
      'userInfo'  => $userInfo
    ]);
  }

  /**
   * Get work comment
   */
  public function getComments(
    Request $request,
    WorkService $workService,
    string $workId
  ) {
    $validator = Validator::make([
      'workId'  => $workId,
    ], [
      'workId'  => 'required|uuid',
    ]);

    if ($validator->fails()) {
      throw new WorkNotExistsException();
    }

    $comments = $workService->getWorkComments(
      $workId, null, true, 10
    );

    return $this->render('works.comments', [
      'comments'  => $comments,
      'clientVersion' => $request->headers->get('X-Version'),
    ]);
  }

  /**
   * Get loading page
   */
  public function getLoadingPage(
    Request $request,
    WorkService $workService
  ) {
    $apiChannel = config('apiChannel.channel', 'singplus');
    $countryAbbr = $request->headers->get('X-CountryAbbr');
    if ($apiChannel == "gaaoplus"){
      $countryAbbr = 'IN';
    }
    $selections = $workService->getH5Selections($countryAbbr);

    return $this->render('works.loadingPage', [
      'selections'  => $selections
    ]);
  }
  
}
