<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateWorkFavouriteTable extends Migration
{
    protected $connection = 'mongodb';

    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
      if ( ! Schema::connection($this->connection)->hasTable('work_favourites')) {
        Schema::connection($this->connection)->create('work_favourites', function (Blueprint $table) {
          $table->softDeletes();
          $table->index(['work_id', 'user_id']);
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
      if (Schema::connection($this->connection)->hasTable('work_favourites')) {
        Schema::connection($this->connection)->drop('work_favourites');
      }
    }
}
