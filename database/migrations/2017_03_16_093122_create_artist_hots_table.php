<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateArtistHotsTable extends Migration
{
    protected $connection = 'mongodb';

    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
      if ( ! Schema::connection($this->connection)->hasTable('artist_hots')) {
        Schema::connection($this->connection)->create('artist_hots', function (Blueprint $table) {
          $table->index('artist_id');
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
      if (Schema::connection($this->connection)->hasTable('artist_hots')) {
        Schema::connection($this->connection)->drop('artist_hots');
      }
    }
}
