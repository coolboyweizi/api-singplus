<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateFeedsIndices extends Migration
{
    protected $connection = 'mongodb';
    protected $collection = 'feeds';

    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
      Schema::connection($this->connection)->table($this->collection, function (Blueprint $table) {
          $table->index(['user_id', 'type', 'is_read']);
      });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
      if (Schema::connection($this->connection)->hasTable($this->collection)) {
        Schema::connection($this->connection)->drop($this->collection);
      }
    }
}
