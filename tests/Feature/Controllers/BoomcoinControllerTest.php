<?php
/**
 * Created by PhpStorm.
 * User: karl
 * Date: 2018/4/8
 * Time: 下午4:29
 */

namespace FeatureTest\SingPlus\Controllers;

use Cache;
use Queue;
use Mockery;
use GuzzleHttp\Client;
use GuzzleHttp\Handler\MockHandler;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Psr7\Response;
use FeatureTest\SingPlus\TestCase;
use FeatureTest\SingPlus\MongodbClearTrait;
use SingPlus\Contracts\Coins\Constants\Trans;
use SingPlus\Domains\Boomcoin\Models\Order;
use SingPlus\Exceptions\ExceptionCode;

class BoomcoinControllerTest extends TestCase
{
    use MongodbClearTrait;

    //=================================
    //        getProductList
    //=================================
    public function testGetProductListSuccess(){
        $user = factory(\SingPlus\Domains\Users\Models\User::class)->create([
            'mobile' => '8613480632045',
            'country_code' => '86'
        ]);

        $this->mockHttpClient(json_encode([
            'responseCode'    => 0,
            'message' => [
                'boomcoins' => 12260,
                'countryCode' => 'CN'
            ]
        ]));

        $response = $this->actingAs($user)->getJson('v3/boomcoin/products')
            ->assertJson(['code' => 0]);

        $response = json_decode($response->getContent());
        $data = $response->data;
        self::assertEquals(12260, $data->boomCoins);
        self::assertCount(6, $data->products);
        $this->assertDatabaseHas('users', [
            '_id' => $user->id,
            'boomcoin_country_code' => 'CN'
        ]);
    }

    public function testGetProductListFailed_MobileNotBind(){
        $user = factory(\SingPlus\Domains\Users\Models\User::class)->create([
            'mobile' => null
        ]);
        $response = $this->actingAs($user)->getJson('v3/boomcoin/products')
            ->assertJson(['code' => ExceptionCode::USER_MOBILE_NOT_BOUND]);
    }

    public function testGetProductListFailed_MobileNotExistInBoomcoinAccount(){
        $user = factory(\SingPlus\Domains\Users\Models\User::class)->create([
            'mobile' => '8613480632045',
            'country_code' => '86'
        ]);

        $this->mockHttpClient(json_encode([
            'responseCode'    => 2000,
            'message' => [
            ]
        ]));

        $response = $this->actingAs($user)->getJson('v3/boomcoin/products')
            ->assertJson(['code' => ExceptionCode::BOOMCOIN_USER_NOT_EXSITS]);

    }

    public function testGetProductListFailed_AuthFailed(){
        $user = factory(\SingPlus\Domains\Users\Models\User::class)->create([
            'mobile' => '8613480632045',
            'country_code' => '86'
        ]);

        $this->mockHttpClient(json_encode([
            'responseCode'    => 2003,
            'message' => [
            ]
        ]));

        $response = $this->actingAs($user)->getJson('v3/boomcoin/products')
            ->assertJson(['code' => ExceptionCode::BOOMCOIN_GENERAL]);
    }

    public function testGetProductListFailed_GatewayError(){
        $user = factory(\SingPlus\Domains\Users\Models\User::class)->create([
            'mobile' => '8613480632045',
            'country_code' => '86'
        ]);

        $this->mockHttpClient(json_encode([
            'responseCode'    => 2003,
            'message' => [
            ]
        ]), 504);
        $response = $this->actingAs($user)->getJson('v3/boomcoin/products')
            ->assertJson(['code' => ExceptionCode::GENERAL]);
    }

    //=================================
    //        exchangeCoins
    //=================================
    public function testExchangeCoinsSuccess(){

        $product = factory(\SingPlus\Domains\Boomcoin\Models\Product::class)->create([
            'dollars' => 1,
            'coins'   => 5000,
            'display_order' => 100
        ]);
        $user = factory(\SingPlus\Domains\Users\Models\User::class)->create([
            'mobile' => '8613480632045',
            'country_code' => '86',
            'boomcoin_country_code' => 'CN'
        ]);
        $userProfile = factory(\SingPlus\Domains\Users\Models\UserProfile::class)->create([
            'user_id' => $user->id
        ]);

        $counterMock = Mockery::mock(\Illuminate\Contracts\Cache\Store::class);
        $counterMock->shouldReceive('increment')
            ->once()
            ->with('cointrans', 100)
            ->andReturn(100);
        $counterMock->shouldReceive('increment')
            ->once()
            ->with('boomcoin_order', 100)
            ->andReturn(100);
        Cache::shouldReceive('driver')
            ->twice()
            ->with('counter')
            ->andReturn($counterMock);

        $this->mockHttpClient(json_encode([
            'responseCode'    => 0,
            'message' => [
                'boomcoins' => 11000,
                'countryCode' => 'CN',
                'transaction_tracking_id' => "fd25dda2-3cb2-49ed-b098-0befcd86bdfc",
                'reference' => "17b63ebc-e91e-4df3-8e6a-9b9777709ac9"
            ]
        ]));

        $response = $this->actingAs($user)
            ->postJson('v3/boomcoin/exchange', [
                'productId' => $product->id
            ])->assertJson(['code' => 0]);

        $response = json_decode($response->getContent());
        $data = $response->data;
        $this->assertDatabaseHas('coin_transactions', [
            'user_id' => $user->id,
            'operator' => $user->id,
            'amount'   => 5000,
            'source'   => Trans::SOURECE_DEPOSIT_BOOMCOIN,
            'details' => (object)[
                'order_id' => $data->orderId
            ]
        ]);

        $this->assertDatabaseHas('boomcoin_order', [
            'user_id' => $user->id,
            'product_id' => $product->id,
            'amount'  => 361,
            'msisnd'  => '8613480632045',
            'country_code' => 'CN',
            'status' => Order::STATUS_SUCCESS,
            'transaction_id' => "fd25dda2-3cb2-49ed-b098-0befcd86bdfc",
            'display_order' => 100,
        ]);

    }

    public function testExchageCoinFailed_UserNoBoomcoinCountryCode(){
        $product = factory(\SingPlus\Domains\Boomcoin\Models\Product::class)->create([
            'dollars' => 1,
            'coins'   => 5000,
            'display_order' => 100
        ]);
        $user = factory(\SingPlus\Domains\Users\Models\User::class)->create([
            'mobile' => '8613480632045',
            'country_code' => '86',
        ]);
        $userProfile = factory(\SingPlus\Domains\Users\Models\UserProfile::class)->create([
            'user_id' => $user->id
        ]);
        $response = $this->actingAs($user)
            ->postJson('v3/boomcoin/exchange', [
                'productId' => $product->id
            ])->assertJson(['code' => 11000]);
    }

    public function testExchangeCoinFailed_ProductNotExist(){
        $user = factory(\SingPlus\Domains\Users\Models\User::class)->create([
            'mobile' => '8613480632045',
            'country_code' => '86',
            'boomcoin_country_code' => 'CN'
        ]);
        $userProfile = factory(\SingPlus\Domains\Users\Models\UserProfile::class)->create([
            'user_id' => $user->id
        ]);
        $response = $this->actingAs($user)
            ->postJson('v3/boomcoin/exchange', [
                'productId' => '787739c708ac4b80ac718fa6b26a0b5a'
            ])->assertJson(['code' => 11000]);
    }

    public function testExchangeCoinFailed_UserNotExistInBoomcoinAccount(){
        $product = factory(\SingPlus\Domains\Boomcoin\Models\Product::class)->create([
            'dollars' => 1,
            'coins'   => 5000,
            'display_order' => 100
        ]);
        $user = factory(\SingPlus\Domains\Users\Models\User::class)->create([
            'mobile' => '8613480632045',
            'country_code' => '86',
            'boomcoin_country_code' => 'CN'
        ]);
        $userProfile = factory(\SingPlus\Domains\Users\Models\UserProfile::class)->create([
            'user_id' => $user->id
        ]);

        $this->mockHttpClient(json_encode([
            'responseCode'    => 2000,
            'message' => [

            ]
        ]));

        $counterMock = Mockery::mock(\Illuminate\Contracts\Cache\Store::class);
        $counterMock->shouldReceive('increment')
            ->once()
            ->with('boomcoin_order', 100)
            ->andReturn(100);
        Cache::shouldReceive('driver')
            ->once()
            ->with('counter')
            ->andReturn($counterMock);

        $response = $this->actingAs($user)
            ->postJson('v3/boomcoin/exchange', [
                'productId' => $product->id
            ])->assertJson(['code' => ExceptionCode::BOOMCOIN_USER_NOT_EXSITS]);

        $this->assertDatabaseHas('boomcoin_order', [
            'user_id' => $user->id,
            'product_id' => $product->id,
            'amount'  => 361,
            'msisnd'  => '8613480632045',
            'country_code' => 'CN',
            'status' => Order::STATUS_FAILURE,
            'display_order' => 100,
        ]);
    }

    public function testExchangeFailed_BoomcoinNotEnoughBalance(){
        $product = factory(\SingPlus\Domains\Boomcoin\Models\Product::class)->create([
            'dollars' => 1,
            'coins'   => 5000,
            'display_order' => 100
        ]);
        $user = factory(\SingPlus\Domains\Users\Models\User::class)->create([
            'mobile' => '8613480632045',
            'country_code' => '86',
            'boomcoin_country_code' => 'CN'
        ]);
        $userProfile = factory(\SingPlus\Domains\Users\Models\UserProfile::class)->create([
            'user_id' => $user->id
        ]);

        $this->mockHttpClient(json_encode([
            'responseCode'    => 2001,
            'message' => [

            ]
        ]));

        $counterMock = Mockery::mock(\Illuminate\Contracts\Cache\Store::class);
        $counterMock->shouldReceive('increment')
            ->once()
            ->with('boomcoin_order', 100)
            ->andReturn(100);
        Cache::shouldReceive('driver')
            ->once()
            ->with('counter')
            ->andReturn($counterMock);

        $response = $this->actingAs($user)
            ->postJson('v3/boomcoin/exchange', [
                'productId' => $product->id
            ])->assertJson(['code' => ExceptionCode::BOOMCOIN_BALANCE_NOT_ENOUGH]);

        $this->assertDatabaseHas('boomcoin_order', [
            'user_id' => $user->id,
            'product_id' => $product->id,
            'amount'  => 361,
            'msisnd'  => '8613480632045',
            'country_code' => 'CN',
            'status' => Order::STATUS_FAILURE,
            'display_order' => 100,
        ]);
    }

    public function testExchangeFailed_AuthFailed(){
        $product = factory(\SingPlus\Domains\Boomcoin\Models\Product::class)->create([
            'dollars' => 1,
            'coins'   => 5000,
            'display_order' => 100
        ]);
        $user = factory(\SingPlus\Domains\Users\Models\User::class)->create([
            'mobile' => '8613480632045',
            'country_code' => '86',
            'boomcoin_country_code' => 'CN'
        ]);
        $userProfile = factory(\SingPlus\Domains\Users\Models\UserProfile::class)->create([
            'user_id' => $user->id
        ]);

        $this->mockHttpClient(json_encode([
            'responseCode'    => 2003,
            'message' => [

            ]
        ]));

        $counterMock = Mockery::mock(\Illuminate\Contracts\Cache\Store::class);
        $counterMock->shouldReceive('increment')
            ->once()
            ->with('boomcoin_order', 100)
            ->andReturn(100);
        Cache::shouldReceive('driver')
            ->once()
            ->with('counter')
            ->andReturn($counterMock);

        $response = $this->actingAs($user)
            ->postJson('v3/boomcoin/exchange', [
                'productId' => $product->id
            ])->assertJson(['code' => ExceptionCode::BOOMCOIN_GENERAL]);

        $this->assertDatabaseHas('boomcoin_order', [
            'user_id' => $user->id,
            'product_id' => $product->id,
            'amount'  => 361,
            'msisnd'  => '8613480632045',
            'country_code' => 'CN',
            'status' => Order::STATUS_FAILURE,
            'display_order' => 100,
        ]);
    }

    public function testExchangeFailed_GatewayServiceError(){
        Queue::fake();
        $product = factory(\SingPlus\Domains\Boomcoin\Models\Product::class)->create([
            'dollars' => 1,
            'coins'   => 5000,
            'display_order' => 100
        ]);
        $user = factory(\SingPlus\Domains\Users\Models\User::class)->create([
            'mobile' => '8613480632045',
            'country_code' => '86',
            'boomcoin_country_code' => 'CN'
        ]);
        $userProfile = factory(\SingPlus\Domains\Users\Models\UserProfile::class)->create([
            'user_id' => $user->id
        ]);

        $counterMock = Mockery::mock(\Illuminate\Contracts\Cache\Store::class);
        $counterMock->shouldReceive('increment')
            ->once()
            ->with('boomcoin_order', 100)
            ->andReturn(100);
        Cache::shouldReceive('driver')
            ->once()
            ->with('counter')
            ->andReturn($counterMock);

        $this->mockHttpClient(json_encode([]), 504);

        $response = $this->actingAs($user)
            ->postJson('v3/boomcoin/exchange', [
                'productId' => $product->id
            ])->assertJson(['code' => ExceptionCode::BOOMCOIN_EXCHANGE_EXCEPTION]);

        $this->assertDatabaseHas('boomcoin_order', [
            'user_id' => $user->id,
            'product_id' => $product->id,
            'amount'  => 361,
            'msisnd'  => '8613480632045',
            'country_code' => 'CN',
            'status' => Order::STATUS_PENDING,
            'display_order' => 100
        ]);

        $order = Order::where('user_id', $user->id)
            ->where('product_id', $product->id)
            ->first();

        Queue::assertPushed(
            \SingPlus\Jobs\CheckBoomcoinOrder::class,
            function ($job) use ($user, $order) {
                return $job->userId == $user->id &&
                    $job->orderId == $order->id;
            });

    }

    //=================================
    //        checkOrderStatus
    //=================================
    public function testCheckOrderStatusSuccess(){
        $product = factory(\SingPlus\Domains\Boomcoin\Models\Product::class)->create([
            'dollars' => 1,
            'coins'   => 5000,
            'display_order' => 100
        ]);
        $user = factory(\SingPlus\Domains\Users\Models\User::class)->create([
            'mobile' => '8613480632045',
            'country_code' => '86',
            'boomcoin_country_code' => 'CN'
        ]);
        $userProfile = factory(\SingPlus\Domains\Users\Models\UserProfile::class)->create([
            'user_id' => $user->id
        ]);

        $order = factory(\SingPlus\Domains\Boomcoin\Models\Order::class)->create([
            'user_id' => $user->id,
            'product_id' => $product->id,
            'amount' => 361,
            'msisnd' => '8613480632045',
            'country_code' => 'CN',
            'status' => Order::STATUS_PENDING
        ]);

        $this->mockHttpClient(json_encode([
            'responseCode'    => 0,
            'message' => [
                'status' => 'COMPLETED',
                'transaction_tracking_id' => 'fd25dda2-3cb2-49ed-b098-0befcd86bdfc',
                'boomcoins' => 12000,
                'countryCode' => 'CN'
            ]
        ]));

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
            ->getJson('v3/boomcoin/order/check')
            ->assertJson(['code' => 0]);
        $response = json_decode($response->getContent());

        $this->assertDatabaseHas('coin_transactions', [
            'user_id' => $user->id,
            'operator' => $user->id,
            'amount'   => 5000,
            'source'   => Trans::SOURECE_DEPOSIT_BOOMCOIN,
            'details' => (object)[
                'order_id' => $order->id
            ]
        ]);

        $this->assertDatabaseHas('boomcoin_order', [
            'user_id' => $user->id,
            'product_id' => $product->id,
            'amount'  => 361,
            'msisnd'  => '8613480632045',
            'country_code' => 'CN',
            'status' => Order::STATUS_SUCCESS,
            'transaction_id' => "fd25dda2-3cb2-49ed-b098-0befcd86bdfc",
            'display_order' => 100,
        ]);
    }

    public function testCheckOrderStatusSuccess_OrderNotExist(){
        $user = factory(\SingPlus\Domains\Users\Models\User::class)->create([
            'mobile' => '8613480632045',
            'country_code' => '86',
            'boomcoin_country_code' => 'CN'
        ]);
        $userProfile = factory(\SingPlus\Domains\Users\Models\UserProfile::class)->create([
            'user_id' => $user->id
        ]);

        $response = $this->actingAs($user)
            ->getJson('v3/boomcoin/order/check')
            ->assertJson(['code' => 0]);
        $response = json_decode($response->getContent());
        $data = $response->data;
        self::assertEquals(true, $data->orderWell);
    }

    public function testCheckOrderStatusSuccess_OrderStatusNotPending(){
        $product = factory(\SingPlus\Domains\Boomcoin\Models\Product::class)->create([
            'dollars' => 1,
            'coins'   => 5000,
            'display_order' => 100
        ]);
        $user = factory(\SingPlus\Domains\Users\Models\User::class)->create([
            'mobile' => '8613480632045',
            'country_code' => '86',
            'boomcoin_country_code' => 'CN'
        ]);
        $userProfile = factory(\SingPlus\Domains\Users\Models\UserProfile::class)->create([
            'user_id' => $user->id
        ]);

        $order = factory(\SingPlus\Domains\Boomcoin\Models\Order::class)->create([
            'user_id' => $user->id,
            'product_id' => $product->id,
            'amount' => 361,
            'msisnd' => '8613480632045',
            'country_code' => 'CN',
            'status' => Order::STATUS_SUCCESS
        ]);

        $response = $this->actingAs($user)
            ->getJson('v3/boomcoin/order/check')
            ->assertJson(['code' => 0]);
        $response = json_decode($response->getContent());
        $data = $response->data;
        self::assertEquals(true, $data->orderWell);
    }

    public function testCheckOrderStatusSuccess_OrderInvalidForGatewayService(){
        $product = factory(\SingPlus\Domains\Boomcoin\Models\Product::class)->create([
            'dollars' => 1,
            'coins'   => 5000,
            'display_order' => 100
        ]);
        $user = factory(\SingPlus\Domains\Users\Models\User::class)->create([
            'mobile' => '8613480632045',
            'country_code' => '86',
            'boomcoin_country_code' => 'CN'
        ]);
        $userProfile = factory(\SingPlus\Domains\Users\Models\UserProfile::class)->create([
            'user_id' => $user->id
        ]);

        $order = factory(\SingPlus\Domains\Boomcoin\Models\Order::class)->create([
            'user_id' => $user->id,
            'product_id' => $product->id,
            'amount' => 361,
            'msisnd' => '8613480632045',
            'country_code' => 'CN',
            'status' => Order::STATUS_PENDING
        ]);

        $this->mockHttpClient(json_encode([
            'responseCode'    => 0,
            'message' => [
                'status' => 'INVALID',
                'transaction_tracking_id' => 'fd25dda2-3cb2-49ed-b098-0befcd86bdfc',
                'boomcoins' => 12000,
                'countryCode' => 'CN'
            ]
        ]));
        $response = $this->actingAs($user)
            ->getJson('v3/boomcoin/order/check')
            ->assertJson(['code' => 0]);
        $response = json_decode($response->getContent());
        $data = $response->data;
        self::assertEquals(true, $data->orderWell);

        $this->assertDatabaseHas('boomcoin_order', [
            'user_id' => $user->id,
            'product_id' => $product->id,
            'amount' => 361,
            'msisnd' => '8613480632045',
            'country_code' => 'CN',
            'status' => Order::STATUS_FAILURE
        ]);
    }

    public function testCheckOrderStatusFailed_GatewayError(){
        $product = factory(\SingPlus\Domains\Boomcoin\Models\Product::class)->create([
            'dollars' => 1,
            'coins'   => 5000,
            'display_order' => 100
        ]);
        $user = factory(\SingPlus\Domains\Users\Models\User::class)->create([
            'mobile' => '8613480632045',
            'country_code' => '86',
            'boomcoin_country_code' => 'CN'
        ]);
        $userProfile = factory(\SingPlus\Domains\Users\Models\UserProfile::class)->create([
            'user_id' => $user->id
        ]);

        $order = factory(\SingPlus\Domains\Boomcoin\Models\Order::class)->create([
            'user_id' => $user->id,
            'product_id' => $product->id,
            'amount' => 361,
            'msisnd' => '8613480632045',
            'country_code' => 'CN',
            'status' => Order::STATUS_PENDING
        ]);

        $this->mockHttpClient(json_encode([
            'responseCode'    => 0,
            'message' => [
                'status' => 'INVALID',
                'transaction_tracking_id' => 'fd25dda2-3cb2-49ed-b098-0befcd86bdfc',
                'boomcoins' => 12000,
                'countryCode' => 'CN'
            ]
        ]), 504);
        $response = $this->actingAs($user)
            ->getJson('v3/boomcoin/order/check')
            ->assertJson(['code' => ExceptionCode::BOOMCOIN_GENERAL]);

        $this->assertDatabaseHas('boomcoin_order', [
            'user_id' => $user->id,
            'product_id' => $product->id,
            'amount' => 361,
            'msisnd' => '8613480632045',
            'country_code' => 'CN',
            'status' => Order::STATUS_PENDING
        ]);
    }

    /**
     * mock http client and response
     * 模拟http响应
     *
     * Usage: $this->mockHttpClient(json_encode([
     *          'code' => 0,
     *          'data' => [],
     *          'message'   => 'ok',
     *          ]))
     *
     * @param string $respBody 模拟响应body
     * @param int $respCode 模拟响应http status
     * @param array $respHeader 模拟响应http header
     */
    protected function mockHttpClient(
        $respBody,
        $respCode = 200,
        array $respHeader = []
    ) {
        $mock = new MockHandler();
        $mock->append(new Response($respCode, $respHeader, $respBody));

        $handler = HandlerStack::create($mock);
        $this->app[\GuzzleHttp\ClientInterface::class] = new Client(['handler' => $handler]);
    }


}