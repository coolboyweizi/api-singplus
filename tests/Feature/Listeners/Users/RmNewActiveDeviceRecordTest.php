<?php

namespace FeatureTest\SingPlus\Listeners\Users;

use Mockery;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use FeatureTest\SingPlus\TestCase;
use FeatureTest\SingPlus\MongodbClearTrait;
use SingPlus\Events\Users\PushAliasBound as PushAliasBoundEvent;

class RmNewActiveDeviceRecordTest extends TestCase
{
  use MongodbClearTrait; 

  public function testSuccess()
  {
    factory(\SingPlus\Domains\ClientSupports\Models\NewActiveDeviceInfo::class)->create([
      'alias'     => 'abcdefg',
    ]);

    $userId = '4e0b58872daa4577a71a0008cf009d9d';
    $event = new PushAliasBoundEvent('singplus', $userId, 'abcdefg');

    $res = $this->getListener()->handle($event);
    self::assertTrue($res);

    self::assertDatabaseMissing('new_active_device_infos', [
      'alias'     => 'abcdefg',
    ]);
  }

  private function getListener()
  {
    return $this->app->make(\SingPlus\Listeners\Users\RmNewActiveDeviceRecord::class);
  }
}
