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
        Schema::table('events', function (Blueprint $table) {
            $table->string('slug')->nullable()->unique()->after('name');
            $table->boolean('is_regular')->default(false)->after('custom_data');
            $table->dateTime('event_start_at')->nullable()->after('is_regular');
            $table->dateTime('event_end_at')->nullable()->after('event_start_at');
            $table->dateTime('registration_start_at')->nullable()->after('event_end_at');
            $table->dateTime('registration_end_at')->nullable()->after('registration_start_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('events', function (Blueprint $table) {
            $table->dropColumn([
                'slug',
                'is_regular',
                'event_start_at',
                'event_end_at',
                'registration_start_at',
                'registration_end_at',
            ]);
        });
    }
};
