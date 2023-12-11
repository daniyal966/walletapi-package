<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateErrorLogsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        if ( !Schema::hasTable('error_logs') ) {
            Schema::create('error_logs', function (Blueprint $table) {
                $table->id();
                $table->longText('error')->nullable();
                $table->string('method')->nullable();
                $table->timestamp('created_at')->useCurrent();
                $table->index(['method', 'created_at'], 'error_index');
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
        Schema::dropIfExists('error_logs');
    }
}
