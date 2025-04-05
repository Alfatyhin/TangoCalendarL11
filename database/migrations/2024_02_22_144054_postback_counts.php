<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class PostbackCounts extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('postback_counts', function (Blueprint $table) {
            $table->id();
            $table->string('btag');
            $table->string('pid');
            $table->mediumInteger('ps_id')->default(1);
            $table->mediumInteger('reg_count')->default(0);
            $table->mediumInteger('dep_count')->default(0);
            $table->mediumInteger('dep_summ')->default(0);
            $table->string('last_postback')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        //
    }
}
