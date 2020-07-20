<?php

namespace FeatureTest\SingPlus\Jobs;

use GuzzleHttp\Client;
use GuzzleHttp\Handler\MockHandler;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Psr7\Response;
use FeatureTest\SingPlus\TestCase;
use FeatureTest\SingPlus\MongodbClearTrait;
use SingPlus\Jobs\SyncStaleSocialiteUserIntoChannel as SyncStaleSocialiteUserIntoChannelJob;

class SyncStaleSocialiteUserIntoChannelTest extends TestCase
{
  use MongodbClearTrait;

  public function testSuccess()
  {
    $soUser = factory(\SingPlus\Domains\Users\Models\SocialiteUser::class)->create([
      'user_id'           => '2ba7c204f45e48d98c4a10e2e30cdf91',
      'socialite_user_id' => '272e14cbc32a41e7a7d38f8958f78ac9', 
      'provider'          => 'facebook',
      'channels'          => null,
    ]);

    $job = new SyncStaleSocialiteUserIntoChannelJob(
      '2ba7c204f45e48d98c4a10e2e30cdf91',
      '272e14cbc32a41e7a7d38f8958f78ac9',
      'aaaaaaaaaaaaaaaa',
      'bbbbbbbbbbbbbbbb'
    );

    $res = $job->handle($this->app->make(\SingPlus\Services\SocialiteService::class));

    $this->assertDatabaseHas('socialite_users', [
      '_id'               => $soUser->id,
      'socialite_user_id' => null,
      'provider'          => 'facebook',
      'channels'          => [
        'singplus'  => [
          'openid'  => '272e14cbc32a41e7a7d38f8958f78ac9',
          'token'   => 'aaaaaaaaaaaaaaaa',
        ],
      ],
      'union_token' => 'bbbbbbbbbbbbbbbb',
    ]);
  }

  public function testSuccess_UnionTokenExists()
  {
    factory(\SingPlus\Domains\Users\Models\SocialiteUser::class)->create([
      'union_token' => 'bbbbbbbbbbbbbbbb',
    ]);

    $soUser = factory(\SingPlus\Domains\Users\Models\SocialiteUser::class)->create([
      'user_id'           => '2ba7c204f45e48d98c4a10e2e30cdf91',
      'socialite_user_id' => '272e14cbc32a41e7a7d38f8958f78ac9', 
      'provider'          => 'facebook',
      'channels'          => null,
    ]);

    $job = new SyncStaleSocialiteUserIntoChannelJob(
      '2ba7c204f45e48d98c4a10e2e30cdf91',
      '272e14cbc32a41e7a7d38f8958f78ac9',
      'aaaaaaaaaaaaaaaa',
      'bbbbbbbbbbbbbbbb'
    );

    $res = $job->handle($this->app->make(\SingPlus\Services\SocialiteService::class));

    $this->assertDatabaseHas('socialite_users', [
      '_id'               => $soUser->id,
      'socialite_user_id' => null,
      'provider'          => 'facebook',
      'channels'          => [
        'singplus'  => [
          'openid'  => '272e14cbc32a41e7a7d38f8958f78ac9',
          'token'   => 'aaaaaaaaaaaaaaaa',
        ],
      ],
      'union_token' => null,
    ]);
  }
}
