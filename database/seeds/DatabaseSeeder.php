<?php

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
      $this->call(BannerTableSeeder::class);
      $this->call(MusicRecommendTableSeeder::class);
      $this->call(MusicHotTableSeeder::class);
      $this->call(ArtistHotTableSeeder::class);
    }
}
