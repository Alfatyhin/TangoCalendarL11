<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('calendar_webhook_ids', function (Blueprint $table) {
            $table->json('data')->nullable();
            $table->timestamp('expires_at')->nullable();
            $table->timestamp('last_webhook_at')->nullable();
            $table->string('resourceId')->nullable()->index();
            $table->string('resourceUri')->nullable();
            $table->unique('calendarId');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        //
    }
};
