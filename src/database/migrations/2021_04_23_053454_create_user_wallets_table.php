<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateUserWalletsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        if ( !Schema::hasTable('user_wallets') ) {
            Schema::create('user_wallets', function (Blueprint $table) {
                $table->id();
                $table->foreignId('user_id')->constrained('users');
                $table->enum('source',['QB', 'PURSER', 'CELOXO', 'BCP', 'CP' , 'WI' , 'DS' ,'FB']);
                $table->enum('host',['LTC_NODE', 'BITCOIN_NODE', 'NODE', 'SOL_NODE']);
                $table->enum('crypto_currency',['LITECOIN', 'BITCOIN', 'BITCOIN_CASH', 'SOLANA']);
                $table->decimal('total_balance',100,8)->default(0.00000000);
                $table->decimal('spendable_balance',100,8)->default(0.00000000);
                $table->integer('is_locked')->nullable();
                $table->string('current_address')->nullable();
                $table->string('mnemonic_phrase')->nullable();
                $table->timestamp('created_at')->useCurrent();
                $table->timestamp('updated_at')->useCurrentOnUpdate()->nullable();
                $table->index(['user_id','current_address','source','crypto_currency','host'],'customer_wallet_index');
                $table->unique(['user_id', 'source', 'host', 'crypto_currency', 'is_locked'],'unique_customer_wallet_constraint');
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
        Schema::dropIfExists('customer_wallets');
    }
}
