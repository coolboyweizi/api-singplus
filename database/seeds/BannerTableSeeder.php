<?php

use Carbon\Carbon;
use Illuminate\Database\Seeder;
use SingPlus\Contracts\Banners\Constants\ModelConstant;
use SingPlus\Domains\Banners\Models\Banner;

class BannerTableSeeder extends Seeder
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
      Banner::create([
        'image'       => 'pizzas/1389ccaba01149709a9fa7a80a11e751/images/5080fd64194b4531b5c6d50e6281030a',
        'type'        => ModelConstant::TYPE_URL,
        'attributes'  => [
          'url' => 'https://www.baidu.com/',
        ],
        'start_time'  => Carbon::parse('2017-04-01'),
        'stop_time'   => Carbon::parse('2018-04-01'),
      ]);
      Banner::create([
        'image'       => 'pizzas/1389ccaba01149709a9fa7a80a11e751/images/bc3fbad4830849bc85773af33ba98be0',
        'type'        => ModelConstant::TYPE_NATIVE,
        'attributes'  => (object) [],
        'start_time'  => Carbon::parse('2017-04-01'),
        'stop_time'   => Carbon::parse('2018-04-01'),
      ]);
      Banner::create([
        'image'       => 'pizzas/1389ccaba01149709a9fa7a80a11e751/images/bc3fbad4830849bc85773af33ba98be0',
        'type'        => ModelConstant::TYPE_NATIVE,
        'attributes'  => (object) [],
        'start_time'  => Carbon::parse('2017-04-01'),
        'stop_time'   => Carbon::parse('2017-04-08'),
      ]);
    }

    protected function clear()
    {
      DB::connection('mongodb')->table('banners')->delete();
    }
}
