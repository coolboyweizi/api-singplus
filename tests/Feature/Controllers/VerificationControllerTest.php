<?php

namespace FeatureTest\SingPlus\Controllers;

use Queue;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use FeatureTest\SingPlus\TestCase;
use FeatureTest\SingPlus\MongodbClearTrait;

class VerificationControllerTest extends TestCase
{
  use MongodbClearTrait; 

  //=================================
  //        sendRegisterVerifyCode
  //=================================
  public function testSendRegisterVerifyCodeSuccess()
  {
    Queue::fake();

    config(['sms.config.pretending' => true]);
    config([
      'verification'  => [
        'send_interval'       => 60,
        'limit_period'        => 0,
        'limit_count'         => 0,
        'total_limit_count'   => 0,
        'total_limit_period'  => 0,
      ],
    ]);
    $response = $this->postJson('v3/verification/register/captcha', [
            'countryCode' => '254',
            'mobile'      => '07200000001',
         ]);

    Queue::assertPushed(\SingPlus\SMS\SendQueuedMessage::class, function ($job) {
      return $job->message->getTo() == ['2547200000001'] &&
             is_null($job->transport);
    });

    $response->assertJson([
      'code'  => 0,
      'data'  => [
        'interval'  => 60,
      ],
    ]);

    $verification = \SingPlus\Domains\Verifications\Models\Verification::where('mobile', '2547200000001')->first();
    self::assertNotNull($verification);
    self::assertEquals(4, strlen($verification->code));
    $response = json_decode($response->getContent());
    $data = $response->data;
  }

  public function testSendRegisterVerifyCodeSuccess_SMSPretending()
  {
    config(['sms.config.pretending' => true]);
    config([
      'verification'  => [
        'send_interval'       => 60,
        'limit_period'        => 0,
        'limit_count'         => 0,
        'total_limit_count'   => 0,
        'total_limit_period'  => 0,
      ],
    ]);
    $response = $this->postJson('v3/verification/register/captcha', [
            'countryCode' => '254',
            'mobile'      => '07200000001',
         ]);

    $response->assertJson([
      'code'  => 0,
      'data'  => [
        'interval'  => 60,
      ],
    ]);

    $verification = \SingPlus\Domains\Verifications\Models\Verification::where('mobile', '2547200000001')->first();
    self::assertNotNull($verification);
    self::assertEquals(4, strlen($verification->code));
    $response = json_decode($response->getContent());
    $data = $response->data;
    self::assertEquals($verification->code, $data->code);
  }

  public function testSendRegisterVerifyCodeFailed_SendInterval()
  {
    config([
      'verification'  => [
        'send_interval'       => 60,
        'limit_period'        => 0,
        'limit_count'         => 0,
        'total_limit_count'   => 0,
        'total_limit_period'  => 0,
      ],
    ]);
    $verification = factory(\SingPlus\Domains\Verifications\Models\Verification::class)->create([
      'mobile'      => '2547200000001',
      'created_at'  => date('Y-m-d H:i:s', strtotime('-10 seconds')),
    ]);

    $response = $this->postJson('v3/verification/register/captcha', [
            'countryCode' => '254',
            'mobile'      => '7200000001',
         ]);
    $response->assertJson([
      'code'    => '10003',
      'message' => 'send frequence too many',
    ]);
  }

  public function testSendRegisterVerifyCodeFailed_MessageRateLimit()
  {
    config([
      'verification' => [
        'send_interval'       => 60,
        'limit_period'        => 86400,
        'limit_count'         => 2,
        'total_limit_count'   => 0,
        'total_limit_period'  => 0,
      ],
    ]);

    factory(\SingPlus\Domains\Verifications\Models\Verification::class)->create([
      'mobile'      => '2547200000001',
      'created_at'  => date('Y-m-d H:i:s', strtotime('-120 seconds')),
    ]);
    factory(\SingPlus\Domains\Verifications\Models\Verification::class)->create([
      'mobile'      => '2547200000001',
      'created_at'  => date('Y-m-d H:i:s', strtotime('-240 seconds')),
    ]);

    $response = $this->postJson('v3/verification/register/captcha', [
            'countryCode' => '254',
            'mobile'      => '7200000001',
         ]);

    $response->assertJson([
      'code'    => '10003',
      'message' => 'send verification code exceed max limitaion',
    ]);
  }

  public function testSendRegisterVerifyCodeFailed_MessageRateTotalLimit()
  {
    config([
      'verification'  => [
        'send_interval'       => 60,
        'limit_period'        => 0,
        'limit_count'         => 0,
        'total_limit_count'   => 2,
        'total_limit_period'  => 86400,
      ],
    ]);

    factory(\SingPlus\Domains\Verifications\Models\Verification::class)->create([
      'mobile'      => '2547200000001',
      'created_at'  => date('Y-m-d H:i:s', strtotime('-120 seconds')),
    ]);
    factory(\SingPlus\Domains\Verifications\Models\Verification::class)->create([
      'mobile'      => '2547200000002',
      'created_at'  => date('Y-m-d H:i:s', strtotime('-240 seconds')),
    ]);

    $response = $this->postJson('v3/verification/register/captcha', [
            'countryCode' => '254',
            'mobile'      => '7200000001',
         ]);

    $response->assertJson([
      'code'    => '10003',
      'message' => 'send verification code exceed max limitaion',
    ]);
  }

  public function testSendRegisterVerifyCodeSuccess_UserNotExists()
  {
    factory(\SingPlus\Domains\Users\Models\User::class)->create([
      'mobile'  => '2547200000001',
    ]);

    $response = $this->postJson('v3/verification/register/captcha', [
            'countryCode' => '254',
            'mobile'      => '7200000001',
         ]);

    $response->assertJson(['code' => 10102]);
  }

  //=================================
  //        sendMobileBindVerifyCode
  //=================================
  public function testSendMobileBindVerifyCodeSuccess()
  {
    config(['sms.config.pretending' => true]);
    config([
      'verification'  => [
        'send_interval'       => 60,
        'limit_period'        => 0,
        'limit_count'         => 0,
        'total_limit_count'   => 0,
        'total_limit_period'  => 0,
      ],
    ]);
    $user = factory(\SingPlus\Domains\Users\Models\User::class)->create([
      'country_code'  => null,
      'mobile'        => null,
    ]);
    $response = $this->actingAs($user)
                     ->postJson('v3/verification/mobile-bind/captcha', [
                        'countryCode' => '254',
                        'mobile'      => '07200000001',
                      ]);

    $response->assertJson([
      'code'  => 0,
      'data'  => [
        'interval'  => 60,
      ],
    ]);

    $verification = \SingPlus\Domains\Verifications\Models\Verification::where('mobile', '25407200000001')->first();
    self::assertNotNull($verification);
    self::assertEquals(4, strlen($verification->code));
    $response = json_decode($response->getContent());
    $data = $response->data;
    self::assertEquals($verification->code, $data->code);
  }

  public function testSendMobileBindVerifyCodeFailed_MobileAreadyBound()
  {
    config(['sms.config.pretending' => true]);
    config([
      'verification'  => [
        'send_interval'       => 60,
        'limit_period'        => 0,
        'limit_count'         => 0,
        'total_limit_count'   => 0,
        'total_limit_period'  => 0,
      ],
    ]);
    factory(\SingPlus\Domains\Users\Models\User::class)->create([
      'mobile'  => '2547200000001',
    ]);
    $user = factory(\SingPlus\Domains\Users\Models\User::class)->create([
      'country_code'  => null,
      'mobile'        => null,
    ]);
    $response = $this->actingAs($user)
                     ->postJson('v3/verification/mobile-bind/captcha', [
                        'countryCode' => '254',
                        'mobile'      => '7200000001',
                      ]);

    $response->assertJson([
      'code'    => 10105,
      'message' => '2547200000001 aready bound by someone else',
    ]);
  }

  public function testSendMobileBindVerifyCodeFailed_UserAreadyBound()
  {
    $user = factory(\SingPlus\Domains\Users\Models\User::class)->create([
      'country_code'  => 254,
      'mobile'        => '2547200000001',
    ]);

    $response = $this->actingAs($user)
                     ->postJson('v3/verification/mobile-bind/captcha', [
                        'countryCode' => '254',
                        'mobile'      => '7200000001',
                      ]);
    $response->assertJson(['code' => 10105]);
  }

  //=====================================
  //        sendMobileUnbindVerifyCode
  //=====================================
  public function testSendMobileUnbindVerifyCodeSuccess()
  {
    config(['sms.config.pretending' => true]);
    config([
      'verification'  => [
        'send_interval'       => 60,
        'limit_period'        => 0,
        'limit_count'         => 0,
        'total_limit_count'   => 0,
        'total_limit_period'  => 0,
      ],
    ]);
    $user = factory(\SingPlus\Domains\Users\Models\User::class)->create([
      'country_code'  => 254,
      'mobile'        => '25407200000001',
    ]);
    $response = $this->actingAs($user)
                     ->postJson('v3/verification/mobile-unbind/captcha');

    $response->assertJson(['code' => 0]);    
  }

  public function testSendMobileUnbindVerifyCodeFailed_UserNotBound()
  {
    $user = factory(\SingPlus\Domains\Users\Models\User::class)->create([
      'country_code'  => null,
      'mobile'        => null,
    ]);
    $response = $this->actingAs($user)
                     ->postJson('v3/verification/mobile-unbind/captcha');
    $response->assertJson(['code' => 10106]);
  }

  //=====================================
  //        sendMobileRebindVerifyCode
  //=====================================
  public function testSendMobileRebindVerifyCodeSuccess()
  {
    config(['sms.config.pretending' => true]);
    config([
      'verification'  => [
        'send_interval'       => 60,
        'limit_period'        => 0,
        'limit_count'         => 0,
        'total_limit_count'   => 0,
        'total_limit_period'  => 0,
      ],
    ]);
    $user = factory(\SingPlus\Domains\Users\Models\User::class)->create([
      'country_code'  => 254,
      'mobile'        => '25407200000001',
    ]);
    $response = $this->actingAs($user)
                     ->postJson('v3/verification/mobile-rebind/captcha', [
                        'countryCode' => 254,
                        'mobile'      => '7200000002',
                     ]);

    $response->assertJson(['code' => 0]);    
  }

  public function testSendMobileRebindVerifyCodeFailed_UserNotBound()
  {
    $user = factory(\SingPlus\Domains\Users\Models\User::class)->create([
      'country_code'  => null,
      'mobile'        => null,
    ]);
    $response = $this->actingAs($user)
                     ->postJson('v3/verification/mobile-rebind/captcha', [
                        'countryCode' => 254, 
                        'mobile'      => '7200000002',
                     ]);
    $response->assertJson(['code' => 10106]);
  }

  public function testSendMobileRebindVerifyCodeFailed_NewMobileAreadyBound()
  {
    factory(\SingPlus\Domains\Users\Models\User::class)->create([
      'country_code'  => 254,
      'mobile'        => '2547200000002',
    ]);
    $user = factory(\SingPlus\Domains\Users\Models\User::class)->create([
      'country_code'  => 254,
      'mobile'        => '2547200000001',
    ]);
    $response = $this->actingAs($user)
                     ->postJson('v3/verification/mobile-rebind/captcha', [
                        'countryCode' => 254, 
                        'mobile'      => '7200000002',
                     ]);
    $response->assertJson(['code' => 10105]);
  }

  public function testSendMobileRebindVerifyCodeFailed_ReboundExistsMobile()
  {
    $user = factory(\SingPlus\Domains\Users\Models\User::class)->create([
      'country_code'  => 254,
      'mobile'        => '2547200000001',
    ]);
    $response = $this->actingAs($user)
                     ->postJson('v3/verification/mobile-rebind/captcha', [
                        'countryCode' => 254, 
                        'mobile'      => '7200000001',
                     ]);
    $response->assertJson(['code' => 10000]);
  }

  //========================================
  //        sendPasswordResetVerifyCode
  //========================================
  public function testSendPasswordResetVerifyCodeSuccess()
  {
    config(['sms.config.pretending' => true]);
    config([
      'verification'  => [
        'send_interval'       => 60,
        'limit_period'        => 0,
        'limit_count'         => 0,
        'total_limit_count'   => 0,
        'total_limit_period'  => 0,
      ],
    ]);
    $user = factory(\SingPlus\Domains\Users\Models\User::class)->create([
      'country_code'  => 254,
      'mobile'        => '25407200000001',
    ]);

    $response = $this->postJson('v3/verification/password-reset/captcha', [
                          'countryCode' => 254,
                          'mobile'      => '07200000001',
                        ]);
    $response->assertJson([
      'code'  => 0,
      'data'  => [
        'interval'  => 60,
      ],
    ]);

    $verification = \SingPlus\Domains\Verifications\Models\Verification::where(
                      'mobile', '25407200000001')->first();
    self::assertNotNull($verification);
    self::assertEquals(4, strlen($verification->code));
    $response = json_decode($response->getContent());
    $data = $response->data;
  }

  public function testSendPasswordResetVerifyCodeFailed_UserNotExists()
  {
    $response = $this->postJson('v3/verification/password-reset/captcha', [
                          'countryCode' => 254,
                          'mobile'      => '7200000001',
                        ]);
    $response->assertJson(['code' => 10103]);
  }
}
