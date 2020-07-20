<?php

namespace FeatureTest\SingPlus\Controllers\Auth;

use GuzzleHttp\Client;
use GuzzleHttp\Handler\MockHandler;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Psr7\Response;
use Cache;
use Artisan;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use FeatureTest\SingPlus\TestCase;
use FeatureTest\SingPlus\MongodbClearTrait;
use Mockery;
use SingPlus\Support\Helpers\Str;

class MusicControllerTest extends TestCase
{
  use MongodbClearTrait; 

  //=================================
  //        getRecommends
  //=================================
  public function testRecommendsSuccess()
  {
    $user = factory(\SingPlus\Domains\Users\Models\User::class)->create();
    factory(\SingPlus\Domains\Users\Models\UserProfile::class)->create([
      'user_id' => $user->id, 
    ]);

    $artistOne = factory(\SingPlus\Domains\Musics\Models\Artist::class)->create();
    $artistTwo = factory(\SingPlus\Domains\Musics\Models\Artist::class)->create();
    $musicOne = factory(\SingPlus\Domains\Musics\Models\Music::class)->create([
      //'artists'     => [$artistOne->id],
      'created_at'  => \Carbon\Carbon::yesterday(),
    ]);
    $musicTwo = factory(\SingPlus\Domains\Musics\Models\Music::class)->create([
      'artists' => [$artistOne->id, $artistTwo->id],
      'created_at'  => \Carbon\Carbon::today(),
      'resource'  => [
        'zip' => 'https://s3.sing.plus/musics/abced.zip',
        'raw' => [
          'mimetype'  => 'audio/mpeg',
          'size'      => '12345678',
        ],
        'accompaniment' => [
          'mimetype'  => 'audio/mpeg',
          'size'      => '23456789',
        ],
        'size'        => '789789789',
      ],
    ]);
    $recommendOne = factory(\SingPlus\Domains\Musics\Models\MusicRecommend::class)->create([
      'music_id'      => $musicOne->id,
      'display_order' => 100,
    ]);
    $recommendTwo = factory(\SingPlus\Domains\Musics\Models\MusicRecommend::class)->create([
      'music_id'      => $musicTwo->id,
      'display_order' => 200,
    ]);

    Cache::shouldReceive('get')
         ->once()
         ->with(sprintf('music:%s:reqnum', $recommendOne->music_id))
         ->andReturn(18);
    Cache::shouldReceive('get')
         ->once()
         ->with(sprintf('music:%s:reqnum', $recommendTwo->music_id))
         ->andReturn(138);

    $response = $this->actingAs($user)
                     ->getJson('v3/musics/recommends');
    $response->assertJson(['code' => 0]);

    $response = json_decode($response->getContent());
    $musics = $response->data->musics;
    self::assertCount(2, $musics);
    self::assertEquals($recommendTwo->id, $musics[0]->id);    // order by recommend's display order
    self::assertEquals($musicTwo->id, $musics[0]->musicId);
    self::assertTrue(starts_with($musics[0]->size, '753'));
    self::assertEquals($artistOne->name . " " . $artistTwo->name, $musics[0]->artists);
    self::assertEmpty($musics[1]->artists);       // music without artists
    self::assertEquals(138, $musics[0]->requestNum);
    self::assertEquals(18, $musics[1]->requestNum);
  }

  public function testRecommendsSuccess_CountryOperation()
  {
    $this->enableNationOperationMiddleware();
    config([
      'nationality.operation_country_abbr'  => ['TZ'],
    ]);
    $user = factory(\SingPlus\Domains\Users\Models\User::class)->create();
    factory(\SingPlus\Domains\Users\Models\UserProfile::class)->create([
      'user_id' => $user->id, 
    ]);

    $artistOne = factory(\SingPlus\Domains\Musics\Models\Artist::class)->create();
    $artistTwo = factory(\SingPlus\Domains\Musics\Models\Artist::class)->create();
    $musicOne = factory(\SingPlus\Domains\Musics\Models\Music::class)->create([
      //'artists'     => [$artistOne->id],
      'created_at'  => \Carbon\Carbon::yesterday(),
    ]);
    $musicTwo = factory(\SingPlus\Domains\Musics\Models\Music::class)->create([
      'artists' => [$artistOne->id, $artistTwo->id],
      'created_at'  => \Carbon\Carbon::today(),
      'resource'  => [
        'zip' => 'https://s3.sing.plus/musics/abced.zip',
        'raw' => [
          'mimetype'  => 'audio/mpeg',
          'size'      => '12345678',
        ],
        'accompaniment' => [
          'mimetype'  => 'audio/mpeg',
          'size'      => '23456789',
        ],
        'size'        => '789789789',
      ],
    ]);
    $recommendOne = factory(\SingPlus\Domains\Musics\Models\MusicRecommend::class)->create([
      'country_abbr'  => '-*',
      'music_id'      => $musicOne->id,
      'display_order' => 100,
    ]);
    $recommendTwo = factory(\SingPlus\Domains\Musics\Models\MusicRecommend::class)->create([
      'country_abbr'  => 'TZ',
      'music_id'      => $musicTwo->id,
      'display_order' => 200,
    ]);

    Cache::shouldReceive('get')
         ->once()
         ->with(sprintf('music:%s:reqnum', $recommendOne->music_id))
         ->andReturn(18);
    Cache::shouldReceive('get')
         ->never()
         ->with(sprintf('music:%s:reqnum', $recommendTwo->music_id));

    $response = $this->actingAs($user)
                     ->getJson('v3/musics/recommends', [
                        'X-CountryAbbr' => 'IN', 
                     ]);
    $response->assertJson(['code' => 0]);

    $response = json_decode($response->getContent());
    $musics = $response->data->musics;
    self::assertCount(1, $musics);
    // IN not in config, -* fetched
    self::assertEquals($recommendOne->id, $musics[0]->id);
    self::assertEquals($musicOne->id, $musics[0]->musicId);
  }

  public function testRecommendsSuccess_WithoutLogin()
  {
      $artistOne = factory(\SingPlus\Domains\Musics\Models\Artist::class)->create();
      $artistTwo = factory(\SingPlus\Domains\Musics\Models\Artist::class)->create();
      $musicOne = factory(\SingPlus\Domains\Musics\Models\Music::class)->create([
          //'artists'     => [$artistOne->id],
          'created_at'  => \Carbon\Carbon::yesterday(),
      ]);
      $musicTwo = factory(\SingPlus\Domains\Musics\Models\Music::class)->create([
          'artists' => [$artistOne->id, $artistTwo->id],
          'created_at'  => \Carbon\Carbon::today(),
          'resource'  => [
              'zip' => 'https://s3.sing.plus/musics/abced.zip',
              'raw' => [
                  'mimetype'  => 'audio/mpeg',
                  'size'      => '12345678',
              ],
              'accompaniment' => [
                  'mimetype'  => 'audio/mpeg',
                  'size'      => '23456789',
              ],
              'size'        => '789789789',
          ],
      ]);
      $recommendOne = factory(\SingPlus\Domains\Musics\Models\MusicRecommend::class)->create([
          'music_id'      => $musicOne->id,
          'display_order' => 100,
      ]);
      $recommendTwo = factory(\SingPlus\Domains\Musics\Models\MusicRecommend::class)->create([
          'music_id'      => $musicTwo->id,
          'display_order' => 200,
      ]);

      Cache::shouldReceive('get')
          ->once()
          ->with(sprintf('music:%s:reqnum', $recommendOne->music_id))
          ->andReturn(18);
      Cache::shouldReceive('get')
          ->once()
          ->with(sprintf('music:%s:reqnum', $recommendTwo->music_id))
          ->andReturn(138);

      $response = $this->getJson('v3/musics/recommends');
      $response->assertJson(['code' => 0]);

      $response = json_decode($response->getContent());
      $musics = $response->data->musics;
      self::assertCount(2, $musics);
      self::assertEquals($recommendTwo->id, $musics[0]->id);    // order by recommend's display order
      self::assertEquals($musicTwo->id, $musics[0]->musicId);
      self::assertTrue(starts_with($musics[0]->size, '753'));
      self::assertEquals($artistOne->name . " " . $artistTwo->name, $musics[0]->artists);
      self::assertEmpty($musics[1]->artists);       // music without artists
      self::assertEquals(138, $musics[0]->requestNum);
      self::assertEquals(18, $musics[1]->requestNum);
  }

    public function testRecommendsSuccess_CountryOperation_WithoutLogin()
    {
        $this->enableNationOperationMiddleware();
        config([
            'nationality.operation_country_abbr'  => ['TZ'],
        ]);

        $artistOne = factory(\SingPlus\Domains\Musics\Models\Artist::class)->create();
        $artistTwo = factory(\SingPlus\Domains\Musics\Models\Artist::class)->create();
        $musicOne = factory(\SingPlus\Domains\Musics\Models\Music::class)->create([
            //'artists'     => [$artistOne->id],
            'created_at'  => \Carbon\Carbon::yesterday(),
        ]);
        $musicTwo = factory(\SingPlus\Domains\Musics\Models\Music::class)->create([
            'artists' => [$artistOne->id, $artistTwo->id],
            'created_at'  => \Carbon\Carbon::today(),
            'resource'  => [
                'zip' => 'https://s3.sing.plus/musics/abced.zip',
                'raw' => [
                    'mimetype'  => 'audio/mpeg',
                    'size'      => '12345678',
                ],
                'accompaniment' => [
                    'mimetype'  => 'audio/mpeg',
                    'size'      => '23456789',
                ],
                'size'        => '789789789',
            ],
        ]);
        $recommendOne = factory(\SingPlus\Domains\Musics\Models\MusicRecommend::class)->create([
            'country_abbr'  => '-*',
            'music_id'      => $musicOne->id,
            'display_order' => 100,
        ]);
        $recommendTwo = factory(\SingPlus\Domains\Musics\Models\MusicRecommend::class)->create([
            'country_abbr'  => 'TZ',
            'music_id'      => $musicTwo->id,
            'display_order' => 200,
        ]);

        Cache::shouldReceive('get')
            ->once()
            ->with(sprintf('music:%s:reqnum', $recommendOne->music_id))
            ->andReturn(18);
        Cache::shouldReceive('get')
            ->never()
            ->with(sprintf('music:%s:reqnum', $recommendTwo->music_id));

        $response = $this->getJson('v3/musics/recommends', [
                'X-CountryAbbr' => 'IN',
            ]);
        $response->assertJson(['code' => 0]);

        $response = json_decode($response->getContent());
        $musics = $response->data->musics;
        self::assertCount(1, $musics);
        // IN not in config, -* fetched
        self::assertEquals($recommendOne->id, $musics[0]->id);
        self::assertEquals($musicOne->id, $musics[0]->musicId);
    }

  //=================================
  //        getRecommendMusicSheet
  //=================================
  public function testGetRecommendMusicSheetSuccess()
  {
    $user = factory(\SingPlus\Domains\Users\Models\User::class)->create();
    factory(\SingPlus\Domains\Users\Models\UserProfile::class)->create([
      'user_id' => $user->id, 
    ]);

    $artistOne = factory(\SingPlus\Domains\Musics\Models\Artist::class)->create();
    $artistTwo = factory(\SingPlus\Domains\Musics\Models\Artist::class)->create();
    $musicOne = factory(\SingPlus\Domains\Musics\Models\Music::class)->create([
      'artists'     => [$artistOne->id],
      'created_at'  => \Carbon\Carbon::yesterday(),
      'display_order' => 100,
    ]);
    $musicTwo = factory(\SingPlus\Domains\Musics\Models\Music::class)->create([
      'artists' => [$artistOne->id, $artistTwo->id],
      'created_at'  => \Carbon\Carbon::today(),
      'display_order' => 200,
    ]);
    $musicThree = factory(\SingPlus\Domains\Musics\Models\Music::class)->create([
      'artists' => [$artistTwo->id],
      'created_at'  => \Carbon\Carbon::today(),
      'display_order' => 300,
    ]);

    $sheet = factory(\SingPlus\Domains\Musics\Models\RecommendMusicSheet::class)->create([
      'title'         => 'hello',
      'cover'         => ['sheet-cover', 'sheet-cover-two'],
      'request_count' => 123,
      'music_ids'     => [
        $musicOne->id, $musicTwo->id, $musicThree->id,
      ],
      'status'        => 1,
    ]);

    Cache::shouldReceive('get')
         ->once()
         ->with(sprintf('music:%s:reqnum', $musicOne->id))
         ->andReturn(138);
    Cache::shouldReceive('get')
         ->once()
         ->with(sprintf('music:%s:reqnum', $musicTwo->id))
         ->andReturn(1888);

    Cache::shouldReceive('get')
         ->once()
         ->with(sprintf('music:%s:reqnum', $musicThree->id))
         ->andReturn(20);

    config([
      'filesystems.disks.s3.region'  => 'eu-central-1',
      'filesystems.disks.s3.bucket'  => 'sing-plus',
    ]);

    $response = $this->actingAs($user)
                     ->getJson('v3/musics/sheet/' . $sheet->id);
    $response->assertJson(['code' => 0]);
    $sheet = (json_decode($response->getContent()))->data->sheet;
    self::assertNotNull($sheet);
    self::assertCount(2, $sheet->cover);
    self::assertContains('https://sing-plus.s3.eu-central-1.amazonaws.com/sheet-cover-two', $sheet->cover);
    self::assertEquals('hello', $sheet->title);
    self::assertEquals(123, $sheet->requestNum);
    self::assertCount(3, $sheet->musics);
  }

  public function testGetRecommendMusicSheetSuccess_SheetNotExist()
  {
    $user = factory(\SingPlus\Domains\Users\Models\User::class)->create();
    factory(\SingPlus\Domains\Users\Models\UserProfile::class)->create([
      'user_id' => $user->id, 
    ]);

    $response = $this->actingAs($user)
                     ->getJson('v3/musics/sheet/58b464d2a95f474f880df8352acddfbd')
                     ->assertJson([
                        'code'  => 0,
                        'data'  => [
                          'sheet' => null,
                        ],
                     ]);
  }

  public function testGetRecommendMusicSheetSuccess_NoMusics()
  {
    $user = factory(\SingPlus\Domains\Users\Models\User::class)->create();
    factory(\SingPlus\Domains\Users\Models\UserProfile::class)->create([
      'user_id' => $user->id, 
    ]);
    $sheet = factory(\SingPlus\Domains\Musics\Models\RecommendMusicSheet::class)->create([
      'title'         => 'hello',
      'cover'         => ['sheet-cover'],
      'request_count' => 123,
      'music_ids'     => [],
      'status'        => 1,
    ]);

    config([
      'filesystems.disks.s3.region'  => 'eu-central-1',
      'filesystems.disks.s3.bucket'  => 'sing-plus',
    ]);

    $response = $this->actingAs($user)
                     ->getJson('v3/musics/sheet/' . $sheet->id)
                     ->assertJson([
                        'code'  => 0,
                        'data'  => [
                          'sheet' => [
                            'title' => 'hello',
                            'cover' => ['https://sing-plus.s3.eu-central-1.amazonaws.com/sheet-cover'],
                            'requestNum'  => 123,
                            'musics'  => [],
                          ],
                        ],
                     ]);
  }

  public function testGetRecommendMusicSheetSuccess_WithoutLogin()
  {
      $artistOne = factory(\SingPlus\Domains\Musics\Models\Artist::class)->create();
      $artistTwo = factory(\SingPlus\Domains\Musics\Models\Artist::class)->create();
      $musicOne = factory(\SingPlus\Domains\Musics\Models\Music::class)->create([
          'artists'     => [$artistOne->id],
          'created_at'  => \Carbon\Carbon::yesterday(),
          'display_order' => 100,
      ]);
      $musicTwo = factory(\SingPlus\Domains\Musics\Models\Music::class)->create([
          'artists' => [$artistOne->id, $artistTwo->id],
          'created_at'  => \Carbon\Carbon::today(),
          'display_order' => 200,
      ]);
      $musicThree = factory(\SingPlus\Domains\Musics\Models\Music::class)->create([
          'artists' => [$artistTwo->id],
          'created_at'  => \Carbon\Carbon::today(),
          'display_order' => 300,
      ]);

      $sheet = factory(\SingPlus\Domains\Musics\Models\RecommendMusicSheet::class)->create([
          'title'         => 'hello',
          'cover'         => ['sheet-cover', 'sheet-cover-two'],
          'request_count' => 123,
          'music_ids'     => [
              $musicOne->id, $musicTwo->id, $musicThree->id,
          ],
          'status'        => 1,
      ]);

      Cache::shouldReceive('get')
          ->once()
          ->with(sprintf('music:%s:reqnum', $musicOne->id))
          ->andReturn(138);
      Cache::shouldReceive('get')
          ->once()
          ->with(sprintf('music:%s:reqnum', $musicTwo->id))
          ->andReturn(1888);

      Cache::shouldReceive('get')
          ->once()
          ->with(sprintf('music:%s:reqnum', $musicThree->id))
          ->andReturn(20);

      config([
          'filesystems.disks.s3.region'  => 'eu-central-1',
          'filesystems.disks.s3.bucket'  => 'sing-plus',
      ]);

      $response = $this->getJson('v3/musics/sheet/' . $sheet->id);
      $response->assertJson(['code' => 0]);
      $sheet = (json_decode($response->getContent()))->data->sheet;
      self::assertNotNull($sheet);
      self::assertCount(2, $sheet->cover);
      self::assertContains('https://sing-plus.s3.eu-central-1.amazonaws.com/sheet-cover-two', $sheet->cover);
      self::assertEquals('hello', $sheet->title);
      self::assertEquals(123, $sheet->requestNum);
      self::assertCount(3, $sheet->musics);
  }

    public function testGetRecommendMusicSheetSuccess_SheetNotExist_WithoutLogin()
    {

        $response = $this->getJson('v3/musics/sheet/58b464d2a95f474f880df8352acddfbd')
            ->assertJson([
                'code'  => 0,
                'data'  => [
                    'sheet' => null,
                ],
            ]);
    }

    public function testGetRecommendMusicSheetSuccess_NoMusics_WithoutLogin()
    {
        $sheet = factory(\SingPlus\Domains\Musics\Models\RecommendMusicSheet::class)->create([
            'title'         => 'hello',
            'cover'         => ['sheet-cover'],
            'request_count' => 123,
            'music_ids'     => [],
            'status'        => 1,
        ]);

        config([
            'filesystems.disks.s3.region'  => 'eu-central-1',
            'filesystems.disks.s3.bucket'  => 'sing-plus',
        ]);

        $response = $this->getJson('v3/musics/sheet/' . $sheet->id)
            ->assertJson([
                'code'  => 0,
                'data'  => [
                    'sheet' => [
                        'title' => 'hello',
                        'cover' => ['https://sing-plus.s3.eu-central-1.amazonaws.com/sheet-cover'],
                        'requestNum'  => 123,
                        'musics'  => [],
                    ],
                ],
            ]);
    }

  //=================================
  //        getHots
  //=================================
  public function testGetHotsSuccess()
  {
    $user = factory(\SingPlus\Domains\Users\Models\User::class)->create();
    factory(\SingPlus\Domains\Users\Models\UserProfile::class)->create([
      'user_id' => $user->id, 
    ]);

    $artistOne = factory(\SingPlus\Domains\Musics\Models\Artist::class)->create();
    $artistTwo = factory(\SingPlus\Domains\Musics\Models\Artist::class)->create();
    $musicOne = factory(\SingPlus\Domains\Musics\Models\Music::class)->create([
      'artists'     => [$artistOne->id],
      'created_at'  => \Carbon\Carbon::yesterday(),
    ]);
    $musicTwo = factory(\SingPlus\Domains\Musics\Models\Music::class)->create([
      'artists' => [$artistOne->id, $artistTwo->id],
      'created_at'  => \Carbon\Carbon::today(),
    ]);
    $musicThree = factory(\SingPlus\Domains\Musics\Models\Music::class)->create([
      'artists' => [$artistTwo->id],
      'created_at'  => \Carbon\Carbon::today(),
    ]);

    $hotOne = factory(\SingPlus\Domains\Musics\Models\MusicHot::class)->create([
      'music_id'      => $musicOne->id,
      'display_order' => 100,
    ]);
    $hotTwo = factory(\SingPlus\Domains\Musics\Models\MusicHot::class)->create([
      'music_id'      => $musicTwo->id,
      'display_order' => 200,
    ]);
    $hotThree = factory(\SingPlus\Domains\Musics\Models\MusicHot::class)->create([
      'music_id'      => $musicThree->id,
      'display_order' => 300,
    ]);

    Cache::shouldReceive('get')
         ->once()
         ->with(sprintf('music:%s:reqnum', $hotTwo->music_id))
         ->andReturn(138);
    Cache::shouldReceive('get')
         ->once()
         ->with(sprintf('music:%s:reqnum', $hotThree->music_id))
         ->andReturn(1888);

    $response = $this->actingAs($user)
                     ->getJson('v3/musics/hots?' . http_build_query([
                      'size'  => 2,
                     ]));
    $response->assertJson(['code' => 0]);
    $response = json_decode($response->getContent());
    $musics = $response->data->musics;
    self::assertCount(2, $musics);
    self::assertEquals($hotThree->id, $musics[0]->id);    // order by hot display order
    self::assertEquals($musicThree->id, $musics[0]->musicId);
    self::assertEquals($artistTwo->name, $musics[0]->artists);
    self::assertEquals($hotTwo->id, $musics[1]->id);
    self::assertEquals($artistOne->name . " " . $artistTwo->name, $musics[1]->artists);
    self::assertEquals(1888, $musics[0]->requestNum);
  }

  public function testGetHotsSuccess_FromCountryOperation()
  {
    $this->enableNationOperationMiddleware();
    config([
      'nationality.operation_country_abbr'  => ['TZ'],
    ]);

    $user = factory(\SingPlus\Domains\Users\Models\User::class)->create();
    factory(\SingPlus\Domains\Users\Models\UserProfile::class)->create([
      'user_id' => $user->id, 
    ]);

    $artistOne = factory(\SingPlus\Domains\Musics\Models\Artist::class)->create();
    $artistTwo = factory(\SingPlus\Domains\Musics\Models\Artist::class)->create();
    $musicOne = factory(\SingPlus\Domains\Musics\Models\Music::class)->create([
      'artists'     => [$artistOne->id],
      'created_at'  => \Carbon\Carbon::yesterday(),
    ]);
    $musicTwo = factory(\SingPlus\Domains\Musics\Models\Music::class)->create([
      'artists' => [$artistOne->id, $artistTwo->id],
      'created_at'  => \Carbon\Carbon::today(),
    ]);
    $musicThree = factory(\SingPlus\Domains\Musics\Models\Music::class)->create([
      'artists' => [$artistTwo->id],
      'created_at'  => \Carbon\Carbon::today(),
    ]);

    $hotOne = factory(\SingPlus\Domains\Musics\Models\MusicHot::class)->create([
      'music_id'      => $musicOne->id,
      'display_order' => 100,
      'country_abbr'  => 'IN',
    ]);
    $hotTwo = factory(\SingPlus\Domains\Musics\Models\MusicHot::class)->create([
      'music_id'      => $musicTwo->id,
      'display_order' => 200,
      'country_abbr'  => '-*',
    ]);
    $hotThree = factory(\SingPlus\Domains\Musics\Models\MusicHot::class)->create([
      'music_id'      => $musicThree->id,
      'display_order' => 300,
      'country_abbr'  => 'TZ',
    ]);

    Cache::shouldReceive('get')
         ->once()
         ->with(sprintf('music:%s:reqnum', $hotThree->music_id))
         ->andReturn(138);

    $response = $this->actingAs($user)
                     ->getJson('v3/musics/hots?' . http_build_query([
                      'size'  => 2,
                     ]), [
                        'X-CountryAbbr' => 'TZ', 
                     ]);
    $response->assertJson(['code' => 0]);
    $response = json_decode($response->getContent());
    $musics = $response->data->musics;
    self::assertCount(1, $musics);
    self::assertEquals($hotThree->id, $musics[0]->id);
  }

  public function testGetHotsSuccess_SpecifyHotId()
  {
    $user = factory(\SingPlus\Domains\Users\Models\User::class)->create();
    factory(\SingPlus\Domains\Users\Models\UserProfile::class)->create([
      'user_id' => $user->id, 
    ]);

    $artistOne = factory(\SingPlus\Domains\Musics\Models\Artist::class)->create();
    $artistTwo = factory(\SingPlus\Domains\Musics\Models\Artist::class)->create();
    $musicOne = factory(\SingPlus\Domains\Musics\Models\Music::class)->create([
      'artists'     => [$artistOne->id],
      'created_at'  => \Carbon\Carbon::yesterday(),
    ]);
    $musicTwo = factory(\SingPlus\Domains\Musics\Models\Music::class)->create([
      'artists' => [$artistOne->id, $artistTwo->id],
      'created_at'  => \Carbon\Carbon::today(),
    ]);
    $musicThree = factory(\SingPlus\Domains\Musics\Models\Music::class)->create([
      'artists' => [$artistTwo->id],
      'created_at'  => \Carbon\Carbon::today(),
    ]);

    $hotOne = factory(\SingPlus\Domains\Musics\Models\MusicHot::class)->create([
      'music_id'      => $musicOne->id,
      'display_order' => 100,
    ]);
    $hotTwo = factory(\SingPlus\Domains\Musics\Models\MusicHot::class)->create([
      'music_id'      => $musicTwo->id,
      'display_order' => 200,
    ]);
    $hotThree = factory(\SingPlus\Domains\Musics\Models\MusicHot::class)->create([
      'music_id'      => $musicThree->id,
      'display_order' => 300,
    ]);

    Cache::shouldReceive('get')
         ->once()
         ->with(sprintf('music:%s:reqnum', $hotOne->music_id))
         ->andReturn(1888);

    $response = $this->actingAs($user)
                     ->getJson('v3/musics/hots?' . http_build_query([
                      'id'    => $hotTwo->id,
                      'size'  => 2,
                     ]));
    $response->assertJson(['code' => 0]);
    $response = json_decode($response->getContent());
    $musics = $response->data->musics;
    self::assertCount(1, $musics);
    self::assertEquals($hotOne->id, $musics[0]->id);    // order by created_at
    self::assertEquals($musicOne->id, $musics[0]->musicId);    // order by created_at
    self::assertEquals($artistOne->name, $musics[0]->artists);
    self::assertEquals(1888, $musics[0]->requestNum);
  }

  public function testGetHotsSuccess_PrevWithoutId()
  {
    $user = factory(\SingPlus\Domains\Users\Models\User::class)->create();
    factory(\SingPlus\Domains\Users\Models\UserProfile::class)->create([
      'user_id' => $user->id, 
    ]);

    $artistOne = factory(\SingPlus\Domains\Musics\Models\Artist::class)->create();
    $artistTwo = factory(\SingPlus\Domains\Musics\Models\Artist::class)->create();
    $musicOne = factory(\SingPlus\Domains\Musics\Models\Music::class)->create([
      'artists'     => [$artistOne->id],
      'created_at'  => \Carbon\Carbon::yesterday(),
    ]);
    $musicTwo = factory(\SingPlus\Domains\Musics\Models\Music::class)->create([
      'artists' => [$artistOne->id, $artistTwo->id],
      'created_at'  => \Carbon\Carbon::today(),
    ]);
    $musicThree = factory(\SingPlus\Domains\Musics\Models\Music::class)->create([
      'artists' => [$artistTwo->id],
      'created_at'  => \Carbon\Carbon::today(),
    ]);

    $hotOne = factory(\SingPlus\Domains\Musics\Models\MusicHot::class)->create([
      'music_id'      => $musicOne->id,
      'display_order' => 100,
    ]);
    $hotTwo = factory(\SingPlus\Domains\Musics\Models\MusicHot::class)->create([
      'music_id'      => $musicTwo->id,
      'display_order' => 200,
    ]);
    $hotThree = factory(\SingPlus\Domains\Musics\Models\MusicHot::class)->create([
      'music_id'      => $musicThree->id,
      'display_order' => 300,
    ]);

    $response = $this->actingAs($user)
                     ->getJson('v3/musics/hots?' . http_build_query([
                      'isNext'  => 0,
                      'size'    => 2,
                     ]));
    $response->assertJson(['code' => 0]);
    $response = json_decode($response->getContent());
    $musics = $response->data->musics;
    self::assertCount(0, $musics);
  }

  public function testGetHotsSuccess_PrevAndSpecifyHotId()
  {
    $user = factory(\SingPlus\Domains\Users\Models\User::class)->create();
    factory(\SingPlus\Domains\Users\Models\UserProfile::class)->create([
      'user_id' => $user->id, 
    ]);

    $artistOne = factory(\SingPlus\Domains\Musics\Models\Artist::class)->create();
    $artistTwo = factory(\SingPlus\Domains\Musics\Models\Artist::class)->create();
    $musicOne = factory(\SingPlus\Domains\Musics\Models\Music::class)->create([
      'artists'     => [$artistOne->id],
      'created_at'  => \Carbon\Carbon::yesterday(),
    ]);
    $musicTwo = factory(\SingPlus\Domains\Musics\Models\Music::class)->create([
      'artists' => [$artistOne->id, $artistTwo->id],
      'created_at'  => \Carbon\Carbon::today(),
    ]);
    $musicThree = factory(\SingPlus\Domains\Musics\Models\Music::class)->create([
      'artists' => [$artistTwo->id],
      'created_at'  => \Carbon\Carbon::today(),
    ]);

    $hotOne = factory(\SingPlus\Domains\Musics\Models\MusicHot::class)->create([
      'music_id'      => $musicOne->id,
      'display_order' => 100,
    ]);
    $hotTwo = factory(\SingPlus\Domains\Musics\Models\MusicHot::class)->create([
      'music_id'      => $musicTwo->id,
      'display_order' => 200,
    ]);
    $hotThree = factory(\SingPlus\Domains\Musics\Models\MusicHot::class)->create([
      'music_id'      => $musicThree->id,
      'display_order' => 300,
    ]);

    Cache::shouldReceive('get')
         ->once()
         ->with(sprintf('music:%s:reqnum', $hotThree->music_id))
         ->andReturn(1888);

    $response = $this->actingAs($user)
                     ->getJson('v3/musics/hots?' . http_build_query([
                      'id'      => $hotTwo->id,
                      'isNext'  => 0,
                      'size'    => 2,
                     ]));
    $response->assertJson(['code' => 0]);
    $response = json_decode($response->getContent());
    $musics = $response->data->musics;
    self::assertCount(1, $musics);
    self::assertEquals($hotThree->id, $musics[0]->id);    // order by created_at
    self::assertEquals($musicThree->id, $musics[0]->musicId);    // order by created_at
    self::assertEquals($artistTwo->name, $musics[0]->artists);
    self::assertEquals(1888, $musics[0]->requestNum);
  }

  public function testGetHotsSuccess_WithoutLogin()
  {
      $artistOne = factory(\SingPlus\Domains\Musics\Models\Artist::class)->create();
      $artistTwo = factory(\SingPlus\Domains\Musics\Models\Artist::class)->create();
      $musicOne = factory(\SingPlus\Domains\Musics\Models\Music::class)->create([
          'artists'     => [$artistOne->id],
          'created_at'  => \Carbon\Carbon::yesterday(),
      ]);
      $musicTwo = factory(\SingPlus\Domains\Musics\Models\Music::class)->create([
          'artists' => [$artistOne->id, $artistTwo->id],
          'created_at'  => \Carbon\Carbon::today(),
      ]);
      $musicThree = factory(\SingPlus\Domains\Musics\Models\Music::class)->create([
          'artists' => [$artistTwo->id],
          'created_at'  => \Carbon\Carbon::today(),
      ]);

      $hotOne = factory(\SingPlus\Domains\Musics\Models\MusicHot::class)->create([
          'music_id'      => $musicOne->id,
          'display_order' => 100,
      ]);
      $hotTwo = factory(\SingPlus\Domains\Musics\Models\MusicHot::class)->create([
          'music_id'      => $musicTwo->id,
          'display_order' => 200,
      ]);
      $hotThree = factory(\SingPlus\Domains\Musics\Models\MusicHot::class)->create([
          'music_id'      => $musicThree->id,
          'display_order' => 300,
      ]);

      Cache::shouldReceive('get')
          ->once()
          ->with(sprintf('music:%s:reqnum', $hotTwo->music_id))
          ->andReturn(138);
      Cache::shouldReceive('get')
          ->once()
          ->with(sprintf('music:%s:reqnum', $hotThree->music_id))
          ->andReturn(1888);

      $response = $this->getJson('v3/musics/hots?' . http_build_query([
                  'size'  => 2,
              ]));
      $response->assertJson(['code' => 0]);
      $response = json_decode($response->getContent());
      $musics = $response->data->musics;
      self::assertCount(2, $musics);
      self::assertEquals($hotThree->id, $musics[0]->id);    // order by hot display order
      self::assertEquals($musicThree->id, $musics[0]->musicId);
      self::assertEquals($artistTwo->name, $musics[0]->artists);
      self::assertEquals($hotTwo->id, $musics[1]->id);
      self::assertEquals($artistOne->name . " " . $artistTwo->name, $musics[1]->artists);
      self::assertEquals(1888, $musics[0]->requestNum);
  }

    public function testGetHotsSuccess_FromCountryOperation_WithoutLogin()
    {
        $this->enableNationOperationMiddleware();
        config([
            'nationality.operation_country_abbr'  => ['TZ'],
        ]);

        $artistOne = factory(\SingPlus\Domains\Musics\Models\Artist::class)->create();
        $artistTwo = factory(\SingPlus\Domains\Musics\Models\Artist::class)->create();
        $musicOne = factory(\SingPlus\Domains\Musics\Models\Music::class)->create([
            'artists'     => [$artistOne->id],
            'created_at'  => \Carbon\Carbon::yesterday(),
        ]);
        $musicTwo = factory(\SingPlus\Domains\Musics\Models\Music::class)->create([
            'artists' => [$artistOne->id, $artistTwo->id],
            'created_at'  => \Carbon\Carbon::today(),
        ]);
        $musicThree = factory(\SingPlus\Domains\Musics\Models\Music::class)->create([
            'artists' => [$artistTwo->id],
            'created_at'  => \Carbon\Carbon::today(),
        ]);

        $hotOne = factory(\SingPlus\Domains\Musics\Models\MusicHot::class)->create([
            'music_id'      => $musicOne->id,
            'display_order' => 100,
            'country_abbr'  => 'IN',
        ]);
        $hotTwo = factory(\SingPlus\Domains\Musics\Models\MusicHot::class)->create([
            'music_id'      => $musicTwo->id,
            'display_order' => 200,
            'country_abbr'  => '-*',
        ]);
        $hotThree = factory(\SingPlus\Domains\Musics\Models\MusicHot::class)->create([
            'music_id'      => $musicThree->id,
            'display_order' => 300,
            'country_abbr'  => 'TZ',
        ]);

        Cache::shouldReceive('get')
            ->once()
            ->with(sprintf('music:%s:reqnum', $hotThree->music_id))
            ->andReturn(138);

        $response = $this->getJson('v3/musics/hots?' . http_build_query([
                    'size'  => 2,
                ]), [
                'X-CountryAbbr' => 'TZ',
            ]);
        $response->assertJson(['code' => 0]);
        $response = json_decode($response->getContent());
        $musics = $response->data->musics;
        self::assertCount(1, $musics);
        self::assertEquals($hotThree->id, $musics[0]->id);
    }

    public function testGetHotsSuccess_SpecifyHotId_WithoutLogin()
    {
        $artistOne = factory(\SingPlus\Domains\Musics\Models\Artist::class)->create();
        $artistTwo = factory(\SingPlus\Domains\Musics\Models\Artist::class)->create();
        $musicOne = factory(\SingPlus\Domains\Musics\Models\Music::class)->create([
            'artists'     => [$artistOne->id],
            'created_at'  => \Carbon\Carbon::yesterday(),
        ]);
        $musicTwo = factory(\SingPlus\Domains\Musics\Models\Music::class)->create([
            'artists' => [$artistOne->id, $artistTwo->id],
            'created_at'  => \Carbon\Carbon::today(),
        ]);
        $musicThree = factory(\SingPlus\Domains\Musics\Models\Music::class)->create([
            'artists' => [$artistTwo->id],
            'created_at'  => \Carbon\Carbon::today(),
        ]);

        $hotOne = factory(\SingPlus\Domains\Musics\Models\MusicHot::class)->create([
            'music_id'      => $musicOne->id,
            'display_order' => 100,
        ]);
        $hotTwo = factory(\SingPlus\Domains\Musics\Models\MusicHot::class)->create([
            'music_id'      => $musicTwo->id,
            'display_order' => 200,
        ]);
        $hotThree = factory(\SingPlus\Domains\Musics\Models\MusicHot::class)->create([
            'music_id'      => $musicThree->id,
            'display_order' => 300,
        ]);

        Cache::shouldReceive('get')
            ->once()
            ->with(sprintf('music:%s:reqnum', $hotOne->music_id))
            ->andReturn(1888);

        $response = $this->getJson('v3/musics/hots?' . http_build_query([
                    'id'    => $hotTwo->id,
                    'size'  => 2,
                ]));
        $response->assertJson(['code' => 0]);
        $response = json_decode($response->getContent());
        $musics = $response->data->musics;
        self::assertCount(1, $musics);
        self::assertEquals($hotOne->id, $musics[0]->id);    // order by created_at
        self::assertEquals($musicOne->id, $musics[0]->musicId);    // order by created_at
        self::assertEquals($artistOne->name, $musics[0]->artists);
        self::assertEquals(1888, $musics[0]->requestNum);
    }

    public function testGetHotsSuccess_PrevWithoutId_WithoutLogin()
    {
        $artistOne = factory(\SingPlus\Domains\Musics\Models\Artist::class)->create();
        $artistTwo = factory(\SingPlus\Domains\Musics\Models\Artist::class)->create();
        $musicOne = factory(\SingPlus\Domains\Musics\Models\Music::class)->create([
            'artists'     => [$artistOne->id],
            'created_at'  => \Carbon\Carbon::yesterday(),
        ]);
        $musicTwo = factory(\SingPlus\Domains\Musics\Models\Music::class)->create([
            'artists' => [$artistOne->id, $artistTwo->id],
            'created_at'  => \Carbon\Carbon::today(),
        ]);
        $musicThree = factory(\SingPlus\Domains\Musics\Models\Music::class)->create([
            'artists' => [$artistTwo->id],
            'created_at'  => \Carbon\Carbon::today(),
        ]);

        $hotOne = factory(\SingPlus\Domains\Musics\Models\MusicHot::class)->create([
            'music_id'      => $musicOne->id,
            'display_order' => 100,
        ]);
        $hotTwo = factory(\SingPlus\Domains\Musics\Models\MusicHot::class)->create([
            'music_id'      => $musicTwo->id,
            'display_order' => 200,
        ]);
        $hotThree = factory(\SingPlus\Domains\Musics\Models\MusicHot::class)->create([
            'music_id'      => $musicThree->id,
            'display_order' => 300,
        ]);

        $response = $this->getJson('v3/musics/hots?' . http_build_query([
                    'isNext'  => 0,
                    'size'    => 2,
                ]));
        $response->assertJson(['code' => 0]);
        $response = json_decode($response->getContent());
        $musics = $response->data->musics;
        self::assertCount(0, $musics);
    }

    public function testGetHotsSuccess_PrevAndSpecifyHotId_WithoutLogin()
    {
        $artistOne = factory(\SingPlus\Domains\Musics\Models\Artist::class)->create();
        $artistTwo = factory(\SingPlus\Domains\Musics\Models\Artist::class)->create();
        $musicOne = factory(\SingPlus\Domains\Musics\Models\Music::class)->create([
            'artists'     => [$artistOne->id],
            'created_at'  => \Carbon\Carbon::yesterday(),
        ]);
        $musicTwo = factory(\SingPlus\Domains\Musics\Models\Music::class)->create([
            'artists' => [$artistOne->id, $artistTwo->id],
            'created_at'  => \Carbon\Carbon::today(),
        ]);
        $musicThree = factory(\SingPlus\Domains\Musics\Models\Music::class)->create([
            'artists' => [$artistTwo->id],
            'created_at'  => \Carbon\Carbon::today(),
        ]);

        $hotOne = factory(\SingPlus\Domains\Musics\Models\MusicHot::class)->create([
            'music_id'      => $musicOne->id,
            'display_order' => 100,
        ]);
        $hotTwo = factory(\SingPlus\Domains\Musics\Models\MusicHot::class)->create([
            'music_id'      => $musicTwo->id,
            'display_order' => 200,
        ]);
        $hotThree = factory(\SingPlus\Domains\Musics\Models\MusicHot::class)->create([
            'music_id'      => $musicThree->id,
            'display_order' => 300,
        ]);

        Cache::shouldReceive('get')
            ->once()
            ->with(sprintf('music:%s:reqnum', $hotThree->music_id))
            ->andReturn(1888);

        $response = $this->getJson('v3/musics/hots?' . http_build_query([
                    'id'      => $hotTwo->id,
                    'isNext'  => 0,
                    'size'    => 2,
                ]));
        $response->assertJson(['code' => 0]);
        $response = json_decode($response->getContent());
        $musics = $response->data->musics;
        self::assertCount(1, $musics);
        self::assertEquals($hotThree->id, $musics[0]->id);    // order by created_at
        self::assertEquals($musicThree->id, $musics[0]->musicId);    // order by created_at
        self::assertEquals($artistTwo->name, $musics[0]->artists);
        self::assertEquals(1888, $musics[0]->requestNum);
    }

  //=================================
  //        getCategories
  //=================================
  public function testGetCategoriesSuccess()
  {
    $user = factory(\SingPlus\Domains\Users\Models\User::class)->create();
    factory(\SingPlus\Domains\Users\Models\UserProfile::class)->create([
      'user_id' => $user->id, 
    ]);

    $langOne = factory(\SingPlus\Domains\Musics\Models\Language::class)->create([
      'name'          => 'English',
      'cover_image'   => 'e2119cd6d8de459eb64dd0c1859c0228',
      'total_number'  => 2222,
      'display_order' => 100,
      'status'        => 1,
    ]);
    $langTwo = factory(\SingPlus\Domains\Musics\Models\Language::class)->create([
      'name'          => 'Swahili',
      'cover_image'   => '6f52bde6c1374add92ea6325c7aef705',
      'total_number'  => 1111,
      'display_order' => 200,
    ]);
    $styleOne = factory(\SingPlus\Domains\Musics\Models\Style::class)->create([
      'name'          => 'rock',
      'cover_image'   => '41535728b0a2496f8f7c906e0261ce48',
      'total_number'  => 1111,
      'need_show'     => 1,
      'display_order' => 100,
    ]);
    $styleTwo = factory(\SingPlus\Domains\Musics\Models\Style::class)->create([
      'name'          => 'hip-hop',
      'cover_image'   => 'f6ea7ffd22454b88937954682e46ad07',
      'total_number'  => 8888,
      'need_show'     => 1,
      'display_order' => 200,
    ]);
    $styleThree = factory(\SingPlus\Domains\Musics\Models\Style::class)->create([
      'name'          => 'other',
      'cover_image'   => 'aaaa7ffd22454b88937954682e46ad07',
      'total_number'  => 8888,
      'need_show'     => 0,
      'display_order' => 300,
    ]);
    $styleFour = factory(\SingPlus\Domains\Musics\Models\Style::class)->create([
      'name'          => 'Jazz',
      'cover_image'   => 'bbbb7ffd22454b88937954682e46ad07',
      'total_number'  => 1111,
      'need_show'     => 0,
      'display_order' => 400,
    ]);
    
    Cache::shouldReceive('get')
         ->once()
         ->with('lang:num:adjust')
         ->andReturn([
          $langOne->id  => 1,
          $langTwo->id  => 2,
         ]);
    Cache::shouldReceive('get')
         ->once()
         ->with('style:num:adjust')
         ->andReturn([
          $styleOne->id  => 1,
          $styleTwo->id  => 2,
         ]);

    $response = $this->actingAs($user)
                     ->getJson('v3/musics/categories');
    $response->assertJson(['code' => 0]);

    $response = json_decode($response->getContent());
    $languages = $response->data->languages;
    $styles = $response->data->styles;
    self::assertCount(2, $languages);
    self::assertEquals('Swahili', $languages[0]->name);   // order by display order desc
    self::assertEquals(1113, $languages[0]->totalNum);
    self::assertEquals('English', $languages[1]->name);   // order by display order desc
    self::assertCount(3, $styles);
    self::assertEquals('hip-hop', $styles[0]->name);
    self::assertEquals(8890, $styles[0]->totalNum);
    self::assertEquals('rock', $styles[1]->name);
    self::assertNull($styles[2]->styleId);
    self::assertEquals('other', $styles[2]->name);
    self::assertEquals(9999, $styles[2]->totalNum);
  }

  public function testGetCategoriesSuccess_WithoutLogin()
  {
      $langOne = factory(\SingPlus\Domains\Musics\Models\Language::class)->create([
          'name'          => 'English',
          'cover_image'   => 'e2119cd6d8de459eb64dd0c1859c0228',
          'total_number'  => 2222,
          'display_order' => 100,
          'status'        => 1,
      ]);
      $langTwo = factory(\SingPlus\Domains\Musics\Models\Language::class)->create([
          'name'          => 'Swahili',
          'cover_image'   => '6f52bde6c1374add92ea6325c7aef705',
          'total_number'  => 1111,
          'display_order' => 200,
      ]);
      $styleOne = factory(\SingPlus\Domains\Musics\Models\Style::class)->create([
          'name'          => 'rock',
          'cover_image'   => '41535728b0a2496f8f7c906e0261ce48',
          'total_number'  => 1111,
          'need_show'     => 1,
          'display_order' => 100,
      ]);
      $styleTwo = factory(\SingPlus\Domains\Musics\Models\Style::class)->create([
          'name'          => 'hip-hop',
          'cover_image'   => 'f6ea7ffd22454b88937954682e46ad07',
          'total_number'  => 8888,
          'need_show'     => 1,
          'display_order' => 200,
      ]);
      $styleThree = factory(\SingPlus\Domains\Musics\Models\Style::class)->create([
          'name'          => 'other',
          'cover_image'   => 'aaaa7ffd22454b88937954682e46ad07',
          'total_number'  => 8888,
          'need_show'     => 0,
          'display_order' => 300,
      ]);
      $styleFour = factory(\SingPlus\Domains\Musics\Models\Style::class)->create([
          'name'          => 'Jazz',
          'cover_image'   => 'bbbb7ffd22454b88937954682e46ad07',
          'total_number'  => 1111,
          'need_show'     => 0,
          'display_order' => 400,
      ]);

      Cache::shouldReceive('get')
          ->once()
          ->with('lang:num:adjust')
          ->andReturn([
              $langOne->id  => 1,
              $langTwo->id  => 2,
          ]);
      Cache::shouldReceive('get')
          ->once()
          ->with('style:num:adjust')
          ->andReturn([
              $styleOne->id  => 1,
              $styleTwo->id  => 2,
          ]);

      $response = $this->getJson('v3/musics/categories');
      $response->assertJson(['code' => 0]);

      $response = json_decode($response->getContent());
      $languages = $response->data->languages;
      $styles = $response->data->styles;
      self::assertCount(2, $languages);
      self::assertEquals('Swahili', $languages[0]->name);   // order by display order desc
      self::assertEquals(1113, $languages[0]->totalNum);
      self::assertEquals('English', $languages[1]->name);   // order by display order desc
      self::assertCount(3, $styles);
      self::assertEquals('hip-hop', $styles[0]->name);
      self::assertEquals(8890, $styles[0]->totalNum);
      self::assertEquals('rock', $styles[1]->name);
      self::assertNull($styles[2]->styleId);
      self::assertEquals('other', $styles[2]->name);
      self::assertEquals(9999, $styles[2]->totalNum);
  }

    //=================================
    //        searchSuggest
    //=================================
    public function testSearchSuggestSuccess()
    {
        $this->mockHttpClient(json_encode([
            'code'  => 0,
            'data'  => [
                'suggests'  => [
                    [
                        'search'    => 'Booty Music', 
                        'source'    => 'name_suggest', 
                        'suggest_display'   => 'Booty Music Git Fresh', 
                        'suggest_raw'       => 'Booty Music',
                    ],
                    [
                        'search'            => 'Brooks & Dunn', 
                        'source'            => 'artists_name_suggest', 
                        'suggest_display'   => 'Brooks & Dunn Ain\'t Nothing \'Bout You', 
                        'suggest_raw'       => 'Brooks & Dunn',
                    ],
                ],
             ]]));

        $response = $this->getJson('v3/musics/search/suggest?' . http_build_query([
                        'search'    => 'byoo',
                    ]));
        $response = json_decode($response->getContent());
        $suggests = $response->data->suggests;
        self::assertCount(2, $suggests);
        self::assertEquals('Booty Music', $suggests[0]->search);
        self::assertEquals('Brooks & Dunn', $suggests[1]->search);
  }


  //=================================
  //        listMusics
  //=================================
  public function testListMusicsSuccess_WithArtist()
  {
    $user = factory(\SingPlus\Domains\Users\Models\User::class)->create();
    factory(\SingPlus\Domains\Users\Models\UserProfile::class)->create([
      'user_id' => $user->id, 
    ]);
    $data = $this->prepareMusic();

    $response = $this->actingAs($user)
                     ->getJson('v3/musics?' . http_build_query([
                        'artistId'  => $data->artists->one->id,
                     ]));
    $response->assertJson(['code' => 0]);
    $response = json_decode($response->getContent());
    $musics = $response->data->musics;
    self::assertCount(2, $musics);
    self::assertEquals('musicThree', $musics[0]->name);
    self::assertEquals('artistOne artistTwo', $musics[0]->artists); // two artists
    self::assertEquals('musicOne', $musics[1]->name);
  }

  public function testListMusicsSuccess_WithArtistAndGetNext()
  {
    $user = factory(\SingPlus\Domains\Users\Models\User::class)->create();
    factory(\SingPlus\Domains\Users\Models\UserProfile::class)->create([
      'user_id' => $user->id, 
    ]);
    $data = $this->prepareMusic();

    $response = $this->actingAs($user)
                     ->getJson('v3/musics?' . http_build_query([
                        'artistId'  => $data->artists->one->id,
                        'musicId'   => $data->musics->three->id,
                        'size'      => 1,
                     ]));
    $response->assertJson(['code' => 0]);
    $response = json_decode($response->getContent());
    $musics = $response->data->musics;
    self::assertCount(1, $musics);
    self::assertEquals('musicOne', $musics[0]->name);
    self::assertEquals('artistOne', $musics[0]->artists); // two artists
  }

  public function testListMusicsSuccess_WithLang()
  {
    $user = factory(\SingPlus\Domains\Users\Models\User::class)->create();
    factory(\SingPlus\Domains\Users\Models\UserProfile::class)->create([
      'user_id' => $user->id, 
    ]);
    $data = $this->prepareMusic();

    $response = $this->actingAs($user)
                     ->getJson('v3/musics?' . http_build_query([
                        'languageId'    => $data->langs->one->id,
                        'musicId'       => $data->musics->three->id,
                        'size'          => 1,
                     ]));
    $response->assertJson(['code' => 0]);
    $response = json_decode($response->getContent());
    $musics = $response->data->musics;
    self::assertCount(1, $musics);
    self::assertEquals('musicOne', $musics[0]->name);
    self::assertEquals('artistOne', $musics[0]->artists); // two artists
  }

  public function testListMusicsSuccess_WithStyle()
  {
    $user = factory(\SingPlus\Domains\Users\Models\User::class)->create();
    factory(\SingPlus\Domains\Users\Models\UserProfile::class)->create([
      'user_id' => $user->id, 
    ]);
    $data = $this->prepareMusic();

    $response = $this->actingAs($user)
                     ->getJson('v3/musics?' . http_build_query([
                        'styleId'       => $data->styles->one->id,
                        'musicId'       => $data->musics->three->id,
                        'size'          => 1,
                     ]));
    $response->assertJson(['code' => 0]);
    $response = json_decode($response->getContent());
    $musics = $response->data->musics;
    self::assertCount(1, $musics);
    self::assertEquals('musicOne', $musics[0]->name);
    self::assertEquals('artistOne', $musics[0]->artists); // two artists
  }

  public function testListMusicsSuccess_WithSearch()
  {
    Artisan::call('migrate:refresh');
    $user = factory(\SingPlus\Domains\Users\Models\User::class)->create();
    factory(\SingPlus\Domains\Users\Models\UserProfile::class)->create([
      'user_id' => $user->id, 
    ]);
    $data = $this->prepareMusic();
    $beyonceArtist = factory(\SingPlus\Domains\Musics\Models\Artist::class)->create([
      'name'  => 'Beyoncé',
    ]);
    $beyonceMusic = factory(\SingPlus\Domains\Musics\Models\Music::class)->create([
      'name'          => 'vvvvv',
      'artists'       => [
        $beyonceArtist->id,
      ],
      'artists_name'  => 'Beyoncé',
      'display_order' => 0,
    ]);

    $this->mockHttpClient(json_encode([
        'code'  => 0,
        'data'  => [
            'data'  => [
                [
                    '_id'   => 'eaaceaa2153c11e79ba952540085b9d0',
                    'artists'   => [
                        $beyonceArtist->id,
                    ],
                    'name'  => 'vvvvv',
                    'artists_name'  => 'Beyoncé',
                    'resource'  => [
                        'accompaniment' => [
                            'size'  => 4309018,
                        ],
                        'raw'   => [
                            'size'  => 2344333,
                        ],
                        'size'  => 4554842,
                    ],
                    'highlight' => [
                        'artists_name'  => [
                            '<em>Beyonce</em>'
                        ],
                    ],
                ],
            ],
            'total' => 1,
        ],
    ]));

    $response = $this->actingAs($user)
                     ->getJson('v3/musics?' . http_build_query([
                        //'search'        => 'artistone artistTwo',
                        'search'        => 'beyonce',
                     ]));

    $response->assertJson(['code' => 0]);
    $response = json_decode($response->getContent());
    $musics = $response->data->musics;
    self::assertCount(1, $musics);
    self::assertEquals('vvvvv', $musics[0]->name);
    self::assertEquals('Beyoncé', $musics[0]->artists);
    self::assertEquals('<em>Beyonce</em>', $musics[0]->highlight->artists_name);
  }

  public function testListMusicsSuccess_WithSearchNoResult()
  {
    Artisan::call('migrate:refresh');
    $user = factory(\SingPlus\Domains\Users\Models\User::class)->create();
    factory(\SingPlus\Domains\Users\Models\UserProfile::class)->create([
      'user_id' => $user->id, 
    ]);

    $this->mockHttpClient(json_encode([
        'code'  => 0,
        'data'  => [
            'data'  => [],
            'total' => 0,
        ],
    ]));

    $response = $this->actingAs($user)
                     ->getJson('v3/musics?' . http_build_query([
                        'search'        => 'artistone artistTwo',
                     ]));
    $response->assertJson([
      'code'  => 0,
      'data'  => [
        'musics'  => [], 
      ],
    ]);

    $this->assertDatabaseHas('feedbacks', [
      'user_id' => $user->id,
      'type'    => 3,           // \SingPlus\Domains\Helps\Models\Feedback::TYPE_MUSIC_SEARCH_AUTO
      'status'  => 1,           // \SingPlus\Domains\Helps\Models\Feedback::STATUS_WAIT
      'message' => [
        'musicName' => 'artistone artistTwo',
      ],
    ]);
  }

  public function testListMusicsSuccess_WithSearchAndPage()
  {
    Artisan::call('migrate:refresh');
    $user = factory(\SingPlus\Domains\Users\Models\User::class)->create();
    factory(\SingPlus\Domains\Users\Models\UserProfile::class)->create([
      'user_id' => $user->id, 
    ]);
    $data = $this->prepareMusic();

    $this->mockHttpClient(json_encode([
        'code'  => 0,
        'data'  => [
            'data'  => [
                [
                    '_id'   => 'eaaceaa2153c11e79ba952540085b9d0',
                    'artists'   => [
                        'eaaceaa2153c11e79ba952540085b9d0',
                    ],
                    'name'  => 'musicTwo',
                    'artists_name'  => 'Beyoncé',
                    'resource'  => [
                        'accompaniment' => [
                            'size'  => 4309018,
                        ],
                        'raw'   => [
                            'size'  => 2344333,
                        ],
                        'size'  => 4554842,
                    ],
                ],
            ],
            'total' => 1,
        ],
    ]));
    $response = $this->actingAs($user)
                     ->getJson('v3/musics?' . http_build_query([
                        'search'        => 'artistTwo',
                        'musicId'       => $data->musics->three->id,
                     ]));
    $response->assertJson(['code' => 0]);
    $response = json_decode($response->getContent());
    $musics = $response->data->musics;
    self::assertCount(1, $musics);
    self::assertEquals('musicTwo', $musics[0]->name);     // match artistTwo
  }

  public function testListMusicsSuccess_WithArtistWithoutLogin()
  {
      $data = $this->prepareMusic();

      $response = $this->getJson('v3/musics?' . http_build_query([
                  'artistId'  => $data->artists->one->id,
              ]));
      $response->assertJson(['code' => 0]);
      $response = json_decode($response->getContent());
      $musics = $response->data->musics;
      self::assertCount(2, $musics);
      self::assertEquals('musicThree', $musics[0]->name);
      self::assertEquals('artistOne artistTwo', $musics[0]->artists); // two artists
      self::assertEquals('musicOne', $musics[1]->name);
  }

    public function testListMusicsSuccess_WithArtistAndGetNext_WithoutLogin()
    {
        $data = $this->prepareMusic();

        $response = $this->getJson('v3/musics?' . http_build_query([
                    'artistId'  => $data->artists->one->id,
                    'musicId'   => $data->musics->three->id,
                    'size'      => 1,
                ]));
        $response->assertJson(['code' => 0]);
        $response = json_decode($response->getContent());
        $musics = $response->data->musics;
        self::assertCount(1, $musics);
        self::assertEquals('musicOne', $musics[0]->name);
        self::assertEquals('artistOne', $musics[0]->artists); // two artists
    }

    public function testListMusicsSuccess_WithLang_WithoutLogin()
    {
        $data = $this->prepareMusic();

        $response = $this->getJson('v3/musics?' . http_build_query([
                    'languageId'    => $data->langs->one->id,
                    'musicId'       => $data->musics->three->id,
                    'size'          => 1,
                ]));
        $response->assertJson(['code' => 0]);
        $response = json_decode($response->getContent());
        $musics = $response->data->musics;
        self::assertCount(1, $musics);
        self::assertEquals('musicOne', $musics[0]->name);
        self::assertEquals('artistOne', $musics[0]->artists); // two artists
    }

    public function testListMusicsSuccess_WithStyle_WithoutLogin()
    {
        $data = $this->prepareMusic();

        $response = $this->getJson('v3/musics?' . http_build_query([
                    'styleId'       => $data->styles->one->id,
                    'musicId'       => $data->musics->three->id,
                    'size'          => 1,
                ]));
        $response->assertJson(['code' => 0]);
        $response = json_decode($response->getContent());
        $musics = $response->data->musics;
        self::assertCount(1, $musics);
        self::assertEquals('musicOne', $musics[0]->name);
        self::assertEquals('artistOne', $musics[0]->artists); // two artists
    }

    public function testListMusicsSuccess_WithSearch_WithoutLogin()
    {
        Artisan::call('migrate:refresh');
        $data = $this->prepareMusic();
        $beyonceArtist = factory(\SingPlus\Domains\Musics\Models\Artist::class)->create([
            'name'  => 'Beyoncé',
        ]);
        $beyonceMusic = factory(\SingPlus\Domains\Musics\Models\Music::class)->create([
            'name'          => 'vvvvv',
            'artists'       => [
                $beyonceArtist->id,
            ],
            'artists_name'  => 'Beyoncé',
            'display_order' => 0,
        ]);

        $this->mockHttpClient(json_encode([
            'code'  => 0,
            'data'  => [
                'data'  => [
                    [
                        '_id'   => 'eaaceaa2153c11e79ba952540085b9d0',
                        'artists'   => [
                            'eaaceaa2153c11e79ba952540085b9d0',
                        ],
                        'name'  => 'vvvvv',
                        'artists_name'  => 'Beyoncé',
                        'resource'  => [
                            'accompaniment' => [
                                'size'  => 4309018,
                            ],
                            'raw'   => [
                                'size'  => 2344333,
                            ],
                            'size'  => 4554842,
                        ],
                    ],
                ],
                'total' => 1,
            ],
        ]));

        $response = $this->getJson('v3/musics?' . http_build_query([
                    //'search'        => 'artistone artistTwo',
                    'search'        => 'beyonce',
                ]));
        $response->assertJson(['code' => 0]);
        $response = json_decode($response->getContent());
        $musics = $response->data->musics;
        self::assertCount(1, $musics);
        self::assertEquals('vvvvv', $musics[0]->name);
        self::assertEquals('Beyoncé', $musics[0]->artists);
    }

    public function testListMusicsSuccess_WithSearchNoResult_WithoutLogin()
    {
        Artisan::call('migrate:refresh');

        $this->mockHttpClient(json_encode([
            'code'  => 0,
            'data'  => [
                'data'  => [],
                'total' => 0,
            ],
        ]));

        $response = $this->getJson('v3/musics?' . http_build_query([
                    'search'        => 'artistone artistTwo',
                ]));
        $response->assertJson([
            'code'  => 0,
            'data'  => [
                'musics'  => [],
            ],
        ]);

        $this->assertDatabaseHas('feedbacks', [
            'user_id' => "",
            'type'    => 3,           // \SingPlus\Domains\Helps\Models\Feedback::TYPE_MUSIC_SEARCH_AUTO
            'status'  => 1,           // \SingPlus\Domains\Helps\Models\Feedback::STATUS_WAIT
            'message' => [
                'musicName' => 'artistone artistTwo',
            ],
        ]);
    }

    public function testListMusicsSuccess_WithSearchAndPage_WithoutLogin()
    {
        Artisan::call('migrate:refresh');

        $data = $this->prepareMusic();

        $this->mockHttpClient(json_encode([
            'code'  => 0,
            'data'  => [
                'data'  => [
                    [
                        '_id'   => 'eaaceaa2153c11e79ba952540085b9d0',
                        'artists'   => [
                            'eaaceaa2153c11e79ba952540085b9d0',
                        ],
                        'name'  => 'musicTwo',
                        'artists_name'  => 'Beyoncé',
                        'resource'  => [
                            'accompaniment' => [
                                'size'  => 4309018,
                            ],
                            'raw'   => [
                                'size'  => 2344333,
                            ],
                            'size'  => 4554842,
                        ],
                    ],
                ],
                'total' => 1,
            ],
        ]));

        $response = $this->getJson('v3/musics?' . http_build_query([
                    'search'        => 'artistTwo',
                    'musicId'       => $data->musics->three->id,
                ]));
        $response->assertJson(['code' => 0]);
        $response = json_decode($response->getContent());
        $musics = $response->data->musics;
        self::assertCount(1, $musics);
        self::assertEquals('musicTwo', $musics[0]->name);     // match artistTwo
    }

  //=================================
  //        listMusicsByStyle
  //=================================
  public function testListMusicsByStyleSuccess()
  {
    $user = factory(\SingPlus\Domains\Users\Models\User::class)->create();
    factory(\SingPlus\Domains\Users\Models\UserProfile::class)->create([
      'user_id' => $user->id, 
    ]);
    $data = $this->prepareMusic();

    $response = $this->actingAs($user)
                     ->getJson('v3/musics/filter/style?' . http_build_query([
                        'styleId'       => $data->styles->one->id,
                        'musicId'       => $data->musics->three->id,
                        'size'          => 1,
                     ]));
    $response->assertJson(['code' => 0]);
    $response = json_decode($response->getContent());
    $musics = $response->data->musics;
    self::assertCount(1, $musics);
    self::assertEquals('musicOne', $musics[0]->name);
    self::assertEquals('artistOne', $musics[0]->artists); // two artists
  }

  public function testListMusicsByStyleSuccess_SearchOther()
  {
    $user = factory(\SingPlus\Domains\Users\Models\User::class)->create();
    factory(\SingPlus\Domains\Users\Models\UserProfile::class)->create([
      'user_id' => $user->id, 
    ]);
    $data = $this->prepareMusic();

    $response = $this->actingAs($user)
                     ->getJson('v3/musics/filter/style?' . http_build_query([
                        'size'          => 20,
                     ]));
    $response->assertJson(['code' => 0]);
    $response = json_decode($response->getContent());
    $musics = $response->data->musics;
    self::assertCount(3, $musics);
    self::assertEquals('musicThree musicFour', $musics[0]->name); // style: styleThree
    self::assertEquals('artistTwo', $musics[0]->artists);
    self::assertEquals('musicThree', $musics[1]->name);
    self::assertEquals('artistOne artistTwo', $musics[1]->artists); // style: style one, style two
    self::assertEquals('musicTwo', $musics[2]->name);               // style: style two
    self::assertEquals('artistTwo', $musics[2]->artists);

    //
    // note: music one not match, cause it's style only is style one, but style one's
    //       need_show equals to 1, so, not belongs to other
    //
  }

  public function testListMusicsByStyleSuccess_WithoutLogin()
  {
      $data = $this->prepareMusic();

      $response = $this->getJson('v3/musics/filter/style?' . http_build_query([
                  'styleId'       => $data->styles->one->id,
                  'musicId'       => $data->musics->three->id,
                  'size'          => 1,
              ]));
      $response->assertJson(['code' => 0]);
      $response = json_decode($response->getContent());
      $musics = $response->data->musics;
      self::assertCount(1, $musics);
      self::assertEquals('musicOne', $musics[0]->name);
      self::assertEquals('artistOne', $musics[0]->artists); // two artists
  }

    public function testListMusicsByStyleSuccess_SearchOther_WithoutLogin()
    {
        $data = $this->prepareMusic();

        $response = $this->getJson('v3/musics/filter/style?' . http_build_query([
                    'size'          => 20,
                ]));
        $response->assertJson(['code' => 0]);
        $response = json_decode($response->getContent());
        $musics = $response->data->musics;
        self::assertCount(3, $musics);
        self::assertEquals('musicThree musicFour', $musics[0]->name); // style: styleThree
        self::assertEquals('artistTwo', $musics[0]->artists);
        self::assertEquals('musicThree', $musics[1]->name);
        self::assertEquals('artistOne artistTwo', $musics[1]->artists); // style: style one, style two
        self::assertEquals('musicTwo', $musics[2]->name);               // style: style two
        self::assertEquals('artistTwo', $musics[2]->artists);

        //
        // note: music one not match, cause it's style only is style one, but style one's
        //       need_show equals to 1, so, not belongs to other
        //
    }

  //=================================
  //        getTips
  //=================================
  public function testGetTipsSuccess()
  {
    $user = factory(\SingPlus\Domains\Users\Models\User::class)->create();
    factory(\SingPlus\Domains\Users\Models\UserProfile::class)->create([
      'user_id' => $user->id, 
    ]);
    $response = $this->actingAs($user)
                     ->getJson('v3/musics/tips');
    $response->assertJson(['code' => 0]);
  }


  public function testGetTipsSuccess_WithoutLogin()
  {
      $response = $this->getJson('v3/musics/tips');
      $response->assertJson(['code' => 0]);
  }

  //=================================
  //        getMusicDownloadAddress
  //=================================
  public function testGetMusicDownloadAddressSuccess()
  {
    $user = factory(\SingPlus\Domains\Users\Models\User::class)->create();
    factory(\SingPlus\Domains\Users\Models\UserProfile::class)->create([
      'user_id' => $user->id, 
    ]);
    factory(\SingPlus\Domains\Users\Models\UserImage::class)->create([
      'user_id' => $user->id,
      'uri'     => 'images/vvvvvvvvv',
      'is_avatar' => 1,
    ]);
    $music = factory(\SingPlus\Domains\Musics\Models\Music::class)->create([
      'resource'   => [
        'zip' => 'test-bucket/xxxxxxxxxx'
      ],
      'cover_images'  => [
        'images/vvvvvvvvv',
      ],
    ]);
    Cache::shouldReceive('increment')
         ->once()
         ->with(sprintf('music:%s:reqnum', $music->id));
    Cache::shouldReceive('increment')
         ->once()
         ->with(sprintf('music:%s:%s:reqnum', $music->id, null));
    $response = $this->actingAs($user)
                     ->getJson('v3/musics/download?' . http_build_query([
                        'musicId' => $music->id, 
                     ]));
    $response->assertJson(['code' => 0]);
    $response = json_decode($response->getContent());
    $address = $response->data->downloadUrl;
    self::assertTrue(ends_with($address, 'test-bucket/xxxxxxxxxx'));
    self::assertTrue(ends_with($response->data->cover, 'images/vvvvvvvvv'));
  }

  public function testGetMusicDownloadAddressSuccess_FromCountryOperation()
  {
    $this->enableNationOperationMiddleware();
    config([
      'nationality.operation_country_abbr'  => ['TZ'],
    ]);

    $user = factory(\SingPlus\Domains\Users\Models\User::class)->create();
    factory(\SingPlus\Domains\Users\Models\UserProfile::class)->create([
      'user_id' => $user->id, 
    ]);
    factory(\SingPlus\Domains\Users\Models\UserImage::class)->create([
      'user_id' => $user->id,
      'uri'     => 'images/vvvvvvvvv',
      'is_avatar' => 1,
    ]);
    $music = factory(\SingPlus\Domains\Musics\Models\Music::class)->create([
      'resource'   => [
        'zip' => 'test-bucket/xxxxxxxxxx'
      ],
      'cover_images'  => [
        'images/vvvvvvvvv',
      ],
    ]);
    Cache::shouldReceive('increment')
         ->once()
         ->with(sprintf('music:%s:reqnum', $music->id));
    Cache::shouldReceive('increment')
         ->once()
         ->with(sprintf('music:%s:%s:reqnum', $music->id, '-*'));
    $response = $this->actingAs($user)
                     ->getJson('v3/musics/download?' . http_build_query([
                        'musicId' => $music->id,
                     ]), [
                        'X-CountryAbbr' => 'IN', 
                     ]);
    $response->assertJson(['code' => 0]);
    $response = json_decode($response->getContent());
    $address = $response->data->downloadUrl;
    self::assertTrue(ends_with($address, 'test-bucket/xxxxxxxxxx'));
    self::assertTrue(ends_with($response->data->cover, 'images/vvvvvvvvv'));
  }

  public function testGetMusicDownloadAddressFailed_ZipMissed()
  {
    $user = factory(\SingPlus\Domains\Users\Models\User::class)->create();
    factory(\SingPlus\Domains\Users\Models\UserProfile::class)->create([
      'user_id' => $user->id, 
    ]);
    $music = factory(\SingPlus\Domains\Musics\Models\Music::class)->create([
      'resource'   => [],
    ]);
    $response = $this->actingAs($user)
                     ->getJson('v3/musics/download?' . http_build_query([
                        'musicId' => $music->id, 
                     ]));
    $response->assertJson(['code' => 10305]);
  }

  public function testGetMusicDownloadAddressFailed_MusicOutOfStock()
  {
    $user = factory(\SingPlus\Domains\Users\Models\User::class)->create();
    factory(\SingPlus\Domains\Users\Models\UserProfile::class)->create([
      'user_id' => $user->id, 
    ]);
    $music = factory(\SingPlus\Domains\Musics\Models\Music::class)->create([
      'resource'   => [
        'zip' => 'test-bucket/xxxxxxxxxx'
      ],
      'cover_images'  => [
        'images/vvvvvvvvv',
      ],
      'status'  => -1,
    ]);
    $response = $this->actingAs($user)
                     ->getJson('v3/musics/download?' . http_build_query([
                        'musicId' => $music->id, 
                     ]));
    $response->assertJson(['code' => 10302]);
  }

  public function testGetMusicDownloadAddressSuccess_WithoutLogin()
  {
      $music = factory(\SingPlus\Domains\Musics\Models\Music::class)->create([
          'resource'   => [
              'zip' => 'test-bucket/xxxxxxxxxx'
          ],
          'cover_images'  => [
              'images/vvvvvvvvv',
          ],
      ]);
      Cache::shouldReceive('increment')
          ->once()
          ->with(sprintf('music:%s:reqnum', $music->id));
      Cache::shouldReceive('increment')
          ->once()
          ->with(sprintf('music:%s:%s:reqnum', $music->id, null));
      $response = $this->getJson('v3/musics/download?' . http_build_query([
                  'musicId' => $music->id,
              ]));
      $response->assertJson(['code' => 0]);
      $response = json_decode($response->getContent());
      $address = $response->data->downloadUrl;
      self::assertTrue(ends_with($address, 'test-bucket/xxxxxxxxxx'));
      self::assertEquals($response->data->cover, "");
  }

    public function testGetMusicDownloadAddressSuccess_FromCountryOperation_WithoutLogin()
    {
        $this->enableNationOperationMiddleware();
        config([
            'nationality.operation_country_abbr'  => ['TZ'],
        ]);

        $user = factory(\SingPlus\Domains\Users\Models\User::class)->create();
        factory(\SingPlus\Domains\Users\Models\UserProfile::class)->create([
            'user_id' => $user->id,
        ]);
        factory(\SingPlus\Domains\Users\Models\UserImage::class)->create([
            'user_id' => $user->id,
            'uri'     => 'images/vvvvvvvvv',
            'is_avatar' => 1,
        ]);
        $music = factory(\SingPlus\Domains\Musics\Models\Music::class)->create([
            'resource'   => [
                'zip' => 'test-bucket/xxxxxxxxxx'
            ],
            'cover_images'  => [
                'images/vvvvvvvvv',
            ],
        ]);
        Cache::shouldReceive('increment')
            ->once()
            ->with(sprintf('music:%s:reqnum', $music->id));
        Cache::shouldReceive('increment')
            ->once()
            ->with(sprintf('music:%s:%s:reqnum', $music->id, '-*'));
        $response = $this->getJson('v3/musics/download?' . http_build_query([
                    'musicId' => $music->id,
                ]), [
                'X-CountryAbbr' => 'IN',
            ]);
        $response->assertJson(['code' => 0]);
        $response = json_decode($response->getContent());
        $address = $response->data->downloadUrl;
        self::assertTrue(ends_with($address, 'test-bucket/xxxxxxxxxx'));
        self::assertTrue(ends_with($response->data->cover, ''));
    }

    public function testGetMusicDownloadAddressFailed_ZipMissed_WithoutLogin()
    {
        $music = factory(\SingPlus\Domains\Musics\Models\Music::class)->create([
            'resource'   => [],
        ]);
        $response = $this->getJson('v3/musics/download?' . http_build_query([
                    'musicId' => $music->id,
                ]));
        $response->assertJson(['code' => 10305]);
    }


  //=================================
  //        getMusicDetail
  //=================================
  public function testGetMusicDetailSuccess_Basic()
  {
    $user = factory(\SingPlus\Domains\Users\Models\User::class)->create();
    factory(\SingPlus\Domains\Users\Models\UserProfile::class)->create([
      'user_id' => $user->id, 
    ]);

    $music = factory(\SingPlus\Domains\Musics\Models\Music::class)->create([
      'name'          => 'Halo',
      'cover_images'  => ['halo'],
      'resource'      => [
        'size'        => '789789789',
      ],
      'artists_name'  => 'zhangsan lisi',
    ]);

    $response = $this->actingAs($user)
                     ->getJson('v3/musics/' . $music->id . '?' . http_build_query([
                        'basic' => 1, 
                     ]));
    $response->assertJson(['code' => 0]);
    $data = json_decode($response->getContent())->data;
    $resMusic = $data->music;

    self::assertEquals('Halo', $resMusic->name);
    self::assertTrue(ends_with($resMusic->cover, 'halo'));
    self::assertEquals('zhangsan lisi', $resMusic->artists);
    self::assertEquals(789789789, $resMusic->sizeBytes);
    self::assertNull($resMusic->etag);
  }

  public function testGetMusicDetailSuccess_UsingDefaultRanking()
  {
    $this->expectsEvents(\SingPlus\Events\Works\RankExpired::class);
    $user = factory(\SingPlus\Domains\Users\Models\User::class)->create();
    factory(\SingPlus\Domains\Users\Models\UserProfile::class)->create([
      'user_id' => $user->id, 
    ]);

    $music = factory(\SingPlus\Domains\Musics\Models\Music::class)->create([
      'name'          => 'Halo',
      'cover_images'  => ['halo'],
      'resource'      => [
        'size'        => '789789789',
        'etag'        => 'b4ba2587f45144a096406841361e5b81',
      ],
      'artists_name'  => 'zhangsan lisi',
    ]);
    $workData = $this->prepareMusicWorks($music->id);

    $response = $this->actingAs($user)
                     ->getJson('v3/musics/' . $music->id);
    $response->assertJson(['code' => 0]);

    $data = json_decode($response->getContent())->data;
    $resMusic = $data->music;
    $chorusRecommends = $data->chorusRecommends;
    $soloRankinglists = $data->soloRankinglists;
    self::assertEquals('Halo', $resMusic->name);
    self::assertTrue(ends_with($resMusic->cover, 'halo'));
    self::assertEquals('zhangsan lisi', $resMusic->artists);
    self::assertEquals(789789789, $resMusic->sizeBytes);
    self::assertEquals('b4ba2587f45144a096406841361e5b81', $resMusic->etag);

    self::assertCount(2, $chorusRecommends);
    self::assertEquals($workData->work->four->id, $chorusRecommends[0]->workId);
    self::assertEquals(1001, $chorusRecommends[0]->chorusCount);
    self::assertEquals($workData->user->two->id, $chorusRecommends[0]->author->userId);
    self::assertEquals('user-two', $chorusRecommends[0]->author->nickname);
    self::assertTrue(ends_with($chorusRecommends[0]->author->avatar, 'user-two-avatar'));
    self::assertEquals($workData->work->three->id, $chorusRecommends[1]->workId);

    self::assertCount(2, $soloRankinglists);
    self::assertEquals($workData->work->two->id, $soloRankinglists[0]->workId);
    self::assertEquals(200, $soloRankinglists[0]->listenCount);
    self::assertEquals($workData->work->one->id, $soloRankinglists[1]->workId);
  }

  public function testGetMusicDetailSuccess_RankingNotExpired()
  {
    // event should not trigered
    //$this->expectsEvents(\SingPlus\Events\Works\RankExpired::class);
    $user = factory(\SingPlus\Domains\Users\Models\User::class)->create();
    factory(\SingPlus\Domains\Users\Models\UserProfile::class)->create([
      'user_id' => $user->id, 
    ]);

    $music = factory(\SingPlus\Domains\Musics\Models\Music::class)->create([
      'name'          => 'Halo',
      'cover_images'  => ['halo'],
      'resource'      => [
        'size'        => '789789789',
      ],
      'artists_name'  => 'zhangsan lisi',
      'work_rank_expired_at'  => \Carbon\Carbon::now()->addSeconds(60)->format('Y-m-d H:i:s'),
    ]);

    $response = $this->actingAs($user)
                     ->getJson('v3/musics/' . $music->id);
    $response->assertJson(['code' => 0]);

    $data = json_decode($response->getContent())->data;
    $resMusic = $data->music;
    self::assertEquals('zhangsan lisi', $resMusic->artists);
  }

  public function testGetMusicDetailSuccess()
  {
    $this->expectsEvents(\SingPlus\Events\Works\RankExpired::class);
    $user = factory(\SingPlus\Domains\Users\Models\User::class)->create();
    factory(\SingPlus\Domains\Users\Models\UserProfile::class)->create([
      'user_id' => $user->id, 
    ]);

    $music = factory(\SingPlus\Domains\Musics\Models\Music::class)->create([
      'name'          => 'Halo',
      'cover_images'  => ['halo'],
      'resource'      => [
        'size'        => '789789789',
      ],
      'artists_name'  => 'zhangsan lisi',
    ]);
    $workData = $this->prepareMusicWorks($music->id);

    factory(\SingPlus\Domains\Works\Models\MusicWorkRankingList::class)->create([
      'music_id'    => $music->id,
      'work_id'     => $workData->work->three->id,
      'type'        => \SingPlus\Domains\Works\Models\MusicWorkRankingList::TYPE_CHORUS,
    ]);
    factory(\SingPlus\Domains\Works\Models\MusicWorkRankingList::class)->create([
      'music_id'    => $music->id,
      'work_id'     => $workData->work->one->id,
      'type'        => \SingPlus\Domains\Works\Models\MusicWorkRankingList::TYPE_SOLO,
    ]);

    $response = $this->actingAs($user)
                     ->getJson('v3/musics/' . $music->id);
    $response->assertJson(['code' => 0]);

    $data = json_decode($response->getContent())->data;
    $resMusic = $data->music;
    $chorusRecommends = $data->chorusRecommends;
    $soloRankinglists = $data->soloRankinglists;

    self::assertEquals('zhangsan lisi', $resMusic->artists);

    self::assertCount(1, $chorusRecommends);
    self::assertEquals($workData->work->three->id, $chorusRecommends[0]->workId);
    self::assertEquals(1000, $chorusRecommends[0]->chorusCount);
    self::assertEquals($workData->user->one->id, $chorusRecommends[0]->author->userId);
    self::assertEquals('user-one', $chorusRecommends[0]->author->nickname);
    self::assertTrue(ends_with($chorusRecommends[0]->author->avatar, 'user-one-avatar'));

    self::assertCount(1, $soloRankinglists);
    self::assertEquals($workData->work->one->id, $soloRankinglists[0]->workId);
    self::assertEquals(100, $soloRankinglists[0]->listenCount);
  }

  public function testGetMusicDetailFailed_FakeMusic()
  {
    $fakeMusicId = '230ec19915ec4785966880dd41ec003c';
    config([
      'business-logic.fakemusic'  => [
        'id'    => $fakeMusicId,
        'name'  => 'fake music',
      ],
    ]);
    $user = factory(\SingPlus\Domains\Users\Models\User::class)->create();
    factory(\SingPlus\Domains\Users\Models\UserProfile::class)->create([
      'user_id' => $user->id, 
    ]);
    $this->actingAs($user)
         ->getJson('v3/musics/' . $fakeMusicId)
         ->assertJson(['code' => 10301]);
  }

    public function testGetMusicDetailSuccess_Basic_WithoutLogin()
    {
        $music = factory(\SingPlus\Domains\Musics\Models\Music::class)->create([
            'name'          => 'Halo',
            'cover_images'  => ['halo'],
            'resource'      => [
                'size'        => '789789789',
            ],
            'artists_name'  => 'zhangsan lisi',
        ]);

        $response = $this->getJson('v3/musics/' . $music->id . '?' . http_build_query([
                    'basic' => 1,
                ]));
        $response->assertJson(['code' => 0]);
        $data = json_decode($response->getContent())->data;
        $resMusic = $data->music;

        self::assertEquals('Halo', $resMusic->name);
        self::assertTrue(ends_with($resMusic->cover, 'halo'));
        self::assertEquals('zhangsan lisi', $resMusic->artists);
        self::assertEquals(789789789, $resMusic->sizeBytes);
        self::assertNull($resMusic->etag);
    }

    public function testGetMusicDetailSuccess_UsingDefaultRanking_WithoutLogin()
    {
        $this->expectsEvents(\SingPlus\Events\Works\RankExpired::class);

        $music = factory(\SingPlus\Domains\Musics\Models\Music::class)->create([
            'name'          => 'Halo',
            'cover_images'  => ['halo'],
            'resource'      => [
                'size'        => '789789789',
                'etag'        => 'b4ba2587f45144a096406841361e5b81',
            ],
            'artists_name'  => 'zhangsan lisi',
        ]);
        $workData = $this->prepareMusicWorks($music->id);

        $response = $this->getJson('v3/musics/' . $music->id);
        $response->assertJson(['code' => 0]);

        $data = json_decode($response->getContent())->data;
        $resMusic = $data->music;
        $chorusRecommends = $data->chorusRecommends;
        $soloRankinglists = $data->soloRankinglists;
        self::assertEquals('Halo', $resMusic->name);
        self::assertTrue(ends_with($resMusic->cover, 'halo'));
        self::assertEquals('zhangsan lisi', $resMusic->artists);
        self::assertEquals(789789789, $resMusic->sizeBytes);
        self::assertEquals('b4ba2587f45144a096406841361e5b81', $resMusic->etag);

        self::assertCount(2, $chorusRecommends);
        self::assertEquals($workData->work->four->id, $chorusRecommends[0]->workId);
        self::assertEquals(1001, $chorusRecommends[0]->chorusCount);
        self::assertEquals($workData->user->two->id, $chorusRecommends[0]->author->userId);
        self::assertEquals('user-two', $chorusRecommends[0]->author->nickname);
        self::assertTrue(ends_with($chorusRecommends[0]->author->avatar, 'user-two-avatar'));
        self::assertEquals($workData->work->three->id, $chorusRecommends[1]->workId);

        self::assertCount(2, $soloRankinglists);
        self::assertEquals($workData->work->two->id, $soloRankinglists[0]->workId);
        self::assertEquals(200, $soloRankinglists[0]->listenCount);
        self::assertEquals($workData->work->one->id, $soloRankinglists[1]->workId);
    }

    public function testGetMusicDetailSuccess_RankingNotExpired_WithoutLogin()
    {
        // event should not trigered
        //$this->expectsEvents(\SingPlus\Events\Works\RankExpired::class);

        $music = factory(\SingPlus\Domains\Musics\Models\Music::class)->create([
            'name'          => 'Halo',
            'cover_images'  => ['halo'],
            'resource'      => [
                'size'        => '789789789',
            ],
            'artists_name'  => 'zhangsan lisi',
            'work_rank_expired_at'  => \Carbon\Carbon::now()->addSeconds(60)->format('Y-m-d H:i:s'),
        ]);

        $response = $this->getJson('v3/musics/' . $music->id);
        $response->assertJson(['code' => 0]);

        $data = json_decode($response->getContent())->data;
        $resMusic = $data->music;
        self::assertEquals('zhangsan lisi', $resMusic->artists);
    }

    public function testGetMusicDetailSuccess_WithoutLogin()
    {
        $this->expectsEvents(\SingPlus\Events\Works\RankExpired::class);

        $music = factory(\SingPlus\Domains\Musics\Models\Music::class)->create([
            'name'          => 'Halo',
            'cover_images'  => ['halo'],
            'resource'      => [
                'size'        => '789789789',
            ],
            'artists_name'  => 'zhangsan lisi',
        ]);
        $workData = $this->prepareMusicWorks($music->id);

        factory(\SingPlus\Domains\Works\Models\MusicWorkRankingList::class)->create([
            'music_id'    => $music->id,
            'work_id'     => $workData->work->three->id,
            'type'        => \SingPlus\Domains\Works\Models\MusicWorkRankingList::TYPE_CHORUS,
        ]);
        factory(\SingPlus\Domains\Works\Models\MusicWorkRankingList::class)->create([
            'music_id'    => $music->id,
            'work_id'     => $workData->work->one->id,
            'type'        => \SingPlus\Domains\Works\Models\MusicWorkRankingList::TYPE_SOLO,
        ]);

        $response = $this->getJson('v3/musics/' . $music->id);
        $response->assertJson(['code' => 0]);

        $data = json_decode($response->getContent())->data;
        $resMusic = $data->music;
        $chorusRecommends = $data->chorusRecommends;
        $soloRankinglists = $data->soloRankinglists;

        self::assertEquals('zhangsan lisi', $resMusic->artists);

        self::assertCount(1, $chorusRecommends);
        self::assertEquals($workData->work->three->id, $chorusRecommends[0]->workId);
        self::assertEquals(1000, $chorusRecommends[0]->chorusCount);
        self::assertEquals($workData->user->one->id, $chorusRecommends[0]->author->userId);
        self::assertEquals('user-one', $chorusRecommends[0]->author->nickname);
        self::assertTrue(ends_with($chorusRecommends[0]->author->avatar, 'user-one-avatar'));

        self::assertCount(1, $soloRankinglists);
        self::assertEquals($workData->work->one->id, $soloRankinglists[0]->workId);
        self::assertEquals(100, $soloRankinglists[0]->listenCount);
    }

    public function testGetMusicDetailFailed_FakeMusic_WithoutLogin()
    {
        $fakeMusicId = '230ec19915ec4785966880dd41ec003c';
        config([
            'business-logic.fakemusic'  => [
                'id'    => $fakeMusicId,
                'name'  => 'fake music',
            ],
        ]);

        $this->getJson('v3/musics/' . $fakeMusicId)
            ->assertJson(['code' => 10301]);
    }

  private function prepareMusicWorks(string $musicId)
  {
    $userOne = factory(\SingPlus\Domains\Users\Models\User::class)->create();
    $profileOne = factory(\SingPlus\Domains\Users\Models\UserProfile::class)->create([
      'user_id'   => $userOne->id, 
      'nickname'  => 'user-one',
      'avatar'    => 'user-one-avatar',
    ]);
    $userTwo = factory(\SingPlus\Domains\Users\Models\User::class)->create();
    $profileTwo = factory(\SingPlus\Domains\Users\Models\UserProfile::class)->create([
      'user_id'   => $userTwo->id, 
      'nickname'  => 'user-two',
      'avatar'    => 'user-two-avatar',
    ]);
    $workOne = factory(\SingPlus\Domains\Works\Models\Work::class)->create([
      'user_id'       => $userOne->id,
      'music_id'      => $musicId,
      'listen_count'  => 100,
    ]);
    $workTwo = factory(\SingPlus\Domains\Works\Models\Work::class)->create([
      'user_id'       => $userTwo->id,
      'music_id'      => $musicId,
      'listen_count'  => 200,
    ]);
    $workThree = factory(\SingPlus\Domains\Works\Models\Work::class)->create([
      'user_id'           => $userOne->id,
      'music_id'          => $musicId,
      'listen_count'      => 300,
      'chorus_type'       => \SingPlus\Contracts\Works\Constants\WorkConstant::CHORUS_TYPE_START,
      'chorus_start_info' => [
        'chorus_count'  => 1000,
        'resource'      => [
          'zip' => 'work-three-accompaniment',
        ],
      ]
    ]);
    $workFour = factory(\SingPlus\Domains\Works\Models\Work::class)->create([
      'user_id'           => $userTwo->id,
      'music_id'          => $musicId,
      'listen_count'      => 400,
      'chorus_type'       => \SingPlus\Contracts\Works\Constants\WorkConstant::CHORUS_TYPE_START,
      'chorus_start_info' => [
        'chorus_count'  => 1001,
        'resource'      => [
          'zip' => 'work-four-accompaniment',
        ],
      ]
    ]);
    $workFive = factory(\SingPlus\Domains\Works\Models\Work::class)->create([
      'user_id'       => $userOne->id,
      'music_id'      => $musicId,
      'listen_count'  => 500,
      'chorus_type'       => \SingPlus\Contracts\Works\Constants\WorkConstant::CHORUS_TYPE_JOIN,
      'chorus_join_info'  => [
        'origin_work_id'  => $workThree->id,
      ],
    ]);
    $workSix = factory(\SingPlus\Domains\Works\Models\Work::class)->create([
      'user_id'       => $userTwo->id,
      'music_id'      => $musicId,
      'listen_count'  => 600,
      'chorus_type'       => \SingPlus\Contracts\Works\Constants\WorkConstant::CHORUS_TYPE_JOIN,
      'chorus_join_info'  => [
        'origin_work_id'  => $workFour->id,
      ],
    ]);

    return (object) [
      'user'  => (object) [
        'one' => $userOne,
        'two' => $userTwo,
      ],
      'profile' => (object) [
        'one' => $profileOne,
        'two' => $profileTwo,
      ],
      'work'  => (object) [
        'one'   => $workOne,
        'two'   => $workTwo,
        'three' => $workThree,
        'four'  => $workFour,
        'five'  => $workFive,
        'six'   => $workSix,
      ],
    ];
  }

  private function prepareMusic()
  {
    $styleOne = factory(\SingPlus\Domains\Musics\Models\Style::class)->create([
      'name'      => 'styleOne',
      'need_show' => 1,
    ]);
    $styleTwo = factory(\SingPlus\Domains\Musics\Models\Style::class)->create([
      'name'  => 'styleTwo',
    ]);
    $styleThree = factory(\SingPlus\Domains\Musics\Models\Style::class)->create([
      'name'  => 'styleThree',
    ]);
    $langOne = factory(\SingPlus\Domains\Musics\Models\Language::class)->create([
      'name'  => 'langOne',
    ]);
    $langTwo = factory(\SingPlus\Domains\Musics\Models\Language::class)->create([
      'name'  => 'langTwo',
    ]);
    $artistOne = factory(\SingPlus\Domains\Musics\Models\Artist::class)->create([
      'name'  => 'artistOne', 
    ]);
    $artistTwo = factory(\SingPlus\Domains\Musics\Models\Artist::class)->create([
      'name'  => 'artistTwo', 
    ]);
    $musicOne = factory(\SingPlus\Domains\Musics\Models\Music::class)->create([
      'name'    => 'musicOne',
      'artists' => [
        $artistOne->id
      ],
      'artists_name'  => 'artistOne',
      'styles'  => [
        $styleOne->id,
      ],
      'languages' => [
        $langOne->id,
      ],
      'display_order' => 100,
    ]);
    $musicTwo = factory(\SingPlus\Domains\Musics\Models\Music::class)->create([
      'name'    => 'musicTwo',
      'artists' => [
        $artistTwo->id
      ],
      'artists_name'  => 'artistTwo',
      'styles'  => [
        $styleTwo->id,
      ],
      'languages' => [
        $langTwo->id,
      ],
      'display_order' => 200,
    ]);
    $musicThree = factory(\SingPlus\Domains\Musics\Models\Music::class)->create([
      'name'    => 'musicThree',
      'artists' => [
        $artistOne->id, $artistTwo->id
      ],
      'artists_name'  => 'artistOne artistTwo',
      'styles'  => [
        $styleOne->id, $styleTwo->id,
      ],
      'languages' => [
        $langOne->id, $langTwo->id,
      ],
      'display_order' => 300,
    ]);

    $musicFour = factory(\SingPlus\Domains\Musics\Models\Music::class)->create([
      'name'    => 'musicThree musicFour',
      'artists' => [
        $artistTwo->id
      ],
      'styles'  => [
        $styleThree->id,
      ],
      'artists_name'  => 'artistTwo',
      'display_order' => 400,
    ]);

    return (object) [
      'artists' => (object) [
        'one'   => $artistOne,
        'two'   => $artistTwo,
      ],
      'musics'  => (object) [
        'one'   => $musicOne,
        'two'   => $musicTwo,
        'three' => $musicThree,
        'four'  => $musicFour,
      ],
      'langs'   => (object) [
        'one'   => $langOne,
        'two'   => $langTwo,
      ],
      'styles'  => (object) [
        'one'   => $styleOne,
        'two'   => $styleTwo,
      ],
    ];
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
