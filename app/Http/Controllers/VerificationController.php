<?php

namespace SingPlus\Http\Controllers;

use Illuminate\Http\Request;
use SingPlus\Http\Controllers\Controller;
use SingPlus\Services\VerificationService;

class VerificationController extends Controller
{
  /**
   * Send verifiy sms code for user register
   */
  public function sendRegisterVerifyCode(
    Request $request,
    VerificationService $verificationService
  ) {
    $this->validate($request, [
      'countryCode' => 'required|countrycode',
      'mobile'      => 'required|mobile',
    ]);

    $verification = $verificationService->sendRegisterVerifyCode(
      (int) $request->request->get('countryCode'),
      $request->request->get('mobile')
    );

    return $this->render('verifications.verification', [
      'verification'  => $verification,
    ]);
  }

  /**
   * Send verify sms code for user bind his/her mobile
   */
  public function sendMobileBindVerifyCode(
    Request $request,
    VerificationService $verificationService
  ) {
    $this->validate($request, [
      'countryCode' => 'required|countrycode',
      'mobile'      => 'required|mobile',
    ]);

    $verification = $verificationService->sendMobileBindVerifyCode(
      $request->user()->id,
      (int) $request->request->get('countryCode'),
      $request->request->get('mobile')
    );

    return $this->render('verifications.verification', [
      'verification'  => $verification,
    ]);
  }

  /**
   * Send verify code for user un-bind his/her exist mobile, when user change his/her mobile.
   * Then, user should fetch re-bind verify code for new mobile
   */
  public function sendMobileUnbindVerifyCode(
    Request $request,
    VerificationService $verificationService
  ) {
    $verification = $verificationService->sendMobileUnbindVerifyCode(
      $request->user()->id
    );

    return $this->render('verifications.verification', [
      'verification'  => $verification,
    ]);
  }

  /**
   * Send verify code for user rebind his/her mobile.
   */
  public function sendMobileRebindVerifyCode(
    Request $request,
    VerificationService $verificationService
  ) {
    $this->validate($request, [
      'countryCode' => 'required|countrycode',
      'mobile'      => 'required|mobile',
    ]);

    $verification = $verificationService->sendMobileRebindVerifyCode(
      $request->user()->id,
      (int) $request->request->get('countryCode'),
      $request->request->get('mobile')
    );

    return $this->render('verifications.verification', [
      'verification'  => $verification,
    ]);
  }

  /**
   * Send verify code for user reset his/her password
   */
  public function sendPasswordResetVerifyCode(
    Request $request,
    VerificationService $verificationService
  ) {
    $this->validate($request, [
      'countryCode' => 'required|countrycode',
      'mobile'      => 'required|mobile',
    ]);

    $verification = $verificationService->sendPasswordResetVerifyCode(
      (int) $request->request->get('countryCode'),
      $request->request->get('mobile')
    );

    return $this->render('verifications.verification', [
      'verification'  => $verification,
    ]);
  }
}
