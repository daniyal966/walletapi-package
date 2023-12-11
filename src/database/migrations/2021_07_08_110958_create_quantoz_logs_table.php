<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateQuantozLogsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        if ( !Schema::hasTable('quantoz_logs') ) {
            Schema::create('quantoz_logs', function (Blueprint $table) {
                $table->id();
                $table->bigInteger('order_id')->nullable();
                $table->string('request_id')->nullable();
                $table->string('customer_code')->nullable();
                $table->longText('raw_request')->nullable();
                $table->longText('raw_response')->nullable();
                $table->boolean('callback_sent')->nullable();
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
        Schema::dropIfExists('quantoz_logs');
    }
}
