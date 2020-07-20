<?php

namespace FeatureTest\SingPlus\Listeners\Users;

use Mockery;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use FeatureTest\SingPlus\TestCase;
use FeatureTest\SingPlus\MongodbClearTrait;
use SingPlus\Events\Works\WorkPublished as WorkPublishedEvent;

class AddWorkPublishedInAuthorProfileTest extends TestCase
{
  use MongodbClearTrait;

  public function testSuccess()
  {
    $profile = factory(\SingPlus\Domains\Users\Models\UserProfile::class)->create([
      'user_id'         => 'e2ace370d773433baca08b3dfc885fbb',
      'statistics_info' => [
        'admin_field_one'     => 'one',
        'admin_field_two'     => 'two',
        'latest_work_pub_at'  => '2017-10-10 10:10:10',
        'latest_work_id'      => '6e5c64263f044b1a98a4974477de1afd',
      ],
    ]);
    $work = factory(\SingPlus\Domains\Works\Models\Work::class)->create([
      'user_id'     => 'e2ace370d773433baca08b3dfc885fbb',
      'created_at'  => '2017-11-11 11:11:11',
    ]);

    $event = new WorkPublishedEvent($work->id);
    $res = $this->getListener()->handle($event);
    self::assertDatabaseHas('user_profiles', [
      '_id'       => $profile->id,
      'user_id'   => 'e2ace370d773433baca08b3dfc885fbb',
      'statistics_info' => [
        'admin_field_one'     => 'one',
        'admin_field_two'     => 'two',
        'latest_work_pub_at'  => '2017-11-11 11:11:11',
        'latest_work_id'      => $work->id,
      ],
    ]);
  }

  private function getListener()
  {
    return $this->app->make(\SingPlus\Listeners\Users\AddWorkPublishedInAuthorProfile::class);
  }
}
