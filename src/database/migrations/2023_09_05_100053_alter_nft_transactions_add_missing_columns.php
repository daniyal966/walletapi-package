<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AlterNftTransactionsAddMissingColumns extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('nft_transactions', function (Blueprint $table) {
            $table->integer('csv_update')->default(0);
            $table->enum('callback_status', ['pending', 'completed', 'in_process', 'failed'])->default('pending');
            $table->boolean('is_notified')->default(0);
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
            $table->integer('csv_update')->default(0);
            $table->enum('callback_status', ['pending', 'completed', 'in_process', 'failed'])->default('pending');
            $table->boolean('is_notified')->default(0);
        });
    }
}
