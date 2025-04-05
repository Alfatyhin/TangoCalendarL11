<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateCalendarWebhookIdsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('calendar_webhook_ids', function (Blueprint $table) {
            $table->id();
            $table->string('calendarId')->index();
            $table->string('method')->default('webhook');
            $table->string('chanelId')->nullable();
            $table->string('lastUpdate')->default(time());
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
        Schema::dropIfExists('calendar_webhook_ids');
    }
}
