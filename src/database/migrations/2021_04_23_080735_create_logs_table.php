<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateLogsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        if ( !Schema::hasTable('logs') ) {
            Schema::create('logs', function (Blueprint $table) {
                $table->id();
                $table->foreignId('user_id')->nullable()->constrained('users');
                $table->string('log_type');
                $table->longText('raw_request')->nullable();
                $table->longText('raw_response')->nullable();
                $table->longText('raw_header')->nullable();
                $table->longText('raw_user_agent')->nullable();
                $table->string('request_id')->nullable();
                $table->string('process_state')->nullable();
                $table->timestamp('created_at')->useCurrent();
                $table->timestamp('updated_at')->useCurrentOnUpdate()->nullable();
                $table->index(['user_id','log_type','request_id','created_at'],'log_index');
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
        Schema::dropIfExists('logs');
    }
}
