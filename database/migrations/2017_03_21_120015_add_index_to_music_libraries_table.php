<?php
/**
 * @link: https://github.com/jenssegers/laravel-mongodb/wiki/Creating-Full-Text-Index-With-Laravel-Migration-Using-Moloquent
 */

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddIndexToMusicLibrariesTable extends Migration
{
    protected $connection = 'mongodb';

    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
      if ( ! Schema::connection($this->connection)->hasTable('music_libraries')) {
        Schema::connection($this->connection)->create('music_libraries', function (Blueprint $table) {
          $table->index(
            [
              'name'          => 'text',
              'artists_name'  => 'text',
              'search_alias'  => 'text',
            ],
            'music_full_text',
            null
          );
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
      if (Schema::connection($this->connection)->hasTable('music_libraries')) {
        Schema::connection($this->connection)->drop('music_libraries');
        /*
        Schema::connection($this->connection)->table('music_libraries', function (Blueprint $table) {
          $table->dropIndex(['name' => 'music_full_text']);
        });
        */
      }
    }
}
