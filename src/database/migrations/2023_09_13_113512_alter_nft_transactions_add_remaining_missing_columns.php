<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AlterNftTransactionsAddRemainingMissingColumns extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('nft_transactions', function (Blueprint $table) {
            $table->text('reason')->nullable();
            $table->enum('email_sent', ['pending', 'in_process', 'completed', 'failed'])->default('pending');
            $table->string('flow_type')->default('v1');
            $table->decimal('matic_return_fee',100,8)->default(0.00000000);
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
            $table->text('reason')->nullable();
            $table->enum('email_sent', ['pending', 'in_process', 'completed', 'failed'])->default('pending');
            $table->string('flow_type')->default('v1');
            $table->decimal('matic_return_fee',100,8)->default(0.00000000);
        });
    }
}
