<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateEventsCalendarsMapsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('events_calendars_maps', function (Blueprint $table) {
            $table->id();
            $table->string('calendarId')->index();
            $table->string('lastUpdate')->nullable();
            $table->string('year');
            $table->string('month');
            $table->json('eventsDatesIds')->nullable();
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
        Schema::dropIfExists('events_calendars_maps');
    }
}
