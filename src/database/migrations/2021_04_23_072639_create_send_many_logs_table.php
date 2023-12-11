<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateSendManyLogsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        if ( !Schema::hasTable('send_many_logs') ) {
            Schema::create('send_many_logs', function (Blueprint $table) {
                $table->id();
                $table->longText('raw_request')->nullable();
                $table->longText('raw_response')->nullable();
                $table->string('batch_id')->nullable();
                $table->timestamp('created_at')->useCurrent();
                $table->index(['batch_id','created_at'],'bulk_transaction_log');
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
        Schema::dropIfExists('send_many_logs');
    }
}
