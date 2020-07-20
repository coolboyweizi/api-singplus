<?php

namespace FeatureTest\SingPlus\Controllers\Api;

use Bus;
use Illuminate\Support\Facades\Queue;
use Mockery;
use Cache;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use FeatureTest\SingPlus\TestCase;
use FeatureTest\SingPlus\MongodbClearTrait;
use SingPlus\Jobs\IMSendTopicMsg;

class CoinControllerTest extends TestCase
{
  use MongodbClearTrait; 

  //=================================
  //        makeTrans
  //=================================
  public function  testMakeTransSuccess()
  {
    $user = factory(\SingPlus\Domains\Users\Models\User::class)->create();
    $profile = factory(\SingPlus\Domains\Users\Models\UserProfile::class)->create([
        'user_id'   => $user->id, 
    ]);

    $counterMock = Mockery::mock(\Illuminate\Contracts\Cache\Store::class);
    $counterMock->shouldReceive('increment')
                ->once()
                ->with('cointrans', 100)
                ->andReturn(100);
    Cache::shouldReceive('driver')
         ->once()
         ->with('counter')
         ->andReturn($counterMock);

    $response = $this->postJson('api/coins/trans', [
                        'taskId'    => '592a5238a2634d148010baf4d3831264',
                        'userId'    => $user->id,
                        'operator'  => 'd35b02af996e4db780025cb35a7333f6',
                        'amount'    => -123,
                        'type'      => 101,
                        'details'   => [
                            'order_id'  => '7cfe93e7f3414185bf5cf951227f634a',
                        ],
                     ])
                     ->assertJson(['code' => 0]);

    self::assertDatabaseHas('coin_transactions', [
        'user_id'       => $user->id,
        'operator'      => 'd35b02af996e4db780025cb35a7333f6',
        'amount'        => 123,
        'source'        => 101,
        'display_order' => 100,
    ]);

    $profile = $profile->fresh();
    self::assertEquals(123, $profile->coins['balance']);

    $trans = \SingPlus\Domains\Coins\Models\CoinTransaction::where('details.taskId', '592a5238a2634d148010baf4d3831264')->first();
    self::assertNotNull($trans);
    self::assertEquals('7cfe93e7f3414185bf5cf951227f634a', $trans->details['order_id']);
  }


  public function testMakeTransSuccess_TaskIdExists()
  {
    $user = factory(\SingPlus\Domains\Users\Models\User::class)->create();
    $profile = factory(\SingPlus\Domains\Users\Models\UserProfile::class)->create([
        'user_id'   => $user->id, 
        'coins' => [
            'balance'   => 100,
        ],
    ]);
    $trans = factory(\SingPlus\Domains\Coins\Models\CoinTransaction::class)->create([
        'details'   => [
            'taskId'    => '592a5238a2634d148010baf4d3831264',
        ],
    ]);

    Cache::shouldReceive('driver')
         ->never();

    $response = $this->postJson('api/coins/trans', [
                        'taskId'    => '592a5238a2634d148010baf4d3831264',
                        'userId'    => $user->id,
                        'operator'  => 'd35b02af996e4db780025cb35a7333f6',
                        'amount'    => 100,
                        'type'      => 101,
                        'details'   => [
                            'order_id'  => '7cfe93e7f3414185bf5cf951227f634a',
                        ],
                     ])
                     ->assertJson(['code' => 0]);
  }


  public function testMakeTransFailed_TypeInvalid()
  {
    $user = factory(\SingPlus\Domains\Users\Models\User::class)->create();
    $profile = factory(\SingPlus\Domains\Users\Models\UserProfile::class)->create([
        'user_id'   => $user->id, 
        'coins' => [
            'balance'   => 100,
        ],
    ]);

    Cache::shouldReceive('driver')
         ->never();

    $response = $this->postJson('api/coins/trans', [
                        'taskId'    => '592a5238a2634d148010baf4d3831264',
                        'userId'    => $user->id,
                        'operator'  => 'd35b02af996e4db780025cb35a7333f6',
                        'amount'    => 100,
                        'type'      => 1001,
                        'details'   => [
                            'order_id'  => '7cfe93e7f3414185bf5cf951227f634a',
                        ],
                     ])
                     ->assertJson(['code' => 10001]);
  }
}
