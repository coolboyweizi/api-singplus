<?php

namespace SingPlus\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use SingPlus\Http\Controllers\Controller;
use SingPlus\Contracts\Users\Services\UserService as UserServiceContract;
use SingPlus\Contracts\Users\Constants\UserProfile as UserProfileConst;
use SingPlus\Services\UserService;
use SingPlus\Exceptions\ExceptionCode;
use SingPlus\Domains\Users\Models\User;
use Cache;

class UserController extends Controller
{
  /**
   * User init login password after login with thrid-party account
   */
  public function initLoginPassword(
    Request $request,
    UserServiceContract $userService
  ) {
    $this->validate($request, [
      'password'  => 'password',
    ]);

    $userService->initLoginPassword(
      $request->user()->id,
      $request->request->get('password')
    );

    return $this->renderInfo('success');
  }

  /**
   * User change password
   */
  public function changeLoginPassword(
    Request $request,
    UserService $userService
  ) {
    $this->validate($request, [
      'oldPassword'   => 'required|password',
      'password'      => 'required|password',
    ]);

    $userService->changeLoginPassword(
      $request->user()->id,
      $request->request->get('oldPassword'),
      $request->request->get('password')
    );

    return $this->renderInfo('success');
  }

  /**
   * Get user profile
   */
  public function getUserProfile(
    Request $request,
    UserService $userService
  ) {
    $this->validate($request, [
      'userId'  => 'uuid',
    ]);

    $userId = $request->query->get('userId');
    $actingUser = $request->user();
    $actingUserId = "";
    $actingUserId = $actingUser ? $actingUser->id : $actingUserId;
    $actingUserId = Auth::check() ? $actingUserId : "";

    $user = $userService->getUserProfile(
      $actingUserId,
      $userId ?: $actingUserId
    );

    return $this->render('users.profile', [
      'isSelf'    => ! $userId ? true : false,
      'user'      => $user->user,
      'profile'   => $user->userProfile,
    ]);
  }

  /**
   * User modify his/her profile
   */
  public function modifyUserProfile(
    Request $request,
    UserService $userService
  ) {
    $validGender = [
      UserProfileConst::GENDER_MALE,
      UserProfileConst::GENDER_FEMALE,
    ];
    $this->validate($request, [
      'nickName'    => 'string|max:50',
      'sex'         => sprintf('nullable|string|in:%s,%s', ...$validGender),
      'sign'        => 'string|max:140',
      'birthDate'   => 'string|date_format:Y-m-d',
    ]);

    $gender = $request->request->get('sex');
    $userService->modifyUserProfile(
      $request->user()->id,
      $request->request->get('nickName'),
      $gender ? $gender : null,
      $request->request->get('sign'),
      $request->request->get('birthDate')
    );

    return $this->renderInfo('success');
  }

  /**
   * Complete user profile
   */
  public function completeUserProfile(
    Request $request,
    UserService $userService
  ) {
    // keep flash data
    $request->session()->reflash();

    $this->validate($request, [
      'imageId'   => 'uuid',
      'nickName'  => 'required|string|max:50',
    ]);

    $isFromSocialite = $request->session()->get('loginFromSocialite') === true;
    if ($isFromSocialite) {
      $socialiteAvatar = $request->session()->get('socialiteAvatar');
      if ( ! $socialiteAvatar && ! $request->request->get('imageId')) {
        return $this->renderError('avatar is required', ExceptionCode::ARGUMENTS_VALIDATION);
      }

      $userService->completeUserProfileFromSocialite(
        $request->user()->id,
        $request->request->get('nickName'),
        $request->request->get('imageId'),
        $request->session()->get('socialiteAvatar')
      );
    } else {
      if ( ! $request->request->get('imageId')) {
        return $this->renderError('avatar is required', ExceptionCode::ARGUMENTS_VALIDATION);
      }

      $userService->completeUserProfile(
        $request->user()->id,
        $request->request->get('nickName'),
        $request->request->get('imageId')
      );
    }

    return $this->renderInfo('success');
  }

  /**
   * Bind user mobile
   */
  public function bindMobile(
    Request $request,
    UserService $userService
  ) {
    $this->validate($request, [
      'countryCode' => 'required|countrycode',
      'mobile'      => 'required|mobile',
      'code'        => 'required|size:4',
    ]);

    $userService->bindMobile(
      $request->user()->id,
      (int) $request->request->get('countryCode'),
      $request->request->get('mobile'),
      $request->request->get('code')
    );

    return $this->renderInfo('success');
  }

  /**
   * User rebind (change) his/her mobile
   */
  public function rebindMobile(
    Request $request,
    UserService $userService
  ) {
    $this->validate($request, [
      'countryCode'   => 'required|countrycode',
      'mobile'        => 'required|mobile',
      'unbindCode'    => 'required|size:4',
      'rebindCode'    => 'required|size:4',
    ]);

    $userService->rebindMobile(
      $request->user()->id,
      (int) $request->request->get('countryCode'),
      $request->request->get('mobile'),
      $request->request->get('unbindCode'),
      $request->request->get('rebindCode')
    );

    return $this->renderInfo('success');
  }

  /**
   * User Reset his/her password
   */
  public function resetPassword(
    Request $request,
    UserService $userService
  ) {
    $this->validate($request, [
      'countryCode'   => 'required|countrycode',
      'mobile'        => 'required|mobile',
      'password'      => 'required|password',
      'code'          => 'required|size:4',
    ]);

    $userService->resetPassword(
      (int) $request->request->get('countryCode'),
      $request->request->get('mobile'),
      $request->request->get('password'),
      $request->request->get('code')
    );

    return $this->renderInfo('success');
  }

  /**
   * Renew user mobile for staging and testing env
   * This api is a shortcut for reset user mobile in staging and testing env
   */
  public function renewMobile(
    Request $request
  ) {
    $request->headers->set('Accept', 'application/json');

    if ( ! in_array(config('app.env'), ['staging', 'testing'])) {
      return $this->renderError('operation forbidden');
    }

    $this->validate($request, [
      'mobile'  => 'required|mobile',
    ]);
    $user = User::where('mobile', 'like', '%' . $request->query->get('mobile') . '%')->first();
    if ( ! $user) {
      return $this->renderError('mobile not found');
    }
    $user->mobile = null;
    $user->save();

    return $this->renderInfo('success');
  }

  /**
   * User report his/her geo location
   */
  public function reportLocation(
    Request $request,
    UserService $userService
  ) {
    $this->validate($request, [
      'longitude'     => 'string|max:64',
      'latitude'      => 'string|max:64',
      'countryCode'   => 'countrycode',
      'abbreviation'  => 'nullable|string|max:10',
      'city'          => 'string',
      'auto'          => 'boolean'
    ]);

    $countryCode = $request->request->get('countryCode');
    $userService->reportUserLocation(
      $request->user()->id,
      $request->request->get('longitude'),
      $request->request->get('latitude'),
      is_null($countryCode) ? null : (int) $countryCode,
      $request->request->get('abbreviation'),
      $request->request->get('city'),
      $request->request->get('countryName'),
      (bool)$request->request->get('auto', true)
    );

    return $this->renderInfo('success');
  }

  /**
   * Get mobile user source
   */
  public function mobileUserSource(
    Request $request,
    UserService $userService
  ) {
    $this->validate($request, [
      'countryCode' => 'required|countrycode',
      'mobile'      => 'required|mobile',
    ]);

    $source = $userService->getMobileUserSource(
      $request->query->get('countryCode'),
      $request->query->get('mobile')
    );

    return $this->renderInfo('success', [
      'source'  => $source,
    ]);
  }

  public function autoCompleteUserInfo(
      Request $request,
      UserService $userService
  ){
      // keep flash data
      $request->session()->reflash();

      $this->validate($request, [
          'nickName'  => 'required|string|max:50',
      ]);

      $userService->autoCompleteUserInfo($request->user()->id,
          $request->get('nickName'),
          $request->session()->get('socialiteAvatar'));

      return $this->renderInfo('success');
  }

  public function getUsersProfiles(
      Request $request,
      UserService $userService
  ){
      $this->validate($request, [
          'ids'  => 'required|array',
      ]);
      $userIds = $request->request->get('ids', []);
      $profiles = $userService->getUsersProfiles(
          $request->user()->id,
          $userIds
      );

      return $this->render('users.profiles', [
          'profiles'   => $profiles,
      ]);
  }

    /**
     * @param Request $request
     * @param UserService $userService
     * @return \Illuminate\Http\Response
     */
  public function updateUserPreferenceConf(
      Request $request,
      UserService $userService
  ){
      $this->validate($request, [
          'prefName'  => 'required|string',
          'value' => 'required|int',
      ]);
      $userService->updateUserPref($request->user()->id,
          $request->request->get('prefName'),
          (bool)$request->request->get('value'));
      return $this->renderInfo('success');
  }


    public function renewCache(
        Request $request
    ) {

        if ( ! in_array(config('app.env'), ['staging', 'testing'])) {
            return $this->renderError('operation forbidden');
        }

        $this->validate($request, [
            'cacheKey'  => 'required|string',
        ]);

        Cache::forget($request->request->get('cacheKey'));
        return $this->renderInfo('success');
    }
}
