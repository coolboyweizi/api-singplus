<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateMultiUserPushInboxTable extends Migration
{
    protected $connection = 'mongodb';
    protected $collections = [];
    protected $collectionNum = 64;

    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
      $this->getCollections();
      foreach ($this->collections as $_ => $collection) {
        if ( ! Schema::connection($this->connection)->hasTable($collection)) {
          Schema::connection($this->connection)->create($collection, function (Blueprint $table) {
            $table->index(['user_id', 'display_order']);
          });
        }
      }
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
      $this->getCollections();
      foreach ($this->collections as $_ => $collection) {
        if (Schema::connection($this->connection)->hasTable($collection)) {
          Schema::connection($this->connection)->drop($collection);
        }
      }
    }

    private function getCollections()
    {
      $basename = 'user_push_inbox';

      $this->collections[] = $basename;
      foreach (range(1, $this->collectionNum) as $_ => $num) {
        $this->collections[] = sprintf('%s_%03d', $basename, $num);
      }
    }
}
