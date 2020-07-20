<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddIndexPushaliasToUsersTable extends Migration
{
    protected $connection = 'mongodb';
    protected $collection = 'users';

    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
      if (Schema::connection($this->connection)->hasTable($this->collection)) {
        Schema::connection($this->connection)->table($this->collection, function (Blueprint $table) {
          $table->index('push_alias');
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
        //
    }
}
