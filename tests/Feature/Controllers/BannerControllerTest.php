<?php

namespace FeatureTest\SingPlus\Controllers\Auth;

use Carbon\Carbon;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use FeatureTest\SingPlus\TestCase;
use FeatureTest\SingPlus\MongodbClearTrait;
use Mockery;
use SingPlus\Support\Helpers\Str;

class BannerControllerTest extends TestCase
{
  use MongodbClearTrait; 

  //=================================
  //        listBanners
  //=================================
  public function testListBannersSuccess()
  {
    $user = factory(\SingPlus\Domains\Users\Models\User::class)->create();
    factory(\SingPlus\Domains\Users\Models\UserProfile::class)->create([
      'user_id' => $user->id, 
    ]);

    $image_one = factory(\SingPlus\Domains\Banners\Models\Banner::class)->create([
      'image'       => 'test-bucket/images/banners/' . Str::uuid(),
      'start_time'  => Carbon::yesterday(),
      'stop_time'   => Carbon::tomorrow(),
      'created_at'  => Carbon::yesterday(),
      'display_order' => 100,
    ]);
    $image_two = factory(\SingPlus\Domains\Banners\Models\Banner::class)->create([
      'image'       => 'test-bucket/images/banners/' . Str::uuid(),
      'start_time'  => Carbon::today(),
      'stop_time'   => Carbon::tomorrow(),
      'created_at'  => Carbon::today(),
      'display_order' => 200,
    ]);
    $image_three = factory(\SingPlus\Domains\Banners\Models\Banner::class)->create([
      'image'       => 'test-bucket/images/banners/' . Str::uuid(),
      'start_time'  => Carbon::yesterday(),
      'stop_time'   => Carbon::yesterday(),
      'created_at'  => Carbon::today(),
      'display_order' => 300,
    ]);

    $response = $this->actingAs($user)
                     ->getJson('v3/banners');

    $response->assertJson(['code' => 0]);
    $response->assertHeader('etag');                        // validate ETag middleware
    $etag = $response->headers->get('etag');

    $response = json_decode($response->getContent());
    $banners = $response->data->banners;
    self::assertCount(2, $banners);
    self::assertEquals($image_two->id, $banners[0]->id);    // order by created_at desc
    self::assertEquals($image_one->id, $banners[1]->id);

    // validate etag
    $response = $this->actingAs($user)
         ->getJson('v3/banners', [
            'If-None-Match' => $etag, 
         ]);
    $response->assertStatus(304);
  }

  public function testListBannersSuccess_FromCountryOperation()
  {
    $this->enableNationOperationMiddleware();
    config([
      'nationality.operation_country_abbr'  => ['TZ'],
    ]);

    $user = factory(\SingPlus\Domains\Users\Models\User::class)->create();
    factory(\SingPlus\Domains\Users\Models\UserProfile::class)->create([
      'user_id' => $user->id, 
    ]);

    $image_one = factory(\SingPlus\Domains\Banners\Models\Banner::class)->create([
      'image'         => 'test-bucket/images/banners/' . Str::uuid(),
      'start_time'    => Carbon::yesterday(),
      'stop_time'     => Carbon::tomorrow(),
      'created_at'    => Carbon::yesterday(),
      'display_order' => 100,
      'country_abbr'  => '-*',
    ]);
    $image_two = factory(\SingPlus\Domains\Banners\Models\Banner::class)->create([
      'image'         => 'test-bucket/images/banners/' . Str::uuid(),
      'start_time'    => Carbon::today(),
      'stop_time'     => Carbon::tomorrow(),
      'created_at'    => Carbon::today(),
      'display_order' => 200,
      'country_abbr'  => 'IN',
    ]);
    $image_three = factory(\SingPlus\Domains\Banners\Models\Banner::class)->create([
      'image'         => 'test-bucket/images/banners/' . Str::uuid(),
      'start_time'    => Carbon::yesterday(),
      'stop_time'     => Carbon::yesterday(),
      'created_at'    => Carbon::today(),
      'display_order' => 300,
      'country_abbr'  => '-*',
    ]);

    $response = $this->actingAs($user)
                     ->getJson('v3/banners', [
                        'X-CountryAbbr' => 'IN', 
                     ]);

    $response->assertJson(['code' => 0]);
    $response->assertHeader('etag');                        // validate ETag middleware
    $etag = $response->headers->get('etag');
    $response->assertHeader('X-CountryAbbr', '-*');
    $response->assertHeader('X-RealCountryAbbr', 'IN');

    $response = json_decode($response->getContent());
    $banners = $response->data->banners;
    self::assertCount(1, $banners);
    self::assertEquals($image_one->id, $banners[0]->id);    // -* be fetched
  }

    public function testListBannersSuccess_WithoutLogin()
    {
        $user = factory(\SingPlus\Domains\Users\Models\User::class)->create();
        factory(\SingPlus\Domains\Users\Models\UserProfile::class)->create([
            'user_id' => $user->id,
        ]);

        $image_one = factory(\SingPlus\Domains\Banners\Models\Banner::class)->create([
            'image'       => 'test-bucket/images/banners/' . Str::uuid(),
            'start_time'  => Carbon::yesterday(),
            'stop_time'   => Carbon::tomorrow(),
            'created_at'  => Carbon::yesterday(),
            'display_order' => 100,
        ]);
        $image_two = factory(\SingPlus\Domains\Banners\Models\Banner::class)->create([
            'image'       => 'test-bucket/images/banners/' . Str::uuid(),
            'start_time'  => Carbon::today(),
            'stop_time'   => Carbon::tomorrow(),
            'created_at'  => Carbon::today(),
            'display_order' => 200,
        ]);
        $image_three = factory(\SingPlus\Domains\Banners\Models\Banner::class)->create([
            'image'       => 'test-bucket/images/banners/' . Str::uuid(),
            'start_time'  => Carbon::yesterday(),
            'stop_time'   => Carbon::yesterday(),
            'created_at'  => Carbon::today(),
            'display_order' => 300,
        ]);

        $response = $this->getJson('v3/banners');

        $response->assertJson(['code' => 0]);
        $response->assertHeader('etag');                        // validate ETag middleware
        $etag = $response->headers->get('etag');

        $response = json_decode($response->getContent());
        $banners = $response->data->banners;
        self::assertCount(2, $banners);
        self::assertEquals($image_two->id, $banners[0]->id);    // order by created_at desc
        self::assertEquals($image_one->id, $banners[1]->id);

        // validate etag
        $response = $this->actingAs($user)
            ->getJson('v3/banners', [
                'If-None-Match' => $etag,
            ]);
        $response->assertStatus(304);
    }

    public function testListBannersSuccess_FromCountryOperation_WithoutLogin()
    {
        $this->enableNationOperationMiddleware();
        config([
            'nationality.operation_country_abbr'  => ['TZ'],
        ]);

        $user = factory(\SingPlus\Domains\Users\Models\User::class)->create();
        factory(\SingPlus\Domains\Users\Models\UserProfile::class)->create([
            'user_id' => $user->id,
        ]);

        $image_one = factory(\SingPlus\Domains\Banners\Models\Banner::class)->create([
            'image'         => 'test-bucket/images/banners/' . Str::uuid(),
            'start_time'    => Carbon::yesterday(),
            'stop_time'     => Carbon::tomorrow(),
            'created_at'    => Carbon::yesterday(),
            'display_order' => 100,
            'country_abbr'  => '-*',
        ]);
        $image_two = factory(\SingPlus\Domains\Banners\Models\Banner::class)->create([
            'image'         => 'test-bucket/images/banners/' . Str::uuid(),
            'start_time'    => Carbon::today(),
            'stop_time'     => Carbon::tomorrow(),
            'created_at'    => Carbon::today(),
            'display_order' => 200,
            'country_abbr'  => 'IN',
        ]);
        $image_three = factory(\SingPlus\Domains\Banners\Models\Banner::class)->create([
            'image'         => 'test-bucket/images/banners/' . Str::uuid(),
            'start_time'    => Carbon::yesterday(),
            'stop_time'     => Carbon::yesterday(),
            'created_at'    => Carbon::today(),
            'display_order' => 300,
            'country_abbr'  => '-*',
        ]);

        $response = $this->getJson('v3/banners', [
                'X-CountryAbbr' => 'IN',
            ]);

        $response->assertJson(['code' => 0]);
        $response->assertHeader('etag');                        // validate ETag middleware
        $etag = $response->headers->get('etag');
        $response->assertHeader('X-CountryAbbr', '-*');
        $response->assertHeader('X-RealCountryAbbr', 'IN');

        $response = json_decode($response->getContent());
        $banners = $response->data->banners;
        self::assertCount(1, $banners);
        self::assertEquals($image_one->id, $banners[0]->id);    // -* be fetched
    }
}
