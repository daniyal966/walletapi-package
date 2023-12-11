<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateAffiliatesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        if ( !Schema::hasTable('affiliates') ) {
            Schema::create('affiliates', function (Blueprint $table) {
                $table->id();
                $table->string('name');
                $table->string('email')->unique();
                $table->string('password');
                $table->integer('is_locked');
                $table->integer('affiliate_code');
                $table->enum('source',['QB', 'PURSER', 'CELOXO', 'CP', 'BCP' , 'WI' , 'DS' , 'FB']);
                $table->longText('token')->nullable();
                $table->integer('request_limit')->default(160);
                $table->timestamp('created_at')->useCurrent();
                $table->timestamp('updated_at')->useCurrentOnUpdate()->nullable();
                $table->index(['email'],'affiliate_index');
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
        Schema::dropIfExists('affiliates');
    }
}
