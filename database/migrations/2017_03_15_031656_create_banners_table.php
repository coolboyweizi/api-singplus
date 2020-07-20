<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateBannersTable extends Migration
{
  protected $connection = 'mongodb';

  /**
   * Run the migrations.
   *
   * @return void
   */
  public function up()
  {
    if ( ! Schema::connection($this->connection)->hasTable('banners')) {
      Schema::connection($this->connection)->create('banners', function (Blueprint $table) {
          $table->index(['start_time', 'stop_time']);
      });
    }
  }

  /**
   * Reverse the migrations.
   *
   * @return void
   */
  public function down()
  {
    if (Schema::connection($this->connection)->hasTable('banners')) {
      Schema::connection($this->connection)->drop('banners');
    }
  }
}
