<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateAffiliateCallbackLogs extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        if ( !Schema::hasTable('affiliate_callback_logs') ) {
            Schema::create('affiliate_callback_logs', function (Blueprint $table) {
                $table->id();
                $table->bigInteger('transaction_id')->nullable();
                $table->bigInteger('quantoz_transaction_code')->nullable();
                $table->boolean('callback_sent')->default(0);
                $table->text('data')->nullable();
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
        Schema::dropIfExists('affiliate_callback_logs');
    }
}
