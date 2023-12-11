<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateStatusCodesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        if ( !Schema::hasTable('status_codes') ) {
            Schema::create('status_codes', function (Blueprint $table) {
                $table->id();
                $table->integer('status_code');
                $table->text('message');
                $table->string('error');
                $table->timestamp('created_at')->useCurrent();
                $table->index(['status_code'],'status_code_index');
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
        Schema::dropIfExists('status_codes');
    }
}
