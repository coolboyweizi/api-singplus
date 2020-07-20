<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateSocialiteUsersTable extends Migration
{
    protected $connection = 'mongodb';

    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
      if ( ! Schema::connection($this->connection)->hasTable('socialite_users')) {
        Schema::connection($this->connection)->create('socialite_users', function (Blueprint $table) {
          $table->index(['user_id', 'provider']);
          $table->index(['socialite_user_id', 'provider']);
          $table->timestamps();
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
      if (Schema::connection($this->connection)->hasTable('socialite_users')) {
        Schema::connection($this->connection)->drop('socialite_users');
      }
    }
}
