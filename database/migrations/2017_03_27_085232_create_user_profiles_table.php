<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateUserProfilesTable extends Migration
{
    protected $connection = 'mongodb';

    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
      if ( ! Schema::connection($this->connection)->hasTable('user_profiles')) {
        Schema::connection($this->connection)->create('user_profiles', function (Blueprint $table) {
            $table->unique('user_id');
            $table->unique('nickname');
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
      if (Schema::connection($this->connection)->hasTable('user_profiles')) {
        Schema::connection($this->connection)->drop('user_profiles');
      }
    }
}
