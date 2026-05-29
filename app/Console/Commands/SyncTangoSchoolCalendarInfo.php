<?php

namespace App\Console\Commands;

use App\Models\Gcalendar;
use App\Models\GcalendarService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Throwable;

class SyncTangoSchoolCalendarInfo extends Command
{
    protected $signature = 'calendars:sync-tango-school-info';

    protected $description = 'Sync one outdated tango school calendar name, description, and slug from Google Calendar.';

    public function handle(): int
    {
        $calendar = Gcalendar::query()
            ->tangoSchools()
            ->where(function ($query) {
                $query->whereNull('google_info_synced_at')
                    ->orWhere('google_info_synced_at', '<', now()->subDays(5));
            })
            ->where(function ($query) {
                $query->whereNull('google_info_sync_failed_at')
                    ->orWhere('google_info_sync_failed_at', '<', now()->subHours(6));
            })
            ->orderByRaw('google_info_synced_at IS NOT NULL')
            ->orderBy('google_info_synced_at')
            ->orderBy('id')
            ->first();

        if (! $calendar) {
            $this->info('No tango school calendars need Google info sync.');

            return self::SUCCESS;
        }

        try {
            $calendarInfo = GcalendarService::setService()->getCalendarInfo($calendar->gcalendarId);

            $calendar->forceFill([
                'name' => $calendarInfo['name'] ?? $calendar->name,
                'description' => $calendarInfo['description'] ?? $calendar->description,
                'google_info_synced_at' => now(),
                'google_info_sync_failed_at' => null,
                'google_info_sync_attempts' => 0,
                'google_info_sync_error' => null,
            ]);

            $calendar->ensureSlug();
            $calendar->save();

            Cache::forget('calendar_info_' . $calendar->id);

            $this->info("Synced tango school calendar #{$calendar->id}.");

            return self::SUCCESS;
        } catch (Throwable $exception) {
            $calendar->forceFill([
                'google_info_sync_failed_at' => now(),
                'google_info_sync_attempts' => ((int) $calendar->google_info_sync_attempts) + 1,
                'google_info_sync_error' => Str::limit($exception->getMessage(), 1000),
            ])->save();

            Log::warning('Failed to sync tango school calendar Google info.', [
                'calendar_id' => $calendar->id,
                'gcalendar_id' => $calendar->gcalendarId,
                'exception' => $exception,
            ]);

            $this->error("Failed to sync tango school calendar #{$calendar->id}: {$exception->getMessage()}");

            return self::FAILURE;
        }
    }
}
