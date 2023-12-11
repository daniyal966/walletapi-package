<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateCeloxoAddressesQbdTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        if ( !Schema::hasTable('celoxo_addresses_qbd') ) {
            Schema::create('celoxo_addresses_qbd', function (Blueprint $table) {
                $table->id();
                $table->string('request_id')->nullable();
                $table->string('sending_address');
                $table->integer('is_coin_sent');
                $table->string('address_type');
                $table->timestamp('created_at')->useCurrent();
                $table->index(['request_id','sending_address'],'celoxo_transaction_index');
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
        Schema::dropIfExists('celoxo_addresses_qbd');
    }
}
