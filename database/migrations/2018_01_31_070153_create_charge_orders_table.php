<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateChargeOrdersTable extends Migration
{
    protected $connection = 'mongodb';
    protected $collection = 'charge_orders';

    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
      if ( ! Schema::connection($this->connection)->hasTable($this->collection)) {
        Schema::connection($this->connection)->create($this->collection, function (Blueprint $table) {
            /*
             * note: partialFilterExpression must be added, cause pay_order_id may be null when
             *       order created.
             *       in mongodb, empty field with unique key will be treat as null,
             *       if more than one docments have null value for field: pay_order_id, exception
             *       will be throw cause unique constrians.
             *       so, we add partialFilterExpression option, only mobile's type is string
             *       unique constrain be added to this document
             */
          $table->unique('pay_order_id', null, null, [
            'partialFilterExpression'   => [
                'pay_order_id'  => [
                    '$type' => 'string',
                ],
            ],
          ]);
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
      if (Schema::connection($this->connection)->hasTable($this->collection)) {
        Schema::connection($this->connection)->drop($this->collection);
      }
    }
}
