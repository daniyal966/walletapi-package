<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateRatesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        if ( !Schema::hasTable('rates') ) {
            Schema::create('rates', function (Blueprint $table) {
                $table->id();
                $table->string('crypto_currency');
                $table->string('fiat_currency');
                $table->double('rate');
                $table->timestamp('created_at')->useCurrent();
                $table->timestamp('updated_at')->useCurrentOnUpdate()->nullable();
                $table->index(['crypto_currency','fiat_currency'],'rate_index');
                $table->unique(['crypto_currency', 'fiat_currency'],'unique_rates_constraint');
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
        Schema::dropIfExists('rates');
    }
}
