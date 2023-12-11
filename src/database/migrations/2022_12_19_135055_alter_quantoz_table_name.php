<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AlterQuantozTableName extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::rename('quantoz_callback_logs', 'callback_logs');
        Schema::rename('quantoz_customers', 'customers');
        Schema::rename('quantoz_logs', 'order_logs');
        Schema::rename('quantoz_orders', 'orders');
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::rename('callback_logs', 'quantoz_callback_logs');
        Schema::rename('customers', 'quantoz_customers');
        Schema::rename('order_logs', 'quantoz_logs');
        Schema::rename('orders', 'quantoz_orders');
    }
}
