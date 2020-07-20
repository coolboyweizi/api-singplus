<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddOpenidIndexForSocialiteUsersTable extends Migration
{
    protected $connection = 'mongodb';
    protected $collection = 'socialite_users';

    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
      if (Schema::connection($this->connection)->hasTable($this->collection)) {
        Schema::connection($this->connection)->table($this->collection, function (Blueprint $table) {
          $table->index(['channels.singplus.openid', 'provider']);
          $table->index(['channels.boomsing.openid', 'provider']);
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
