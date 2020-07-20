<?php

namespace FeatureTest\SingPlus\Controllers;

use Cache;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use FeatureTest\SingPlus\TestCase;
use FeatureTest\SingPlus\MongodbClearTrait;
use Mockery;

class ChargeOrderControllerTest extends TestCase
{
  use MongodbClearTrait; 

  //=================================
  //        createOrder
  //=================================
  public function testCreateOrderSuccess()
  {
    $user = factory(\SingPlus\Domains\Users\Models\User::class)->create();
    $sku = factory(\SingPlus\Domains\Orders\Models\CoinSku::class)->create([
        'coins'     => 100,
        'title'     => '100金币',
        'price'     => 100000000,
        'status'    => 1,
    ]);

    $response = $this->actingAs($user)
                     ->postJson('v3/pay/order/create', [
                        'productId' => $sku->sku_id,
                        'priceAmountMicros' => '600000000',
                        'priceCurrencyCode' => 'CHY',
                     ])
                     ->assertJson(['code' => 0]);
    $response = json_decode($response->getContent());

    self::assertDatabaseHas('charge_orders', [
        '_id'           => $response->data->developerPayload,
        'user_id'       => $user->id,
        'pay_order_id'  => null,
        'amount'        => 100000000,
        'sku_count'     => 1,
        'pay_order_details' => [
            'currency_amount'   => 600000000,   
            'currency_code'     => 'CHY',
        ],
        'status'        => 1,
        'sku'           => [
            'sku_id'    => $sku->sku_id,
            'price'     => 100000000,
            'coins'     => 100,
            'title'     => '100金币',
        ],
    ]);

    $order = \SingPlus\Domains\Orders\Models\ChargeOrder::find($response->data->developerPayload);
    self::assertCount(1, $order->status_histories);
    self::assertEquals(1, $order->status_histories[0]['status']);
  }

  public function testCreateOrderFailed_SkuNotExists()
  {
    $user = factory(\SingPlus\Domains\Users\Models\User::class)->create();

    $response = $this->actingAs($user)
                     ->postJson('v3/pay/order/create', [
                        'productId' => 'aaaaaaaaaaaaaaaa',
                        'priceAmountMicros' => '600000000',
                        'priceCurrencyCode' => 'CHY',
                     ])
                     ->assertJson(['code' => 10801]);
  }

  //=================================
  //        validateOrder
  //=================================
  public function testValidateOrderSuccess()
  {
    $user = factory(\SingPlus\Domains\Users\Models\User::class)->create();
    $profile = factory(\SingPlus\Domains\Users\Models\UserProfile::class)->create([
        'user_id'   => $user->id,
    ]);
    $orders = $this->prepareChargeOrder($user->id);

    $purchaseMock = Mockery::mock(\stdClass::class);
    $purchaseMock->shouldReceive('get')
                 ->once()
                 ->with(
                    '2.com.example.app',
                    '2exampleSku',
                    '2opaque-token-up-to-1000-characters'
                 )
                 ->andReturn(new \Google_Service_AndroidPublisher_ProductPurchase([
                     'consumptionState' => 0,
                     'developerPayload' => $orders->orders->waiting->id,
                     'kind' => 'xxx',
                     'orderId'  => 'GPA.1234-5678-9012-22222',
                     'purchaseState'    => 0,
                     'purchaseTimeMillis'   => 1345678900002,
                     'purchaseType' => 0,
                 ]));
    app()->make('google.service')
         ->service('AndroidPublisher')
         ->purchases_products = $purchaseMock;

    $counterMock = Mockery::mock(\Illuminate\Contracts\Cache\Store::class);
    $counterMock->shouldReceive('increment')
        ->once()
        ->with('cointrans', 100)
        ->andReturn(100);
    Cache::shouldReceive('driver')
        ->once()
        ->with('counter')
        ->andReturn($counterMock);

    $response = $this->actingAs($user)
                     ->postJson('v3/pay/order/validate', [
                        // order not exists
                        [
                            'orderId'       => 'GPA.1234-5678-9012-11111',
                            'packageName'   => '1.com.example.app',
                            'productId'     => '1exampleSku',
                            'purchaseTime'  => 1345678900000,
                            'purchaseState' => 0,
                            'developerPayload'  => '6165840553ce4ddd8bc8577a0d8a6514',
                            'purchaseToken' => '1opaque-token-up-to-1000-characters',
                        ],
                        // waiting order
                        [
                            'orderId'       => 'GPA.1234-5678-9012-22222',
                            'packageName'   => '2.com.example.app',
                            'productId'     => '2exampleSku',
                            'purchaseTime'  => 1345678900000,
                            'purchaseState' => 0,
                            'developerPayload'  => $orders->orders->waiting->id,
                            'purchaseToken' => '2opaque-token-up-to-1000-characters',
                        ],
                        // paid order
                        [
                            'orderId'       => 'GPA.1234-5678-9012-33333',
                            'packageName'   => '3.com.example.app',
                            'productId'     => '3exampleSku',
                            'purchaseTime'  => 1345678900000,
                            'purchaseState' => 0,
                            'developerPayload'  => $orders->orders->paid->id,
                            'purchaseToken' => '3opaque-token-up-to-1000-characters',
                        ],
                        // closed order
                        [
                            'orderId'       => 'GPA.1234-5678-9012-44444',
                            'packageName'   => '4.com.example.app',
                            'productId'     => '4exampleSku',
                            'purchaseTime'  => 1345678900000,
                            'purchaseState' => 0,
                            'developerPayload'  => $orders->orders->closed->id,
                            'purchaseToken' => '4opaque-token-up-to-1000-characters',
                        ],
                     ])
                     ->assertJson(['code' => 0]);
    $data = (json_decode($response->getContent()))->data;
    self::assertEquals(102, $data->currentGold);
    self::assertCount(3, $data->products);
    self::assertEquals('2exampleSku', $data->products[0]->productId);
    self::assertEquals(0, $data->products[0]->status);
    self::assertEquals(102, $data->products[0]->gainGold);
    self::assertEquals('3exampleSku', $data->products[1]->productId);
    self::assertEquals(0, $data->products[1]->status);
    self::assertNull($data->products[1]->gainGold);
    self::assertEquals('4exampleSku', $data->products[2]->productId);
    self::assertEquals(2, $data->products[2]->status);
    self::assertNull($data->products[2]->gainGold);

    $chargeOrder = \SingPlus\Domains\Orders\Models\ChargeOrder::find($orders->orders->waiting->id);
    self::assertEquals('GPA.1234-5678-9012-22222', $chargeOrder->pay_order_id);
    self::assertEquals(2, $chargeOrder->status);        // waiting to paid
    self::assertCount(2, $chargeOrder->status_histories);
    self::assertEquals(2, $chargeOrder->status_histories[1]['status']);
    self::assertEquals(0, $chargeOrder->pay_order_details['originalPayInfo']['purchaseState']);

    $profile = \SingPlus\Domains\Users\Models\UserProfile::where('user_id', $user->id)->first();
    self::assertEquals(102, $profile->coins['balance']);
    $trans = \SingPlus\Domains\Coins\Models\CoinTransaction::all()->toArray();

    self::assertDatabaseHas('coin_transactions', [
        'user_id'   => $user->id,
        'operator'  => $user->id,
        'amount'    => 102,
        'source'    => 1,
        'details'   => [
            'order_id'  => $orders->orders->waiting->id,
        ],
    ]);
  }

  public function testValidateOrderSuccess_OrderCancelled()
  {
    $user = factory(\SingPlus\Domains\Users\Models\User::class)->create();
    $orders = $this->prepareChargeOrder($user->id);

    $purchaseMock = Mockery::mock(\stdClass::class);
    $purchaseMock->shouldReceive('get')
                 ->once()
                 ->with(
                    '2.com.example.app',
                    '2exampleSku',
                    '2opaque-token-up-to-1000-characters'
                 )
                 ->andReturn(new \Google_Service_AndroidPublisher_ProductPurchase([
                     'consumptionState' => 0,
                     'developerPayload' => $orders->orders->waiting->id,
                     'kind' => 'xxx',
                     'orderId'  => 'GPA.1234-5678-9012-22222',
                     'purchaseState'    => 1,       // cancelled
                     'purchaseTimeMillis'   => 1345678900002,
                     'purchaseType' => 0,
                 ]));
    app()->make('google.service')
         ->service('AndroidPublisher')
         ->purchases_products = $purchaseMock;

    $response = $this->actingAs($user)
                     ->postJson('v3/pay/order/validate', [
                        // waiting order
                        [
                            'orderId'       => 'GPA.1234-5678-9012-22222',
                            'packageName'   => '2.com.example.app',
                            'productId'     => '2exampleSku',
                            'purchaseTime'  => 1345678900000,
                            'purchaseState' => 0,
                            'developerPayload'  => $orders->orders->waiting->id,
                            'purchaseToken' => '2opaque-token-up-to-1000-characters',
                        ],
                     ])
                     ->assertJson(['code' => 0]);

    $data = (json_decode($response->getContent()))->data;
    self::assertEquals(0, $data->currentGold);
    self::assertCount(1, $data->products);
    self::assertEquals('2exampleSku', $data->products[0]->productId);
    self::assertEquals(2, $data->products[0]->status);
    self::assertNull($data->products[0]->gainGold);

    $chargeOrder = \SingPlus\Domains\Orders\Models\ChargeOrder::find($orders->orders->waiting->id);
    self::assertEquals('GPA.1234-5678-9012-22222', $chargeOrder->pay_order_id);
    self::assertEquals(3, $chargeOrder->status);        // waiting to paid
    self::assertCount(2, $chargeOrder->status_histories);
    self::assertEquals(3, $chargeOrder->status_histories[1]['status']);
    self::assertEquals(1, $chargeOrder->pay_order_details['originalPayInfo']['purchaseState']);
  }

  public function testValidateOrderSuccess_ThrowGoogleException()
  {
    $user = factory(\SingPlus\Domains\Users\Models\User::class)->create();
    $orders = $this->prepareChargeOrder($user->id);

    $purchaseMock = Mockery::mock(\stdClass::class);
    $purchaseMock->shouldReceive('get')
                 ->once()
                 ->with(
                    '2.com.example.app',
                    '2exampleSku',
                    '2opaque-token-up-to-1000-characters'
                 )
                 ->andThrow(\Google_Service_Exception::class, 'hhhhhhhh');
    app()->make('google.service')
         ->service('AndroidPublisher')
         ->purchases_products = $purchaseMock;

    $response = $this->actingAs($user)
                     ->postJson('v3/pay/order/validate', [
                        // waiting order
                        [
                            'orderId'       => 'GPA.1234-5678-9012-22222',
                            'packageName'   => '2.com.example.app',
                            'productId'     => '2exampleSku',
                            'purchaseTime'  => 1345678900000,
                            'purchaseState' => 0,
                            'developerPayload'  => $orders->orders->waiting->id,
                            'purchaseToken' => '2opaque-token-up-to-1000-characters',
                        ],
                     ])
                     ->assertJson(['code' => 0]);

    $data = (json_decode($response->getContent()))->data;
    self::assertEquals(0, $data->currentGold);
    self::assertCount(1, $data->products);
    self::assertEquals('2exampleSku', $data->products[0]->productId);
    self::assertEquals(2, $data->products[0]->status);
    self::assertNull($data->products[0]->gainGold);

    $chargeOrder = \SingPlus\Domains\Orders\Models\ChargeOrder::find($orders->orders->waiting->id);
    self::assertNull($chargeOrder->pay_order_id);
    self::assertEquals(3, $chargeOrder->status);        // waiting to paid
    self::assertCount(2, $chargeOrder->status_histories);
    self::assertEquals(3, $chargeOrder->status_histories[1]['status']);
    self::assertEquals('hhhhhhhh', $chargeOrder->pay_order_details['originalPayInfo']);
  }

  public function testValidateOrderSuccess_ThrowOtherException()
  {
    $user = factory(\SingPlus\Domains\Users\Models\User::class)->create();
    $orders = $this->prepareChargeOrder($user->id);

    $purchaseMock = Mockery::mock(\stdClass::class);
    $purchaseMock->shouldReceive('get')
                 ->once()
                 ->with(
                    '2.com.example.app',
                    '2exampleSku',
                    '2opaque-token-up-to-1000-characters'
                 )
                 ->andThrow(\Exception::class, 'hhhhhhhh');
    app()->make('google.service')
         ->service('AndroidPublisher')
         ->purchases_products = $purchaseMock;

    $response = $this->actingAs($user)
                     ->postJson('v3/pay/order/validate', [
                        // waiting order
                        [
                            'orderId'       => 'GPA.1234-5678-9012-22222',
                            'packageName'   => '2.com.example.app',
                            'productId'     => '2exampleSku',
                            'purchaseTime'  => 1345678900000,
                            'purchaseState' => 0,
                            'developerPayload'  => $orders->orders->waiting->id,
                            'purchaseToken' => '2opaque-token-up-to-1000-characters',
                        ],
                     ])
                     ->assertJson(['code' => 0]);

    $data = (json_decode($response->getContent()))->data;
    self::assertEquals(0, $data->currentGold);
    self::assertCount(1, $data->products);
    self::assertEquals('2exampleSku', $data->products[0]->productId);
    self::assertEquals(1, $data->products[0]->status);
    self::assertNull($data->products[0]->gainGold);
  }

  //=================================
  //        getSkus
  //=================================
  public function testGetSkusSuccess()
  {
    $skuOne = factory(\SingPlus\Domains\Orders\Models\CoinSku::class)->create([
        'coins'     => 100,
        'title'     => '100金币',
        'price'     => 100000000,
        'status'    => 1,
    ]);
    $skuTwo = factory(\SingPlus\Domains\Orders\Models\CoinSku::class)->create([
        'coins'     => 200,
        'title'     => '200金币',
        'price'     => 200000000,
        'status'    => 1,
    ]);
    $skuThree = factory(\SingPlus\Domains\Orders\Models\CoinSku::class)->create([
        'coins'     => 300,
        'title'     => '300金币',
        'price'     => 300000000,
        'status'    => 0,
    ]);
    $skuFour = factory(\SingPlus\Domains\Orders\Models\CoinSku::class)->create([
        'coins'     => 400,
        'title'     => '400金币',
        'price'     => 400000000,
        'status'    => -1,
    ]);

    $response = $this->getJson('v3/pay/product/list')
                     ->assertJson(['code' => 0]);
    $skus = (json_decode($response->getContent()))->data->products;
    self::assertCount(2, $skus);
    self::assertTrue(in_array($skus[0]->worth, [100, 200]));
    self::assertTrue(in_array($skus[0]->productId, [$skuOne->sku_id, $skuTwo->sku_id]));
    self::assertTrue(in_array($skus[1]->worth, [100, 200]));
    self::assertTrue(in_array($skus[1]->productId, [$skuOne->sku_id, $skuTwo->sku_id]));
  }

  private function prepareChargeOrder($userId)
  {
    $orderWaiting = factory(\SingPlus\Domains\Orders\Models\ChargeOrder::class)->create([
        'user_id'       => $userId,
        'pay_order_id'  => null,
        'amount'        => 100000000,
        'sku_count'     => 1,
        'pay_order_details' => [
            'currency_amount'   => 600000000,
            'currency_code'     => 'CHY',
        ],
        'status'            => 1,
        'status_histories'  => [
            [
                'status'    => 1,
                'time'      => \Carbon\Carbon::now()->timestamp,
            ],
        ],
        'sku'   => [
            'sku_id'    => '2exampleSku',
            'price'     => 102000000,
            'coins'     => 102,
            'title'     => '102金币',
        ],
    ]);
    $orderPaid = factory(\SingPlus\Domains\Orders\Models\ChargeOrder::class)->create([
        'user_id'       => $userId,
        'pay_order_id'  => 'GPA.1234-5678-9012-33333',
        'amount'        => 100000000,
        'sku_count'     => 1,
        'pay_order_details' => [
            'currency_amount'   => 600000000,
            'currency_code'     => 'CHY',
        ],
        'status'        => 2,
        'status_histories'  => [
            [
                'status'    => 1,
                'time'      => \Carbon\Carbon::yesterday()->timestamp,
            ],
            [
                'status'    => 2,
                'time'      => \Carbon\Carbon::now()->timestamp,
            ],
        ],
        'sku'   => [
            'sku_id'    => '3exampleSku',
            'price'     => 103000000,
            'coins'     => 103,
            'title'     => '103金币',
        ],
    ]);
    $orderClosed = factory(\SingPlus\Domains\Orders\Models\ChargeOrder::class)->create([
        'user_id'       => $userId,
        'pay_order_id'  => 'GPA.1234-5678-9012-44444',
        'amount'        => 100000000,
        'sku_count'     => 1,
        'pay_order_details' => [
            'currency_amount'   => 600000000,
            'currency_code'     => 'CHY',
        ],
        'status'        => 3,
        'status_histories'  => [
            [
                'status'    => 1,
                'time'      => \Carbon\Carbon::yesterday()->timestamp,
            ],
            [
                'status'    => 3,
                'time'      => \Carbon\Carbon::now()->timestamp,
            ],
        ],
        'sku'   => [
            'sku_id'    => '4exampleSku',
            'price'     => 104000000,
            'coins'     => 104,
            'title'     => '104金币',
        ],
    ]);

    return (object) [
        'orders'    => (object) [
            'waiting'   => $orderWaiting,
            'paid'      => $orderPaid,
            'closed'    => $orderClosed,
        ],
    ];
  }
}
