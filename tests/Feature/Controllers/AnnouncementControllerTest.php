<?php

namespace FeatureTest\SingPlus\Controllers;

use Cache;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use FeatureTest\SingPlus\TestCase;
use FeatureTest\SingPlus\MongodbClearTrait;
use Mockery;

class AnnouncementControllerTest extends TestCase
{
  use MongodbClearTrait; 

  //=================================
  //        listAnnouncements
  //=================================
  public function testListAnnouncementsSuccess()
  {
    $user = factory(\SingPlus\Domains\Users\Models\User::class)->create();
    factory(\SingPlus\Domains\Users\Models\UserProfile::class)->create([
      'user_id' => $user->id, 
    ]);
    $this->prepareAnnouncements();
    $response = $this->actingAs($user)
                     ->getJson('v3/messages/announcements');
    $response->assertJson(['code' => 0]);

    $announcements = (json_decode($response->getContent()))->data->announcements;
    self::assertCount(3, $announcements);
    self::assertEquals('three', $announcements[0]->title);    // order by display_order desc
    self::assertEquals('1525a49ceefd442799b687a2fe9b1182', $announcements[0]->attributes->musicId);
  }

  public function testListAnnouncementsSuccess_FromCountryOperation()
  {
    $this->enableNationOperationMiddleware();
    config([
      'nationality.operation_country_abbr'  => ['TZ'],
    ]);

    $user = factory(\SingPlus\Domains\Users\Models\User::class)->create();
    factory(\SingPlus\Domains\Users\Models\UserProfile::class)->create([
      'user_id' => $user->id, 
    ]);
    $this->prepareAnnouncements();
    $response = $this->actingAs($user)
                     ->getJson('v3/messages/announcements', [
                        'X-CountryAbbr' => 'IN', 
                     ]);
    $response->assertJson(['code' => 0]);

    $announcements = (json_decode($response->getContent()))->data->announcements;
    self::assertCount(1, $announcements);
    self::assertEquals('two', $announcements[0]->title);    // only -* be fetched
    self::assertEquals('http://music.sing.plus/two', $announcements[0]->attributes->url);
  }

  public function testListAnnouncementsSuccess_FromCountryOperation_FromUserCountryCode()
  {
    $this->enableNationOperationMiddleware();
    config([
      'nationality.operation_country_abbr'  => ['TZ'],
    ]);

    $user = factory(\SingPlus\Domains\Users\Models\User::class)->create([
      'country_code'  => '1', 
    ]);
    factory(\SingPlus\Domains\Users\Models\UserProfile::class)->create([
      'user_id' => $user->id, 
    ]);
    $this->prepareAnnouncements();
    $response = $this->actingAs($user)
                     ->getJson('v3/messages/announcements');
    $response->assertJson(['code' => 0]);
    $response->assertHeader('X-CountryAbbr', '-*');
    $response->assertHeader('X-RealCountryAbbr', 'CA');

    $announcements = (json_decode($response->getContent()))->data->announcements;
    self::assertCount(1, $announcements);
    self::assertEquals('two', $announcements[0]->title);    // only -* be fetched
    self::assertEquals('http://music.sing.plus/two', $announcements[0]->attributes->url);
  }

  public function testListAnnouncementsSuccess_FromCountryOperation_NotMobileUser()
  {
    $this->enableNationOperationMiddleware();
    config([
      'nationality.operation_country_abbr'  => ['TZ'],
    ]);

    $user = factory(\SingPlus\Domains\Users\Models\User::class)->create([
      'country_code'  => '1', 
      'source'        => 'socialite',
    ]);
    factory(\SingPlus\Domains\Users\Models\UserProfile::class)->create([
      'user_id'   => $user->id, 
      'location'  => [
        'abbreviation'  => 'CN',
      ],
    ]);
    $this->prepareAnnouncements();
    $response = $this->actingAs($user)
                     ->getJson('v3/messages/announcements');
    $response->assertJson(['code' => 0]);
    $response->assertHeader('X-CountryAbbr', '-*');
    $response->assertHeader('X-RealCountryAbbr', 'CN');

    $announcements = (json_decode($response->getContent()))->data->announcements;
    self::assertCount(1, $announcements);
    self::assertEquals('two', $announcements[0]->title);    // only -* be fetched
    self::assertEquals('http://music.sing.plus/two', $announcements[0]->attributes->url);
  }

  public function testListAnnouncementsSuccess_NotFirstPage()
  {
    $user = factory(\SingPlus\Domains\Users\Models\User::class)->create();
    factory(\SingPlus\Domains\Users\Models\UserProfile::class)->create([
      'user_id' => $user->id, 
    ]);
    $data = $this->prepareAnnouncements();
    $response = $this->actingAs($user)
                     ->getJson('v3/messages/announcements?' . http_build_query([
                        'announcementId'  => $data->three->id, 
                        'isNext'          => 1,
                        'size'            => 1,
                     ]));
    $response->assertJson(['code' => 0]);
    $announcements = (json_decode($response->getContent()))->data->announcements;
    self::assertCount(1, $announcements);
    self::assertEquals('two', $announcements[0]->title);    // order by display_order desc
    self::assertEquals('http://music.sing.plus/two', $announcements[0]->attributes->url);
  }

  public function testListAnnouncementsFailed_VersionBeDeprecated()
  {
    $user = factory(\SingPlus\Domains\Users\Models\User::class)->create();
    factory(\SingPlus\Domains\Users\Models\UserProfile::class)->create([
      'user_id' => $user->id, 
    ]);
    factory(\SingPlus\Domains\ClientSupports\Models\VersionUpdate::class)->create([
      'latest'  => '0.0.10',
      'force'   => '0.0.3',
    ]);
    $data = $this->prepareAnnouncements();
    $this->enableVersionCheckMiddleware();
    $response = $this->actingAs($user)
                     ->getJson('v3/messages/announcements?' . http_build_query([
                        'announcementId'  => $data->three->id, 
                        'isNext'          => 1,
                        'size'            => 1,
                     ]), [
                        'HTTP_X-Version'  => '0.0.2', 
                     ]);
    $response->assertJson(['code' => 10020]);
  }

  public function testListAnnouncementsFailed_VersionNotProvide()
  {
    $user = factory(\SingPlus\Domains\Users\Models\User::class)->create();
    factory(\SingPlus\Domains\Users\Models\UserProfile::class)->create([
      'user_id' => $user->id, 
    ]);
    factory(\SingPlus\Domains\ClientSupports\Models\VersionUpdate::class)->create([
      'latest'  => '0.0.0',
      'force'   => '0.0.0',
    ]);
    $data = $this->prepareAnnouncements();
    $this->enableVersionCheckMiddleware();
    $response = $this->actingAs($user)
                     ->getJson('v3/messages/announcements?' . http_build_query([
                        'announcementId'  => $data->three->id, 
                        'isNext'          => 1,
                        'size'            => 1,
                     ]));
    //$response->assertJson(['code' => 10020]);
    $response->assertJson(['code' => 0]);
  }

  private function prepareAnnouncements()
  {
    $one = factory(\SingPlus\Domains\Announcements\Models\Announcement::class)->create([
      'title'   => 'one',
      'cover'   => 'one-cover',
      'summary' => 'summary one',
      'type'    => \SingPlus\Contracts\Announcements\Constants\Announcement::TYPE_URL,
      'attributes'  => [
                        'url' => 'http://music.sing.plus/one',
                      ],
      'status'  => 1,
      'display_order' => 100,
      'country_abbr'  => 'TZ',
    ]);
    $two = factory(\SingPlus\Domains\Announcements\Models\Announcement::class)->create([
      'title'   => 'two',
      'cover'   => 'two-cover',
      'summary' => 'summary two',
      'type'    => \SingPlus\Contracts\Announcements\Constants\Announcement::TYPE_URL,
      'attributes'  => [
                        'url' => 'http://music.sing.plus/two',
                      ],
      'status'  => 1,
      'display_order' => 200,
      'country_abbr'  => '-*',
    ]);
    $three = factory(\SingPlus\Domains\Announcements\Models\Announcement::class)->create([
      'title'   => 'three',
      'cover'   => 'three-cover',
      'summary' => 'summary three',
      'type'    => \SingPlus\Contracts\Announcements\Constants\Announcement::TYPE_MUSIC,
      'attributes'  => [
                          'musicId' => '1525a49ceefd442799b687a2fe9b1182',
                      ],
      'status'  => 1,
      'display_order' => 300,
      'country_abbr'  => 'IN',
    ]);

    return (object) [
      'one'   => $one,
      'two'   => $two,
      'three' => $three,
    ];
  }
}
