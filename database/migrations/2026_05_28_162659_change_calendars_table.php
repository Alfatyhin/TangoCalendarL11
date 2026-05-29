<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('gcalendars', function (Blueprint $table) {
            $table->string('name')->nullable()->after('gcalendarId');
            $table->string('slug')->nullable()->unique()->after('name');
            $table->text('description')->nullable()->after('slug');
            $table->json('data')->nullable()->after('description');
            $table->dateTime('last_sync_at')->nullable()->after('data');
            $table->dateTime('google_info_synced_at')->nullable()->after('last_sync_at');
            $table->dateTime('google_info_sync_failed_at')->nullable()->after('google_info_synced_at');
            $table->unsignedInteger('google_info_sync_attempts')->default(0)->after('google_info_sync_failed_at');
            $table->text('google_info_sync_error')->nullable()->after('google_info_sync_attempts');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('gcalendars', function (Blueprint $table) {
            $table->dropColumn([
                'name',
                'slug',
                'description',
                'data',
                'last_sync_at',
                'google_info_synced_at',
                'google_info_sync_failed_at',
                'google_info_sync_attempts',
                'google_info_sync_error',
            ]);
        });
    }
};
