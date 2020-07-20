<?php

use Illuminate\Database\Seeder;

use SingPlus\Domains\Musics\Models\MusicRecommend;

class MusicRecommendTableSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
      $this->clear();
      $this->seed();
    }

    protected function seed()
    {
      $datas = collect([
        [
          'music_id'  => '2167a1c2153d11e79ba952540085b9d0',
          'display_order' => '100',
        ],
        [
          'music_id'  => '216aeff8153d11e79ba952540085b9d0',
          'display_order' => '200',
        ],
        [
          'music_id'  => '216eabe8153d11e79ba952540085b9d0',
          'display_order' => '300',
        ],
        [
          'music_id'  => '21740228153d11e79ba952540085b9d0',
          'display_order' => '400',
        ],
        [
          'music_id'  => '219b7bf0153d11e79ba952540085b9d0',
          'display_order' => '500',
        ],
        [
          'music_id'  => '21a035dc153d11e79ba952540085b9d0',
          'display_order' => '600',
        ],
      ]);

      $datas->each(function ($data) {
        MusicRecommend::create($data);
      });
    }

    protected function clear()
    {
      DB::connection('mongodb')->table('music_recommends')->delete();
    }
}
