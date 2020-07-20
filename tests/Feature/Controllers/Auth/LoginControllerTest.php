<?php

namespace FeatureTest\SingPlus\Controllers\Auth;

use Illuminate\Foundation\Testing\DatabaseTransactions;
use FeatureTest\SingPlus\TestCase;
use FeatureTest\SingPlus\MongodbClearTrait;

class LoginControllerTest extends TestCase
{
  use MongodbClearTrait; 

  //=====================
  //      login
  //=====================
  public function testLoginSuccess()
  {
    $user = factory(\SingPlus\Domains\Users\Models\User::class)->create([
      'mobile'    => '720000000',
      'password'  => '$2y$10$IYcxJ287/u/hsapWmT/TEeHwpCk7CFoKYe99LgZMoL1mxTzVElQSW', // raw password is: '*******'
    ]);
    $response = $this->postJson('v3/passport/login', [
      'countryCode' => '072',
      'mobile'      => '0000000', 
      'password'    => '*******',
    ]);
    $response->assertJson([
      'code' => 0,
      'data'  => [
        'userId'    => $user->id,
        'isNewUser' => true,
      ],
    ]);
  }

  public function testLoginSuccess_NotNewUser()
  {
    $user = factory(\SingPlus\Domains\Users\Models\User::class)->create([
      'mobile'    => '720000000',
      'password'  => '$2y$10$IYcxJ287/u/hsapWmT/TEeHwpCk7CFoKYe99LgZMoL1mxTzVElQSW', // raw password is: '*******'
    ]);
    factory(\SingPlus\Domains\Users\Models\UserProfile::class)->create([
      'user_id' => $user->id,
      'is_new'  => false,
    ]);
    $response = $this->postJson('v3/passport/login', [
      'countryCode' => '072',
      'mobile'      => '0000000', 
      'password'    => '*******',
    ]);
    $response->assertJson([
      'code' => 0,
      'data'  => [
        'userId'    => $user->id,
        'isNewUser' => false,
      ],
    ]);
  }

  public function testLoginFailed_PasswordNotMatch()
  {
    factory(\SingPlus\Domains\Users\Models\User::class)->create([
      'mobile'    => '720000000',
      'password'  => '$2y$10$IYcxJ287/u/hsapWmT/TEeHwpCk7CFoKYe99LgZMoL1mxTzVElQSW', // raw password is: '*******'
    ]);
    $response = $this->postJson('v3/passport/login', [
      'countryCode' => '072',
      'mobile'      => '0000000', 
      'password'    => '*******1',
    ]);
    $response->assertJson(['code' => 10130]);
  }

  public function testLoginFailed_UserNotExists()
  {
    $response = $this->postJson('v3/passport/login', [
      'countryCode' => '072',
      'mobile'      => '0000000', 
      'password'    => '*******1',
    ]);

    $response->assertJson(['code' => 10103]);   // user not exists
  }

  //=====================
  //      logout
  //=====================
  public function testLogoutSuccess()
  {
    $user = factory(\SingPlus\Domains\Users\Models\User::class)->create([
      'mobile'          => '0720000000',
      'remember_token'  => 'FAxm3Uk2awKO1MlRqD7OxKmYUdstEIUNkp4OqjHxzKDBtCgC2ZSw1KEF3jxN'
    ]);

    $response = $this->actingAs($user)
                     ->getJson('v3/passport/logout');

    $this->assertDatabaseMissing('users', [
      'id'              => $user->id,
      'remember_token'  => 'FAxm3Uk2awKO1MlRqD7OxKmYUdstEIUNkp4OqjHxzKDBtCgC2ZSw1KEF3jxN'
    ]);   // remember_token should be changed after logout

    $this->assertDatabaseHas('users', [
      '_id'  => $user->id,
    ]);   // user still exists
  }

  public function testLogoutSuccess_WithNotLogin()
  {
    $this->getJson('v3/passport/logout')->assertJson(['code' => 0]);
  }
}
