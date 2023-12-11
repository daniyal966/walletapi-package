<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AlterQuantozOrdersColumnNames extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('orders', function(Blueprint $table) {
            $table->renameColumn('quantoz_account_guid', 'account_guid');
            $table->renameColumn('quantoz_account_code', 'account_code');
            $table->renameColumn('quantoz_transaction_code', 'transaction_code');
            $table->renameColumn('quantoz_customer_code', 'customer_code');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('orders', function(Blueprint $table) {
            $table->renameColumn('account_guid', 'quantoz_account_guid');
            $table->renameColumn('account_code', 'quantoz_account_code');
            $table->renameColumn('transaction_code', 'quantoz_transaction_code');
            $table->renameColumn('customer_code', 'quantoz_customer_code');
        });
    }
}
