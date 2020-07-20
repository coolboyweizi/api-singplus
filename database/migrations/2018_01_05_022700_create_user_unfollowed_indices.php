<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateUserUnfollowedIndices extends Migration
{
    protected $connection = 'mongodb';
    protected $collection = 'user_unfollowed';
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
                $table->index(['unfollowed', 'display_order']);
                $table->index(['unfollowed_details.user_id', 'display_order']);
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
