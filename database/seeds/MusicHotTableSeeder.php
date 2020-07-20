<?php

use Illuminate\Database\Seeder;

use SingPlus\Domains\Musics\Models\MusicHot;

class MusicHotTableSeeder extends Seeder
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
          'music_id'      => '20c54490153d11e79ba952540085b9d0',
          'display_order' => '100',
        ],
        [
          'music_id'      => '20cb5dbc153d11e79ba952540085b9d0',
          'display_order' => '200',
        ],
        [
          'music_id'      => '20cfc0b4153d11e79ba952540085b9d0',
          'display_order' => '300',
        ],
        [
          'music_id'      => '20cfc0b4153d11e79ba952540085b9d0',
          'display_order' => '400',
        ],
        [
          'music_id'      => '201ce19c153d11e79ba952540085b9d0',
          'display_order' => '600',
        ],
      ]);

      $datas->each(function ($data) {
        MusicHot::create($data);
      });
    }

    protected function clear()
    {
      DB::connection('mongodb')->table('music_hots')->delete();
    }
}
