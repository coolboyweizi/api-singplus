<?php

namespace FeatureTest\SingPlus\Commands;

use Carbon\Carbon;
use Cache;
use Artisan;
use FeatureTest\SingPlus\TestCase;
use FeatureTest\SingPlus\MongodbClearTrait;

class CategoryAdjustTest extends TestCase
{
  use MongodbClearTrait;

  public function testCategoryAdjustSuccess()
  {
    $langOne = factory(\SingPlus\Domains\Musics\Models\Language::class)->create([
      'total_number'  => 1000,
      'total_song_number' => 99,
    ]);
    $langTwo = factory(\SingPlus\Domains\Musics\Models\Language::class)->create([
      'total_number'  => 1000,
      'total_song_number' => 100,
    ]);
    $styleOne = factory(\SingPlus\Domains\Musics\Models\Style::class)->create([
      'total_number'  => 200, 
      'total_song_number' => 49,
    ]);
    $styleTwo = factory(\SingPlus\Domains\Musics\Models\Style::class)->create([
      'total_number'  => 201, 
      'total_song_number' => 50,
    ]);
    Cache::shouldReceive('get')
         ->once()
         ->with('lang:num:adjust')
         ->andReturn([
            $langOne->id   => 50,
            $langTwo->id   => 51,
         ]);
    foreach ([$langOne->id, $langTwo->id] as $langId) {
      $key = sprintf('music:lang:%s:reqnum', $langId);
      Cache::shouldReceive('get')
           ->once()
           ->with($key)
           ->andReturn(50);
      Cache::shouldReceive('forever')
           ->once()
           ->with($key, 0);
    }
    Cache::shouldReceive('forever')
         ->once()
         ->with('lang:num:adjust', [
            $langOne->id    => 150,   // 50 + 50 + 50
            $langTwo->id    => 101,   // 51 + 50
         ]);

    Cache::shouldReceive('get')
         ->once()
         ->with('style:num:adjust')
         ->andReturn([
            $styleOne->id   => 20,
            $styleTwo->id   => 21,
         ]);
    foreach ([$styleOne->id, $styleTwo->id] as $styleId) {
      $key = sprintf('music:style:%s:reqnum', $styleId);
      Cache::shouldReceive('get')
           ->once()
           ->with($key)
           ->andReturn(50);
      Cache::shouldReceive('forever')
           ->once()
           ->with($key, 0);
    }
    Cache::shouldReceive('forever')
         ->once()
         ->with('style:num:adjust', [
            $styleOne->id   => 125,     // 20 + 2 * 50 + 5
            $styleTwo->id   => 131,     // 21 + 2 * 50 + 10
         ]);
    Artisan::call('adjust:category');
  }
}
