<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateAddressBalanceTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('address_balance', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_wallet_id')->nullable()->constrained('user_wallets');
            $table->string('address');
            $table->decimal('amount',100,8)->default(0.00000000);
            $table->timestamp('created_at')->useCurrent();
            $table->timestamp('updated_at')->useCurrentOnUpdate()->nullable();
            $table->index(['user_wallet_id', 'address', 'amount']);
            $table->unique(['address']);
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('address_balance');
    }
}
