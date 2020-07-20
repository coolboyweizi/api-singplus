<?php

namespace FeatureTest\SingPlus\Controllers\Api;

use Mockery;
use Cache;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use FeatureTest\SingPlus\TestCase;
use FeatureTest\SingPlus\MongodbClearTrait;

class UserControllerTest extends TestCase
{
  use MongodbClearTrait; 

  //=================================
  //    createSyntheticUser
  //=================================
  public function testCreateSyntheticUserSuccess()
  {
    $this->expectsEvents(\SingPlus\Events\UserImageUploaded::class);

    $counterMock = Mockery::mock(\Illuminate\Contracts\Cache\Store::class);
    $counterMock->shouldReceive('increment')
                ->once()
                ->with('user_images', 100)
                ->andReturn(100);
    Cache::shouldReceive('driver')
         ->once()
         ->with('counter')
         ->andReturn($counterMock);

    $storageService = $this->mockStorage();
    $storageService->shouldReceive('store')
                   ->once()
                   ->with(sprintf('%s/test_datas/tesla_logo.jpg', __DIR__), Mockery::on(function (array $options) {
                      return isset($options['prefix']);   // prefix should be set
                   }))
                   ->andReturn('images/0305b27e047a11e7ac640800276e6868');
    $storageService->shouldReceive('toHttpUrl')
                   ->once()
                   ->with('images/0305b27e047a11e7ac640800276e6868')
                   ->andReturn('http://image.sing.plus/images/0305b27e047a11e7ac640800276e6868');

    $response = $this->postJson('api/users/synthetic-users', [
      'countryCode' => '254',
      'mobile'      => '13800138000',
      'password'    => '*******',
      'nickname'    => 'zhangsan',
      'avatar'      => $this->makeUploadFile(sprintf('%s/test_datas/tesla_logo.jpg', __DIR__)),
    ]);
    $response->assertJson(['code' => 0]);
    $userId = (json_decode($response->getContent()))->data->userId;

    self::assertDatabaseHas('users', [
      '_id'           => $userId, 
      'country_code'  => '254',
      'mobile'        => '25413800138000',
      'source'        => 'synthetic',
    ]);
    self::assertDatabaseHas('user_profiles', [
      'user_id'     => $userId,
      'nickname'    => 'zhangsan',
      'is_new'      => false,
      'avatar'      => 'images/0305b27e047a11e7ac640800276e6868',
    ]);
    self::assertDatabaseHas('user_images', [
      'user_id'       => $userId,
      'uri'           => 'images/0305b27e047a11e7ac640800276e6868',
      'display_order' => 100,
    ]);
  }

  public function testCreateSyntheticUserFailed_NotComplementation()
  {
    $user = factory(\SingPlus\Domains\Users\Models\User::class)->create([
      'country_code'  => '254',
      'mobile'        => '25413800138000',
      'source'        => 'synthetic',
    ]);

    $response = $this->postJson('api/users/synthetic-users', [
      'countryCode' => '254',
      'mobile'      => '13800138000',
      'password'    => '*******',
      'nickname'    => 'zhangsan',
      'avatar'      => $this->makeUploadFile(sprintf('%s/test_datas/tesla_logo.jpg', __DIR__)),
    ]);

    $response->assertJson(['code' => 10102]);
  }

  public function testCreateSyntheticUserFailed_NicknameAreadyUsed()
  {
    $user = factory(\SingPlus\Domains\Users\Models\UserProfile::class)->create([
      'nickname'  => 'zhangsan',
    ]);

    $response = $this->postJson('api/users/synthetic-users', [
      'countryCode' => '254',
      'mobile'      => '13800138000',
      'password'    => '*******',
      'nickname'    => 'zhangsan',
      'avatar'      => $this->makeUploadFile(sprintf('%s/test_datas/tesla_logo.jpg', __DIR__)),
    ]);

    $response->assertJson(['code' => 10113]);
  }

  private function mockStorage()
  {
    $storageService = Mockery::mock(\SingPlus\Contracts\Storages\Services\StorageService::class);
    $this->app[\SingPlus\Contracts\Storages\Services\StorageService::class ] = $storageService;

    return $storageService;
  }
}
