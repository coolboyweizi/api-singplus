<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateMusicRecommendsTable extends Migration
{
    protected $connection = 'mongodb';

    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
      if ( ! Schema::connection($this->connection)->hasTable('music_recommends')) {
        Schema::connection($this->connection)->create('music_recommends', function (Blueprint $table) {
          $table->index('music_id');
          $table->index('display_order');
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
      if (Schema::connection($this->connection)->hasTable('music_recommends')) {
        Schema::connection($this->connection)->drop('music_recommends');
      }
    }
}
