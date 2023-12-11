<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateTransactionsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        if ( !Schema::hasTable('transactions') ) {
            Schema::create('transactions', function (Blueprint $table) {
                $table->id();
                $table->foreignId('user_wallet_id')->nullable()->constrained('user_wallets');
                $table->integer('celoxo_transaction_id')->nullable();
                $table->enum('category',['send', 'receive']);
                $table->string('address');
                $table->decimal('amount',100,8)->default(0.00000000);
                $table->decimal('fee',100,8)->default(0.00000000);
                $table->integer('confirmations')->nullable();
                $table->string('tx_hash')->nullable();
                $table->integer('is_sent')->nullable();
                $table->string('tx_unix_timestamp')->nullable();
                $table->string('tx_ts')->nullable();
                $table->text('reason')->nullable();
                $table->enum('status',['Pending', 'Completed' , 'Declined']);
                $table->integer('is_notified')->default(0);
                $table->enum('callback_status',['None', 'Pending', 'Completed' ]);
                $table->timestamp('created_at')->useCurrent();
                $table->timestamp('updated_at')->useCurrentOnUpdate()->nullable();
                $table->index(['user_wallet_id','celoxo_transaction_id','category','address','amount','confirmations','tx_unix_timestamp'],'transaction_index');
                $table->unique(['address', 'tx_hash', 'amount']);
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
        Schema::dropIfExists('transactions');
    }
}
