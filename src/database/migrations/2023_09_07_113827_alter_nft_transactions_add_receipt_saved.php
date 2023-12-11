<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AlterNftTransactionsAddReceiptSaved extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('nft_transactions', function (Blueprint $table) {
            $table->enum('receipt_saved', ['pending', 'completed', 'in_process'])->default('pending');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('nft_transactions', function (Blueprint $table) {
            $table->enum('receipt_saved', ['pending', 'completed', 'in_process'])->default('pending');
        });
    }
}
