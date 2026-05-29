<?php

namespace App\Console\Commands;

use App\Models\CalendarWebhookIds;
use App\Models\GcalendarService;
use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class RenewCalendarWebhooks extends Command
{
    protected $signature = 'calendar:webhooks-renew';
    protected $description = 'Renew expiring Google Calendar webhook channels';

    public function handle()
    {
        Log::channel('cron_commands')->info($this->description);

        $webhooks = CalendarWebhookIds::query()
            ->where(function ($query) {
                $query->whereNull('expires_at')
                    ->orWhereNull('chanelId')
                    ->orWhereNull('resourceId')
                    ->orWhere('expires_at', '<=', now()->addDay());
            })
            ->get();

        if ($webhooks->count() > 0) {

            $gCalendarService = GcalendarService::setService();
            foreach ($webhooks as $webhook) {

                try {
                    if (!empty($webhook->resourceId)) {
                        $gCalendarService->stopWebhookChanel($webhook->chanelId, $webhook->resourceId);
                    }
                } catch (\Throwable $e) {

                }

                $webhook_data = $gCalendarService->getWebhookChanel($webhook->calendar->gcalendarId);
                if (isset($webhook_data['id'])) {
                    $webhook->data = $webhook_data;
                    $webhook->chanelId = $webhook_data['id'];
                    $webhook->expires_at = isset($webhook_data['expiration']) ? \Illuminate\Support\Carbon::createFromTimestampMs($webhook_data['expiration']) : null;
                    $webhook->resourceId = $webhook_data['resourceId'] ?? null;
                    $webhook->resourceUri = $webhook_data['resourceUri'] ?? null;
                    $webhook->last_webhook_at = Carbon::now();
                } else {
                    $webhook->resourceId = null;
                }
                $webhook->save();
            }
        }
        return self::SUCCESS;
    }
}
