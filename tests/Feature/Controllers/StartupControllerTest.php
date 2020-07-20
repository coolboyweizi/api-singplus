<?php

namespace FeatureTest\SingPlus\Controllers\Auth;

use Carbon\Carbon;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use FeatureTest\SingPlus\TestCase;
use FeatureTest\SingPlus\MongodbClearTrait;

class StartupControllerTest extends TestCase
{
  use MongodbClearTrait; 

  //=================================
  //      Startup
  //=================================
  public function testStartupSuccess_NotLogged()
  {
    config([
      'filesystems.disks.s3.region'  => 'eu-central-1',
      'filesystems.disks.s3.bucket'  => 'sing-plus',
      'lang.langs'  => ['en', 'hi', 'fr'],
    ]);

    $startTime = Carbon::now()->addSeconds(-5);
    $stopTime = Carbon::now()->addSeconds(30);
    $ad = factory(\SingPlus\Domains\Ads\Models\Advertisement::class)->create([
      'title' => 'ad-title',
      'image' => 'ad-image',
      'spec_images' => [
        '80*80'   => 'image-80',
        '120*120' => 'image-120',
      ],
      'type'  => 'startup',
      'start_time'  => $startTime->format('Y-m-d H:i:s'),
      'stop_time'  => $stopTime->format('Y-m-d H:i:s'),
    ]);
    
    $response = $this->postJson('v3/startup');
    $response->assertJson([
      'code' => 0,
      'data' => [
        'logged'  => false,
        'user'    => null,
        'update'  => [
          'isUrlForce'      => false,
          'recommendUpdate' => false,
          'forceUpdate'     => false,
          'updateTips'      => null,
        ],
        'ads'   => [
          [
            'adId'        => $ad->id,
            'title'       => $ad->title,
            'type'        => 'startup',
            'needLogin'   => false,
            'image'       => 'https://sing-plus.s3.eu-central-1.amazonaws.com/ad-image',
            'specImages'  => [
              '80*80'   => 'https://sing-plus.s3.eu-central-1.amazonaws.com/image-80',
              '120*120' => 'https://sing-plus.s3.eu-central-1.amazonaws.com/image-120',
            ],
            'link'        => null,
            'startTimestamp'  => $startTime->getTimestamp(),
            'stopTimestamp'   => $stopTime->getTimestamp(),
          ],
        ],
        'supportLangs'  => ['en', 'hi', 'fr'],
      ],
    ]);
  }

  public function testStartupSuccess_NotLogged_FromCountryOperation()
  {
    $this->enableNationOperationMiddleware();
    config([
      'nationality.operation_country_abbr'  => ['TZ'],
    ]);

    config([
      'filesystems.disks.s3.region'  => 'eu-central-1',
      'filesystems.disks.s3.bucket'  => 'sing-plus',
    ]);

    $startTime = Carbon::now()->addSeconds(-5);
    $stopTime = Carbon::now()->addSeconds(30);
    $adOne = factory(\SingPlus\Domains\Ads\Models\Advertisement::class)->create([
      'title' => 'ad-one-title',
      'image' => 'ad-one-image',
      'type'  => 'startup',
      'start_time'    => $startTime->format('Y-m-d H:i:s'),
      'stop_time'     => $stopTime->format('Y-m-d H:i:s'),
      'country_abbr'  => 'IN'
    ]);
    $adTwo = factory(\SingPlus\Domains\Ads\Models\Advertisement::class)->create([
      'title' => 'ad-two-title',
      'image' => 'ad-two-image',
      'type'  => 'startup',
      'start_time'    => $startTime->format('Y-m-d H:i:s'),
      'stop_time'     => $stopTime->format('Y-m-d H:i:s'),
      'country_abbr'  => 'TZ',
    ]);
    
    $response = $this->postJson('v3/startup', [], [
      'X-CountryAbbr' => 'TZ',
    ]);
    $response->assertJson([
      'code' => 0,
      'data' => [
        'logged'  => false,
        'user'    => null,
        'update'  => [
          'recommendUpdate' => false,
          'forceUpdate'     => false,
          'updateTips'      => null,
        ],
        'ads'   => [
          [
            'adId'      => $adTwo->id,
            'title'     => $adTwo->title,
            'type'      => 'startup',
            'needLogin' => false,
            'image'     => 'https://sing-plus.s3.eu-central-1.amazonaws.com/ad-two-image',
            'link'      => null,
            'startTimestamp'  => $startTime->getTimestamp(),
            'stopTimestamp'   => $stopTime->getTimestamp(),
          ],
        ],
      ],
    ]);
  }

  public function testStartupSuccess_UnActiveDevice()
  {
    $response = $this->postJson('v3/startup', [
                        'alias'         => 'push-alias', 
                        'mobile'        => '13800138000',
                        'countryCode'   => '86',
                        'abbreviation'  => 'CHN',
                        'latitude'      => '123.22',
                        'longitude'     => '33.87',
                      ]);
    $response->assertJson([
      'code' => 0,
      'data' => [
        'logged'  => false,
        'user'    => null,
        'update'  => [
          'recommendUpdate' => false,
          'forceUpdate'     => false,
          'updateTips'      => null,
        ],
      ],
    ]);

    $this->assertDatabaseHas('new_active_device_infos', [
      'alias'         => 'push-alias',
      'mobile'        => '13800138000',
      'abbreviation'  => 'CHN',
      'country_code'  => '86',
      'latitude'      => '123.22',
      'longitude'     => '33.87',
      'client_version'  => null,
    ]);
  }

  public function testStartupSuccess_UnActiveDevice_BoomsingChannel()
  {
    config([
      'tudc.currentChannel' => 'boomsing',
    ]);
    $response = $this->postJson('v3/startup', [
                        'alias'         => 'push-alias', 
                        'mobile'        => '13800138000',
                        'countryCode'   => '86',
                        'abbreviation'  => 'CHN',
                        'latitude'      => '123.22',
                        'longitude'     => '33.87',
                      ]);
    $response->assertJson([
      'code' => 0,
      'data' => [
        'logged'  => false,
        'user'    => null,
        'update'  => [
          'recommendUpdate' => false,
          'forceUpdate'     => false,
          'updateTips'      => null,
        ],
      ],
    ]);

    $this->assertDatabaseMissing('new_active_device_infos', [
      'mobile'        => '13800138000',
      'abbreviation'  => 'CHN',
      'country_code'  => '86',
      'latitude'      => '123.22',
      'longitude'     => '33.87',
      'client_version'  => null,
    ]);
  }

  public function testStartupSuccess_AliasAreadyBound()
  {
    factory(\SingPlus\Domains\Users\Models\User::class)->create([
      'push_alias'  => 'push-alias',
    ]);

    $response = $this->postJson('v3/startup', [
                        'alias'         => 'push-alias',
                        'mobile'        => '13800138000',
                        'countryCode'   => '86',
                        'abbreviation'  => 'CHN',
                        'latitude'      => '123.22',
                        'longitude'     => '33.87',
                      ]);
    $response->assertJson([
      'code' => 0,
      'data' => [
        'logged'  => false,
        'user'    => null,
        'update'  => [
          'recommendUpdate' => false,
          'forceUpdate'     => false,
          'updateTips'      => null,
        ],
      ],
    ]);

    $this->assertDatabaseMissing('new_active_device_infos', [
      'alias' => 'push-alias',
    ]);
  }

  public function testStartupSuccess_NewUser()
  {
    $user = factory(\SingPlus\Domains\Users\Models\User::class)->create();

    $response = $this->actingAs($user)
                     ->postJson('v3/startup');
    $response->assertJson([
      'code' => 0,
      'data' => [
        'logged'  => true,
        'user'    => [
          'isNewUser' => true,
        ],
      ],
    ]);
  }

  public function testStartupSuccess_NotNewUser()
  {
    $user = factory(\SingPlus\Domains\Users\Models\User::class)->create();
    factory(\SingPlus\Domains\Users\Models\UserProfile::class)->create([
      'user_id' => $user->id,
      'is_new'  => false,
    ]);

    $response = $this->actingAs($user)
                     ->postJson('v3/startup');
    $response->assertJson([
      'code' => 0,
      'data' => [
        'logged'  => true,
        'user'    => [
          'isNewUser' => false,
        ],
      ],
    ]);
  }

  public function testStartupSuccess_NotUpdate()
  {
    factory(\SingPlus\Domains\ClientSupports\Models\VersionUpdate::class)->create([
      'latest'  => '0.0.10',
      'force'   => '0.0.08',
    ]);
    $response = $this->postJson('v3/startup', [
      'HTTP_X-Version'  => '0.0.10', 
    ]);
    $response->assertJson([
      'code' => 0,
      'data' => [
        'logged'  => false,
        'user'    => null,
        'update'  => [
          'recommendUpdate' => false,
          'forceUpdate'     => false,
          'updateTips'      => null,
        ],
      ],
    ]);
  }

  public function testStartupSuccess_NeedUpdate()
  {
    factory(\SingPlus\Domains\ClientSupports\Models\VersionUpdate::class)->create([
      'latest'  => '0.0.20',
      'force'   => '0.0.12',
    ]);
    factory(\SingPlus\Domains\ClientSupports\Models\VersionUpdateTip::class)->create([
      'version' => '0.0.20',
      'url'     => 'http://download.sing.plus',
      'boomsing_url'    => 'http://download.boomsing.plus',
      'content' => 'new update',
    ]);
    $response = $this->postJson('v3/startup', [], [
      'HTTP_X-Version'  => '0.0.10', 
      'HTTP_X-AppName'  => 'singplus',
    ]);
    $response->assertJson([
      'code' => 0,
      'data' => [
        'logged'  => false,
        'user'    => null,
        'update'  => [
          'isUrlForce'      => false,
          'recommendUpdate' => false,
          'forceUpdate'     => true,
          'updateTips'      => [
            'version' => '0.0.20',
            'url'     => 'http://download.sing.plus',
            'tips'    => 'new update',
          ],
        ],
        'ads'       => [],
      ],
    ]);
  }

  public function testStartupSuccess_NeedUpdate_AppNameIsBoomsing_ForceUpdate()
  {
    factory(\SingPlus\Domains\ClientSupports\Models\VersionUpdate::class)->create([
      'inner_update'            => 0,
      'boomsing_inner_update'   => 1,
      'latest'  => '0.0.20',
      'force'   => ['0.0.8', '0.0.12'],
      'alert'   => ['0.0.10', '0.0.18'],
    ]);
    factory(\SingPlus\Domains\ClientSupports\Models\VersionUpdateTip::class)->create([
      'version' => '0.0.20',
      'url'     => 'http://download.sing.plus',
      'apk_url' => 'abc',
      'boomsing_url'    => 'http://download.boomsing.plus',
      'boomsing_apk_url'    => 'boom-abc',
      'content' => [
        'en'    => 'new update',
        'hi'    => 'new update hi',
      ],
      'alert_content'   => 'new alert update',
    ]);
    config([
        'storage.cdn_host'  => 'cdn'
    ]);
    $response = $this->postJson('v3/startup', [], [
      'HTTP_X-Version'  => '0.0.10', 
      'HTTP_X-Language' => 'hi',
      'HTTP_X-AppName'  => 'boomsing',
    ]);
    $response->assertJson([
      'code' => 0,
      'data' => [
        'logged'  => false,
        'user'    => null,
        'update'  => [
          'isUrlForce'      => true,
          'recommendUpdate' => true,
          'forceUpdate'     => true,
          'updateTips'      => [
            'version' => '0.0.20',
            'url'     => 'http://download.boomsing.plus',
            'apkUrl'  => 'https://cdn/boom-abc',
            'tips'    => 'new update hi',
          ],
        ],
        'ads'       => [],
      ],
    ]);
  }

  public function testStartupSuccess_NeedUpdate_AppNameIsBoomsing_AlertUpdate()
  {
    factory(\SingPlus\Domains\ClientSupports\Models\VersionUpdate::class)->create([
      'inner_update'            => 0,
      'boomsing_inner_update'   => 1,
      'latest'  => '0.0.20',
      'force'   => ['0.0.8', '0.0.12'],
      'alert'   => ['0.0.10', '0.0.18'],
    ]);
    factory(\SingPlus\Domains\ClientSupports\Models\VersionUpdateTip::class)->create([
      'version' => '0.0.20',
      'url'     => 'http://download.sing.plus',
      'apk_url' => 'abc',
      'boomsing_url'    => 'http://download.boomsing.plus',
      'boomsing_apk_url'    => 'boom-abc',
      'content' => [
        'en'    => 'new update',
        'hi'    => 'new update hi',
      ],
      'alert_content'   => [
        'en'    => 'new alert update',
        'hi'    => 'new alert update hi',
      ],
    ]);
    config([
        'storage.cdn_host'  => 'cdn'
    ]);
    $response = $this->postJson('v3/startup', [], [
      'HTTP_X-Version'  => '0.0.14', 
      'HTTP_X-Language' => 'hi',
      'HTTP_X-AppName'  => 'singplus',
    ]);
    $response->assertJson([
      'code' => 0,
      'data' => [
        'logged'  => false,
        'user'    => null,
        'update'  => [
          'isUrlForce'      => false,
          'recommendUpdate' => true,
          'forceUpdate'     => false,
          'updateTips'      => [
            'version' => '0.0.20',
            'url'     => 'http://download.sing.plus',
            'apkUrl'  => 'https://cdn/abc',
            'tips'    => 'new alert update hi',
          ],
        ],
        'ads'       => [],
      ],
    ]);
  }

  public function testStartupSuccess_NeedUpdate_AppNameIsBoomsing_NoneedUpdate()
  {
    factory(\SingPlus\Domains\ClientSupports\Models\VersionUpdate::class)->create([
      'inner_update'            => 1,
      'boomsing_inner_update'   => 1,
      'latest'  => '0.0.20',
      'force'   => ['0.0.8', '0.0.12'],
      'alert'   => ['0.0.10', '0.0.18'],
    ]);
    factory(\SingPlus\Domains\ClientSupports\Models\VersionUpdateTip::class)->create([
      'version' => '0.0.20',
      'url'     => 'http://download.sing.plus',
      'apk_url' => 'abc',
      'boomsing_url'    => 'http://download.boomsing.plus',
      'boomsing_apk_url'    => 'boom-abc',
      'content' => [
        'en'    => 'new update',
        'hi'    => 'new update hi',
      ],
      'alert_content'   => [
        'en'    => 'new alert update',
        'hi'    => 'new alert update hi',
      ],
    ]);
    config([
        'storage.cdn_host'  => 'cdn'
    ]);
    $response = $this->postJson('v3/startup', [], [
      'HTTP_X-Version'  => '0.0.19', 
      'HTTP_X-Language' => 'hi',
      'HTTP_X-AppName'  => 'singplus',
    ]);
    $response->assertJson([
      'code' => 0,
      'data' => [
        'logged'  => false,
        'user'    => null,
        'update'  => null,
        'update'  => [
          'isUrlForce'      => true,
          'recommendUpdate' => false,
          'forceUpdate'     => false,
          'updateTips'      => null,
        ],
        'ads'       => [],
      ],
    ]);
  }

  //=================================
  //      getUserCommonInfo
  //=================================
  public function testGetUserCommonInfoSuccess()
  {
    $this->expectsEvents(\SingPlus\Events\Startups\CommonInfoFetched::class);
    $user = factory(\SingPlus\Domains\Users\Models\User::class)->create();

    factory(\SingPlus\Domains\Feeds\Models\Feed::class)->create([
      'user_id' => $user->id,
      'type'    => 'work_favourite',
      'status'  => 1,
      'is_read' => 0,
    ]);
    factory(\SingPlus\Domains\Feeds\Models\Feed::class)->create([
      'user_id' => $user->id,
      'type'    => 'work_favourite',
      'status'  => 1,
      'is_read' => 0,
    ]);
    factory(\SingPlus\Domains\Feeds\Models\Feed::class)->create([
      'user_id' => $user->id,
      'type'    => 'work_favourite',
      'status'  => 1,
      'is_read' => 1,
    ]);
    factory(\SingPlus\Domains\Feeds\Models\Feed::class)->create([
      'user_id' => $user->id,
      'type'    => 'work_transmit',
      'status'  => 1,
      'is_read' => 0,
    ]);
    factory(\SingPlus\Domains\Feeds\Models\Feed::class)->create([
      'user_id' => $user->id,
      'type'    => 'work_comment',
      'status'  => 1,
      'is_read' => 0,
    ]);
    factory(\SingPlus\Domains\Feeds\Models\Feed::class)->create([
      'user_id' => $user->id,
      'type'    => 'user_followed',
      'status'  => 1,
      'is_read' => 0,
    ]);

    factory(\SingPlus\Domains\Feeds\Models\Feed::class)->create([
          'user_id' => $user->id,
          'type'    => 'gift_send_for_work',
          'status'  => 1,
          'is_read' => 0,
    ]);

    $response = $this->actingAs($user)
                     ->getJson('v3/user/common-info')
                     ->assertJson(['code' => 0]);

    $response = json_decode($response->getContent());
    self::assertCount(1, $response->data->bindTopics);
    self::assertTrue(in_array('topic_all_client', $response->data->bindTopics));
    $feedCounts = (array) $response->data->feedCounts;
    ksort($feedCounts);
    self::assertEquals([
      'workComment'     => 1,
      'workFavourite'   => 2,
      'workTransmit'    => 1,
      'follower'        => 1,
      'workChorusJoin'  => 0,
      'gift'            => 1
    ], $feedCounts);
  }

  public function testGetUserCommonInfoSuccess_ReceiveCountryAbbreviationFromProfile()
  {
    $this->enableNationOperationMiddleware();
    config([
      'nationality.operation_country_abbr'  => ['TZ'],
    ]);

    $this->expectsEvents(\SingPlus\Events\Startups\CommonInfoFetched::class);
    $user = factory(\SingPlus\Domains\Users\Models\User::class)->create();
    factory(\SingPlus\Domains\Users\Models\UserProfile::class)->create([
      'user_id'   => $user->id, 
      'location'  => [
        'abbreviation'  => 'CN',
      ],
    ]);

    $response = $this->actingAs($user)
                     ->getJson('v3/user/common-info')
                     ->assertJson(['code' => 0]);
    $response->assertHeader('X-CountryAbbr', '-*');
    $response->assertHeader('X-RealCountryAbbr', 'CN');

    $response = json_decode($response->getContent());
    self::assertTrue(in_array('topic_all_client', $response->data->bindTopics));
    self::assertTrue(in_array('topic_country_cn', $response->data->bindTopics));
    $feedCounts = (array) $response->data->feedCounts;
    ksort($feedCounts);
    self::assertEquals([
      'workComment'     => 0,
      'workFavourite'   => 0,
      'workTransmit'    => 0,
      'follower'        => 0,
      'workChorusJoin'  => 0,
      'gift'            => 0,
    ], $feedCounts);
  }

  public function testGetUserCommonInfoSuccess_ReceiveCountryAbbreviation()
  {
    $this->enableNationOperationMiddleware();
    config([
      'nationality.operation_country_abbr'  => ['TZ'],
    ]);
    $this->expectsEvents(\SingPlus\Events\Startups\CommonInfoFetched::class);
    $user = factory(\SingPlus\Domains\Users\Models\User::class)->create();
    factory(\SingPlus\Domains\Users\Models\UserProfile::class)->create([
      'user_id'   => $user->id, 
      'location'  => [
        'abbreviation'  => 'CN',
      ],
    ]);

    $response = $this->actingAs($user)
                     ->getJson('v3/user/common-info', [
                      'X-CountryAbbr' => 'IN',
                     ]);
    $response->assertJson(['code' => 0]);
    self::assertEquals('-*', $response->headers->get('X-CountryAbbr'));
    // 由于用户report location存在，因此忽略X-CountryAbbr request header
    self::assertEquals('CN', $response->headers->get('X-RealCountryAbbr'));

    $response = json_decode($response->getContent());
    self::assertTrue(in_array('topic_all_client', $response->data->bindTopics));
    self::assertTrue(in_array('topic_country_cn', $response->data->bindTopics));
    $feedCounts = (array) $response->data->feedCounts;
    ksort($feedCounts);
    self::assertEquals([
      'workComment'     => 0,
      'workFavourite'   => 0,
      'workTransmit'    => 0,
      'follower'        => 0,
      'workChorusJoin'  => 0,
      'gift'            => 0,
    ], $feedCounts);
  }

  public function testGetUserCommonInfoSuccess_ReceiveCountryAbbreviation_ReportLocationMissed()
  {
    $this->enableNationOperationMiddleware();
    config([
      'nationality.operation_country_abbr'  => ['TZ'],
    ]);
    $this->expectsEvents(\SingPlus\Events\Startups\CommonInfoFetched::class);
    $user = factory(\SingPlus\Domains\Users\Models\User::class)->create();
    factory(\SingPlus\Domains\Users\Models\UserProfile::class)->create([
      'user_id'   => $user->id, 
    ]);

    $response = $this->actingAs($user)
                     ->getJson('v3/user/common-info', [
                      'X-CountryAbbr' => 'IN', 
                     ]);
    $response->assertJson(['code' => 0]);
    self::assertEquals('-*', $response->headers->get('X-CountryAbbr'));
    self::assertEquals('IN', $response->headers->get('X-RealCountryAbbr'));

    $response = json_decode($response->getContent());
    self::assertTrue(in_array('topic_all_client', $response->data->bindTopics));
    self::assertTrue(in_array('topic_country_in', $response->data->bindTopics));
    $feedCounts = (array) $response->data->feedCounts;
    ksort($feedCounts);
    self::assertEquals([
      'workComment'     => 0,
      'workFavourite'   => 0,
      'workTransmit'    => 0,
      'follower'        => 0,
      'workChorusJoin'  => 0,
      'gift'            => 0,
    ], $feedCounts);
  }
}
