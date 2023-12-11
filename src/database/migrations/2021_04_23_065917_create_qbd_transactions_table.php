<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateQbdTransactionsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        if ( !Schema::hasTable('qbd_transactions') ) {
            Schema::create('qbd_transactions', function (Blueprint $table) {
                $table->id();
                $table->foreignId('user_wallet_id')->nullable()->constrained('user_wallets');
                $table->string('request_id');
                $table->enum('currency',['LTC', 'BTC', 'BCH'])->nullable();
                $table->string('receiving_address');
                $table->string('receive_transaction_hash')->nullable();
                $table->string('sending_address');
                $table->string('send_transaction_hash')->nullable();
                $table->integer('tries')->nullable()->default('0');
                $table->integer('is_processing')->nullable()->default('0');
                $table->timestamp('created_at')->useCurrent();
                $table->timestamp('updated_at')->useCurrentOnUpdate()->nullable();
                $table->index(['user_wallet_id','request_id','receiving_address','receive_transaction_hash','sending_address','send_transaction_hash','tries','is_processing'],'qbd_transaction_index');
                $table->unique(['receiving_address','receive_transaction_hash'],'unique_transaction_constraint');
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
        Schema::dropIfExists('qbd_transactions');
    }
}
