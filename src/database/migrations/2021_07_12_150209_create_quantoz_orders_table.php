<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateQuantozOrdersTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        if ( !Schema::hasTable('quantoz_orders') ) {
            Schema::create('quantoz_orders', function (Blueprint $table) {
                $table->id();
                $table->string('request_id');
                $table->string('quantoz_account_guid')->nullable();
                $table->string('quantoz_account_code')->nullable();
                $table->string('quantoz_transaction_code')->nullable();
                $table->string('crypto_currency_code')->nullable();
                $table->decimal('crypto_amount',100,8)->default(0.00000000)->nullable();
                $table->decimal('received_crypto_amount',100,8)->default(0.00000000)->nullable();
                $table->string('fiat_currency_code')->nullable();
                $table->decimal('fiat_amount',100,8)->default(0.00000000)->nullable();
                $table->string('transaction_address')->nullable();
                $table->string('transaction_hash')->nullable();
                $table->string('quantoz_customer_code')->nullable();
                $table->enum('transaction_status',['Pending', 'Completed', 'Declined', 'In Process'])->default('Pending');
                $table->decimal('network_fee',100,8)->default(0.00000000)->nullable();
                $table->decimal('service_fee',100,8)->default(0.00000000)->nullable();
                $table->string('merchant_email')->nullable();
                $table->text('callback_url')->nullable();
                $table->timestamp('created_at')->useCurrent();
                $table->timestamp('updated_at')->useCurrent()->useCurrentOnUpdate()->nullable();
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
        Schema::dropIfExists('quantoz_orders');
    }
}
