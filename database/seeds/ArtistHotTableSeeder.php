<?php

use Illuminate\Database\Seeder;

use SingPlus\Domains\Musics\Models\ArtistHot;

class ArtistHotTableSeeder extends Seeder
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
          'artist_id'     => 'ffd48a48153c11e79ba952540085b9d0',
          'display_order' => '100',
        ],
        [
          'artist_id'     => '21a3880e153d11e79ba952540085b9d0',
          'display_order' => '200',
        ],
        [
          'artist_id'     => '21c0b820153d11e79ba952540085b9d0',
          'display_order' => '300',
        ],
      ]);

      $datas->each(function ($data) {
        ArtistHot::create($data);
      });
    }

    protected function clear()
    {
      DB::connection('mongodb')->table('artist_hots')->delete();
    }
}
