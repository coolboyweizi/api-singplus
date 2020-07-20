<?php

namespace FeatureTest\SingPlus\Controllers\Auth;

use Illuminate\Foundation\Testing\DatabaseTransactions;
use FeatureTest\SingPlus\TestCase;
use FeatureTest\SingPlus\MongodbClearTrait;

class RegisterControllerTest extends TestCase
{
  use MongodbClearTrait;

  //============================
  //        Register
  //============================
  public function testRegisterSuccess()
  {
    $verification = factory(\SingPlus\Domains\Verifications\Models\Verification::class)->create([
      'mobile'      => '2547200000001',
      'code'        => '1234',
      'expired_at'  => date('Y-m-d H:i:s', strtotime('+50 seconds')),
    ]);


    self::assertTrue(\Auth::guest());
    $response = $this->postJson('v3/passport/register', [
      'countryCode' => '254',
      'mobile'      => '07200000001',
      'password'    => '*******',
      'code'        => '1234',
    ]);

    $response->assertJson(['code' => 0]);
    $this->assertDatabaseHas('users', [
      'mobile'  => '2547200000001'
    ]);
    $response = json_decode($response->getContent());
    self::assertNotNull($response->data->userId);

    self::assertFalse(\Auth::guest());        // aready login

    // verify code aready deleted
    $this->assertDatabaseMissing('verifications', [
      'mobile'      => '2547200000001',
      'code'        => '1234',
      'deleted_at'  => null,      // soft delete
    ], 'mongodb');
  }

  public function testRegisterFailed_UserExists()
  {
    $verification = factory(\SingPlus\Domains\Verifications\Models\Verification::class)->create([
      'mobile'      => '2547200000001',
      'code'        => 1234,
      'expired_at'  => date('Y-m-d H:i:s', strtotime('+50 seconds')),
    ]);

    factory(\SingPlus\Domains\Users\Models\User::class)->create([
      'mobile'    => '2547200000001',
      'password'  => '$2y$10$IYcxJ287/u/hsapWmT/TEeHwpCk7CFoKYe99LgZMoL1mxTzVElQSW', // raw password is: '*******'
    ]);

    $response = $this->postJson('v3/passport/register', [
      'countryCode' => '254',
      'mobile'      => '07200000001',
      'password'    => '*******',
      'code'        => '1234',
    ]);

    $response->assertJson(['code' => 10102]);   // user aready exists
  }

  public function testRegisterFailed_CodeNotExists()
  {
    $response = $this->postJson('v3/passport/register', [
      'countryCode' => '254',
      'mobile'      => '7200000001',
      'password'    => '*******',
      'code'        => '1234',
    ]);

    $response->assertJson(['code' => 10002]);
    $this->assertDatabaseMissing('users', [
      'mobile'  => '2547200000001'
    ]);
  }

  public function testRegisterFailed_CodeExpired()
  {
    $verification = factory(\SingPlus\Domains\Verifications\Models\Verification::class)->create([
      'mobile'      => '2547200000001',
      'code'        => 1234,
      'expired_at'  => date('Y-m-d H:i:s', strtotime('-50 seconds')),
    ]);

    $response = $this->postJson('v3/passport/register', [
      'countryCode' => '254',
      'mobile'      => '7200000001',
      'password'    => '*******',
      'code'        => '1234',
    ]);

    $response->assertJson(['code' => 10002]);
    $this->assertDatabaseMissing('users', [
      'mobile'  => '2547200000001'
    ]);
  }

  public function testRegisterFailed_CodeNotMatch()
  {
    $verification = factory(\SingPlus\Domains\Verifications\Models\Verification::class)->create([
      'mobile'      => '2547200000001',
      'code'        => 1111,
      'expired_at'  => date('Y-m-d H:i:s', strtotime('+50 seconds')),
    ]);

    $response = $this->postJson('v3/passport/register', [
      'countryCode' => '254',
      'mobile'      => '7200000001',
      'password'    => '*******',
      'code'        => '1234',
    ]);

    $response->assertJson(['code' => 10002]);
    $this->assertDatabaseMissing('users', [
      'mobile'  => '2547200000001',
    ]);
  }
}
