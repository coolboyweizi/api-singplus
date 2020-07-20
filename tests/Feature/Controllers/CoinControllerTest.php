<?php

namespace FeatureTest\SingPlus\Controllers;

use Cache;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use FeatureTest\SingPlus\TestCase;
use FeatureTest\SingPlus\MongodbClearTrait;
use Mockery;

class CoinControllerTest extends TestCase
{
  use MongodbClearTrait; 

  //=================================
  //        getBill
  //=================================
  public function testGetBillSucess()
  {
    $user = factory(\SingPlus\Domains\Users\Models\User::class)->create();
    $data = $this->prepareData($user->id); 

    $response = $this->actingAs($user)
                     ->getJson('v3/pay/bill/list')
                     ->assertJson(['code' => 0]);

    $bills = (json_decode($response->getContent()))->data->bills;
    self::assertCount(4, $bills);
    self::assertEquals($data->trans->four->id, $bills[0]->billId);
    self::assertEquals(-400, $bills[0]->value);
    self::assertEquals('Send gifts', $bills[0]->name);
    self::assertTrue(is_int($bills[0]->time));
    self::assertEquals($data->trans->one->id, $bills[3]->billId);
    self::assertEquals('Purchase coins', $bills[3]->name);
  }

  public function testGetBillSucess_Pagination()
  {
    $user = factory(\SingPlus\Domains\Users\Models\User::class)->create();
    $data = $this->prepareData($user->id); 

    $response = $this->actingAs($user)
                     ->getJson('v3/pay/bill/list?' . http_build_query([
                        'billId'    => $data->trans->four->id, 
                        'size'      => 2,
                     ]))
                     ->assertJson(['code' => 0]);
    $bills = (json_decode($response->getContent()))->data->bills;
    self::assertCount(2, $bills);
    self::assertEquals($data->trans->three->id, $bills[0]->billId);
    self::assertEquals(300, $bills[0]->value);
    self::assertEquals('Sent by Admin', $bills[0]->name);
    self::assertTrue(is_int($bills[0]->time));
    self::assertEquals($data->trans->two->id, $bills[1]->billId);
    self::assertEquals('Daily tasks', $bills[1]->name);
  }

  private function prepareData($userId)
  {
    $tranOne = factory(\SingPlus\Domains\Coins\Models\CoinTransaction::class)->create([
        'user_id'   => $userId,
        'operator'  => '0240f9d318d34eae817b02e2d4aa7725',
        'amount'    => 100,
        'source'    => 1,
        'details'   => [
            'order_id'  => 'aa1e783a9e014f00b31223378091fa2e',
        ],
        'display_order' => 100,
    ]);
    $tranTwo = factory(\SingPlus\Domains\Coins\Models\CoinTransaction::class)->create([
        'user_id'   => $userId,
        'operator'  => '0240f9d318d34eae817b02e2d4aa7725',
        'amount'    => 200,
        'source'    => 51,
        'details'   => [
            'order_id'  => 'aa1e783a9e014f00b31223378091fa2e',
        ],
        'display_order' => 200,
    ]);
    $tranThree = factory(\SingPlus\Domains\Coins\Models\CoinTransaction::class)->create([
        'user_id'   => $userId,
        'operator'  => '0240f9d318d34eae817b02e2d4aa7725',
        'amount'    => 300,
        'source'    => 101,
        'details'   => [
            'order_id'  => 'aa1e783a9e014f00b31223378091fa2e',
        ],
        'display_order' => 300,
    ]);
    $tranFour = factory(\SingPlus\Domains\Coins\Models\CoinTransaction::class)->create([
        'user_id'   => $userId,
        'operator'  => '0240f9d318d34eae817b02e2d4aa7725',
        'amount'    => -400,
        'source'    => 1001,
        'details'   => [
            'order_id'  => 'aa1e783a9e014f00b31223378091fa2e',
        ],
        'display_order' => 400,
    ]);
    $tranFive = factory(\SingPlus\Domains\Coins\Models\CoinTransaction::class)->create([
        'user_id'   => '0240f9d318d34eae817b02e2d4aa7725',
        'operator'  => '0240f9d318d34eae817b02e2d4aa7725',
        'amount'    => 100,
        'source'    => 1,
        'details'   => [
            'order_id'  => 'aa1e783a9e014f00b31223378091fa2e',
        ],
        'display_order' => 100,
    ]);

    return (object) [
        'trans' => (object) [
            'one'   => $tranOne,
            'two'   => $tranTwo,
            'three' => $tranThree,
            'four'  => $tranFour,
            'five'  => $tranFive,
        ],
    ];
  }
}
