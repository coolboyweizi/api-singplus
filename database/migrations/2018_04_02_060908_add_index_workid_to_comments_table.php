<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddIndexWorkidToCommentsTable extends Migration
{
    protected $connection = 'mongodb';
    protected $collection = 'comments';
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        if (Schema::connection($this->connection)->hasTable($this->collection)) {
            Schema::connection($this->connection)->table($this->collection, function (Blueprint $table) {
                $table->index('user_id');
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
