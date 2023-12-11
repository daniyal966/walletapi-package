<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateAddressesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        if ( !Schema::hasTable('addresses') ) {
            Schema::create('addresses', function (Blueprint $table) {
                $table->id();
                $table->string('request_id')->nullable()->unique();
                $table->enum('address_type',['LTC', 'BTC', 'BCH', 'SOL']);
                $table->foreignId('user_wallet_id')->nullable()->constrained('user_wallets');
                $table->foreignId('user_id')->nullable()->constrained('users');
                $table->integer('is_locked')->nullable();
                $table->string('address');
                $table->enum('daemon_used',['old', 'new', 'v4', 'ETH', 'v5', 'v6', 'v7']);
                $table->string('label')->nullable();
                $table->string('contract')->nullable();
                $table->timestamp('created_at')->useCurrent();
                $table->timestamp('updated_at')->useCurrentOnUpdate()->nullable();
                $table->index(['address', 'address_type','request_id','user_wallet_id'],'address_index');
                $table->string('psp')->nullable();
                $table->text('password')->nullable();
                $table->text('encrypted_json')->nullable();
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
        Schema::dropIfExists('addresses');
    }
}
