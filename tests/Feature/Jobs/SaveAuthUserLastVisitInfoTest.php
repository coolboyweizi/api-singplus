<?php

namespace FeatureTest\SingPlus\Jobs;

use FeatureTest\SingPlus\TestCase;
use FeatureTest\SingPlus\MongodbClearTrait;
use SingPlus\Jobs\SaveAuthUserLastVisitInfo as SaveAuthUserLastVisitInfoJob;

class SaveAuthUserLastVisitInfoTest extends TestCase
{
  use MongodbClearTrait;

  public function testSaveSuccess()
  {
    $user = factory(\SingPlus\Domains\Users\Models\User::class)->create();
    $userProfile = factory(\SingPlus\Domains\Users\Models\UserProfile::class)->create([
      'user_id'   => $user->id,
      'nickname'  => 'zhangsan',
    ]);

    $job = new SaveAuthUserLastVisitInfoJob($user->id, (object) ['version' => 'v1.0.2']);
    $job->handle($this->app->make(\SingPlus\Contracts\Users\Services\UserProfileService::class));

    $this->assertDatabaseHas('user_profiles', [
      '_id'             => $userProfile->id,
      'user_id'         => $user->id,
      'client_version'  => 'v1.0.2',
    ]);
  }
}
