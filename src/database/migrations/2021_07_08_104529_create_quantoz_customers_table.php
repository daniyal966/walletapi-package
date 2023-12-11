<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateQuantozCustomersTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        if ( !Schema::hasTable('quantoz_customers') ) {
            Schema::create('quantoz_customers', function (Blueprint $table) {
                $table->id();
                $table->bigInteger('user_id');
                $table->string('customer_code')->unique();
                $table->string('guid')->nullable();
                $table->enum('trust_level', ['New', 'Trusted', 'Identified'])->default('Trusted');
                $table->enum('customer_status', ['ACTIVE', 'NEW'])->default('ACTIVE');
                $table->string('account_code')->nullable();
                $table->string('currency_code')->nullable();
                $table->string('crypto_currency_code')->nullable();
                $table->string('dc_Code')->nullable();
                $table->string('customer_crypto_address')->nullable();
                $table->enum('account_status',['NEW', 'INVALID', 'VALID', 'ACTIVE', 'DELETED'])->default('ACTIVE');
                $table->string('merchant_email');
                $table->timestamp('created_at')->useCurrent();
                $table->timestamp('updated_at')->useCurrent()->useCurrentOnUpdate()->nullable();
                $table->index(['user_id', 'customer_crypto_address']);
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
        Schema::dropIfExists('quantoz_customers');
    }
}
