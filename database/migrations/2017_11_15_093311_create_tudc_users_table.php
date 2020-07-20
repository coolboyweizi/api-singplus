<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateTudcUsersTable extends Migration
{
    protected $connection = 'mongodb';
    protected $collection = 'tudc_users';

    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
      if ( ! Schema::connection($this->connection)->hasTable($this->collection)) {
        Schema::connection($this->connection)->create($this->collection, function (Blueprint $table) {
            $table->unique('user_id');
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
      if (Schema::connection($this->connection)->hasTable($this->collection)) {
        Schema::connection($this->connection)->drop($this->collection);
      }
    }
}
