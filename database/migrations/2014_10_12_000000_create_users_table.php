<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateUsersTable extends Migration
{
    protected $connection = 'mongodb';

    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
      if ( ! Schema::connection($this->connection)->hasTable('users')) {
        Schema::connection($this->connection)->create('users', function (Blueprint $table) {
            /*
             * note: partialFilterExpression must be added, cause mobile may be null if
             *       user from socialite (eg: facebook).
             *       in mongodb, empty field with unique key will be treat as null,
             *       if more than one docments have null value for field: mobie, exception
             *       will be throw cause unique constrians.
             *       so, we add partialFilterExpression option, only mobile's type is string
             *       unique constrain be added to this document
             */
            $table->unique('mobile', null, null, [
              'partialFilterExpression' => [
                'mobile'  => [
                  '$type' => 'string',
                ],
              ],
            ]);   // mobile with country_code
            $table->softDeletes();

            // truansfer $table->unique() to mongo sql, as below 
            /*
            db.users.createIndex(
              {mobile: 1},
              {unique: true, partialFilterExpression: {mobile: {$type: "string"}}}
            );
            */
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
      if (Schema::connection($this->connection)->hasTable('users')) {
        Schema::connection($this->connection)->drop('users');
      }
    }
}
