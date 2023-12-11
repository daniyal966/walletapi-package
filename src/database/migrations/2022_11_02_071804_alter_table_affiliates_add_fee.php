<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AlterTableAffiliatesAddFee extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('affiliates', function (Blueprint $table) {
            $table->decimal('LTC_transaction_fee', 10, 8)->default(0);
            $table->decimal('BTC_transaction_fee', 10, 8)->default(0);
            $table->decimal('BCH_transaction_fee', 10, 8)->default(0);
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('affiliates', function (Blueprint $table) {
            $table->decimal('LTC_transaction_fee', 10, 8)->default(0);
            $table->decimal('BCH_transaction_fee', 10, 8)->default(0);
            $table->decimal('BTC_transaction_fee', 10, 8)->default(0);
        });
    }
}
