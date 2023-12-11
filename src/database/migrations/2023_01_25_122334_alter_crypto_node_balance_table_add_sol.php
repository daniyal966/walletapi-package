<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AlterCryptoNodeBalanceTableAddSol extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('crypto_node_balance', function (Blueprint $table) {
            $table->decimal('sol', 10, 8)->default(0);
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('crypto_node_balance', function (Blueprint $table) {
            $table->decimal('sol',10,8)->default(0);
        });
    }
}
