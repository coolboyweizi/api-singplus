<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddIndexWorkidToGiftContributionTable extends Migration
{
    protected $connection = 'mongodb';
    protected $collection = 'gift_contribution';
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        if (Schema::connection($this->connection)->hasTable($this->collection)) {
            Schema::connection($this->connection)->table($this->collection, function (Blueprint $table) {
                $table->index('sender_id');
                $table->index('receiver_id');
                $table->index('work_id');
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
