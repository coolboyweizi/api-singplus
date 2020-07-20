<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddIndexUseridToDailyTaskHistoryTable extends Migration
{

    protected $connection = 'mongodb';
    protected $collection = 'daily_task_history';
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
                $table->index('task_id');
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
        Schema::table('daily_task_history', function (Blueprint $table) {
            //
        });
    }
}
