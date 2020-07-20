<?php
/**
 * Created by PhpStorm.
 * User: karl
 * Date: 2018/2/2
 * Time: 下午2:58
 */

namespace FeatureTest\SingPlus\Listeners\Feeds;

use Mockery;
use Cache;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use FeatureTest\SingPlus\TestCase;
use FeatureTest\SingPlus\MongodbClearTrait;
use SingPlus\Events\Gifts\UserSendGiftForWork as UserSendGiftForWorkEvent;

class CreateUserSendGiftForWorkFeedTest extends TestCase
{
    use MongodbClearTrait;

    public function testSuccess()
    {
        $this->expectsEvents(\SingPlus\Events\FeedCreated::class);
        $counterMock = Mockery::mock(\Illuminate\Contracts\Cache\Store::class);
        $counterMock->shouldReceive('increment')
            ->once()
            ->with('feeds', 100)
            ->andReturn(100);
        Cache::shouldReceive('driver')
            ->once()
            ->with('counter')
            ->andReturn($counterMock);

        $giftHistory = factory(\SingPlus\Domains\Gifts\Models\GiftHistory::class)->create([
            'sender_id' => '127627b46a914aff930316fce2b3222a',
            'receiver_id' => '0976246d037c4f3385e8776a929c84af',
            'work_id'  => '8b36257adacd49d98293d0c7047f0dee',
            'amount'   => 100,
            'display_order' => 100,
            'status'    => 1,
            'gift_info' => [
                'id' => '392e5d65400e49908d6001f8fd960cb5',
                'type'  => 'AirCop',
                'name'  => 'AirCop Super',
                'icon' => [
                    'icon_small' => 'fsxxxxxssaircop.png',
                    'icon_big'  => 'fsxxxxxssaircop.png'
                ],
                'coins' => 100,
                'sold_amount'   => 100,
                'sold_coin_amount'  => 1000,
                'status'    => 1,
                'popularity'    => 10
            ]
        ]);

        $event = new UserSendGiftForWorkEvent($giftHistory->id);
        $feedId = $this->getListener()
            ->handle($event);

        self::assertDatabaseHas('feeds', [
            '_id'               => $feedId,
            'user_id'           => $giftHistory->receiver_id,
            'operator_user_id'  => $giftHistory->sender_id,
            'type'              => 'gift_send_for_work',
            'detail'            => [
                'work_id'     => $giftHistory->work_id,
                'giftHistory_id'  => $giftHistory->id,
            ],
            'status'            => 1,
            'is_read'           => 0,
            'display_order'     => 100,
        ]);
    }

    private function getListener()
    {
        return $this->app->make(\SingPlus\Listeners\Feeds\CreateGiftSendForWorkFeed::class);
    }
}