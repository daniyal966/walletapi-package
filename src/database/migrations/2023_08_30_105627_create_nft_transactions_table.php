<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateNftTransactionsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('nft_transactions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('affiliate_id')->constrained('affiliates');
            $table->string('request_id');
            $table->string('trx_id');
            $table->string('customer_email');
            $table->string('merchant_email');
            $table->decimal('fiat_amount', 100, 8);
            $table->decimal('crypto_amount', 100, 8);
            $table->decimal('conversion_rate', 100, 8);
            $table->enum('fiat_currency', ['EUR', 'USD']);
            $table->enum('crypto_currency', ['MATIC', 'SOL'])->default('MATIC');
            $table->string('callback_url');
            $table->string('psp_mid');
            $table->string('customer_address');
            $table->string('merchant_address')->nullable();
            $table->string('nft_id')->nullable();
            $table->string('nft_name')->nullable();
            $table->enum('status', ['pending', 'completed', 'in_process', 'on_hold', 'failed', 'declined'])->default('pending');
            $table->text('mint_transaction_hash')->nullable();
            $table->text('transfer_transaction_hash')->nullable();
            $table->decimal('mint_transaction_fee', 100, 8)->nullable();
            $table->decimal('transfer_transaction_fee', 100, 8)->nullable();
            $table->decimal('fiat_gas_fee', 100, 8)->nullable()->nullable();
            $table->text('source');
            $table->text('token_id')->nullable();
            $table->dateTime('delivery_time')->nullable();
            $table->timestamps();
            $table->index(['affiliate_id'], 'affiliate_id_index_nft_transactions');
            $table->unique(['request_id', 'trx_id', 'customer_address', 'merchant_address', 'mint_transaction_hash', 'transfer_transaction_hash'], 'unique_constraint');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('nft_transactions');
    }
}
