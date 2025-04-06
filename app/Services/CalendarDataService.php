<?php

namespace App\Services;

use App\Models\CalendarWebhookIds;
use App\Models\Event;
use App\Models\EventsCalendarsMap;
use App\Models\Gcalendar;
use App\Models\GcalendarService;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;

class CalendarDataService
{

    public function getCalendarEvents($data, $calendar_id)
    {
        if ($calendar_id == 120) {
            $calendar_id = 7;
        }

        $calendar = Gcalendar::find($calendar_id);

        if (isset($data['month'])) {
            $dateStart = Carbon::parse($data['month']);
        } else {
            $dateStart = new Carbon();
        }


        $timeMin = $dateStart->format('Y-m') . '-01T00:00:00-00:00';
        $timeMax = $dateStart->endOfMonth()->format('Y-m-t') . 'T23:59:00-00:00';

        $gCalendarService = GcalendarService::setService();

        $year = $dateStart->format('Y');
        $month = $dateStart->format('m');

        $calendarApi = CalendarWebhookIds::where('calendarId', $calendar_id)->first();

        if (!$calendarApi) {

            $newCalendarWebhook = new CalendarWebhookIds();
            $newCalendarWebhook->calendarId = $calendar_id;
            $chanelId = $gCalendarService->getWebhookChanel($calendar->gcalendarId);
            if ($chanelId) {
                $newCalendarWebhook->chanelId = $chanelId;
            } else {
                $newCalendarWebhook->method = 'api';
            }
            $newCalendarWebhook->save();


            $calendarApi = CalendarWebhookIds::where('calendarId', $calendar_id)->first();
        }

        $calendarApiData = $calendarApi->toArray();

        $eventsMonthMap = EventsCalendarsMap::where('calendarId', $calendar_id)
            ->where('year', $year)
            ->where('month', $month)
            ->first();

        $lastUpdate = $calendarApiData['lastUpdate'];
        $chanelId = $calendarApi->chanelId;
        $idData = explode('-', $chanelId);

        if (!isset($idData[1])) {
            $new_chenelId = $gCalendarService->getWebhookChanel($calendar->gcalendarId, $calendarApi->chanelId);
            $calendarApi->chanelId = $new_chenelId;
            $calendarApi->save();
            $resEvents = $this->updateCalendarEvents($calendar_id, $calendar->gcalendarId, $year, $month, $lastUpdate, $timeMin, $timeMax);
            $lastUpdate = $calendarApiData['lastUpdate'];
            $chanelId = $calendarApi->chanelId;
            $idData = explode('-', $chanelId);

            if ($data['test']) {
//                dd($calendarApi->chanelId);
            }
        }

        $expiration = $idData[1]/1 + 3600*24*6;


        $carbonDate = Carbon::createFromTimestamp($expiration);
        if (isset($data['test'])) {
            print_r("<p>time life chanel to - {$carbonDate->format('Y-m-d h:i:s')}</p>");
        }

        if ($expiration <= time() && !empty($lastUpdate)) {
            $new_chenelId = $gCalendarService->getWebhookChanel($calendar->gcalendarId, $calendarApi->chanelId);
            $calendarApi->chanelId = $new_chenelId;
            $calendarApi->save();
            print_r("<p>update chanel to - {$carbonDate->format('Y-m-d h:i:s')} - $calendarApi->chanelId</p>");
            Log::channel('api_daily')->info("channel updateWebhookChanel $calendar_id [$lastUpdate] #".$calendarApi->chanelId);
        }

        $source = '';
        if (!$eventsMonthMap
            || ($eventsMonthMap && $eventsMonthMap->lastUpdate != $lastUpdate)
            || $expiration <= time()
            || isset($data['test'])
        ) {

            $source = 'updated';
            if (isset($data['test'])) {
                print_r("<p>UPDATE</p>");
            }

            $resEvents = $this->updateCalendarEvents($calendar_id, $calendar->gcalendarId, $year, $month, $lastUpdate, $timeMin, $timeMax);

        } else {
            $source = 'db';
            $eventsDatesIds = json_decode($eventsMonthMap->eventsDatesIds, true);
            $eventsIds = [];
            foreach ($eventsDatesIds as $date => $data) {
                foreach ($data as $eventId => $v) {
                    if (!isset($eventsIds[$eventId])) {
                        $event = Event::where('calendarId', $calendar_id)->where('eventId', $eventId)->first();
                        if (!$event) {
                            $ids = explode('_', $eventId);
                            $eventId = $ids[0];
                            if (!isset($eventsIds[$eventId])) {
                                $event = Event::where('calendarId', $calendar_id)->where('eventId', $eventId)->first();
                            }
                        }
                        if ($event) {
                            $eventsIds[$eventId] = json_decode($event->data, true);
                        }
                    }

                }
            }
            $resEvents['dates'] = $eventsDatesIds;
            $resEvents['events'] = $eventsIds;
            $resEvents['source'] = $source;
        }

        return $resEvents;
    }


    private function  updateCalendarEvents($calId, $gCalendarId, $year, $month, $lastUpdate, $timeMin, $timeMax)
    {
        $gCalendarService = GcalendarService::setService();

        $eventsOnce = $gCalendarService->getCalendarEventsDaysDataOnce($gCalendarId, $timeMin, $timeMax);
        foreach ($eventsOnce as $eventId => $eventOne) {
            $event = Event::firstOrCreate([
                'eventId' => $eventId,
                'calendarId' => $calId
            ]);

            if ($eventOne->status != "cancelled") {
                $event->name = $eventOne->summary;
            }

            if (isset($eventOne->description)) {
                $eventOne->description = str_replace('<br>', '\\n', $eventOne->description);
                $eventOne->description = strip_tags($eventOne->description);
            }




            $event->lastUpdate = $lastUpdate;
            $event->data = json_encode($eventOne);
            $event->save();
            if ($eventOne->status == "cancelled") {
                unset($eventsOnce[$eventId]);
            }
        }

        $events = $gCalendarService->getCalendarEventsDaysData($gCalendarId, $timeMin, $timeMax);

        $eventsIds = [];
        foreach ($events as $date => $data) {
            $eventsIds[$date] = [];
            foreach ($data as $event) {
                $eventId = $event['eventId'];
                if (!isset($eventsOnce[$eventId])) {
                    $eventId = str_replace('@google.com', '', $event['ICalUID']);
                }

                $eventsIds[$date][$eventId] = [
                    'timeUse' => $event['timeUse'],
                    'dateStart' => $event['dateStart'],
                    'timeStart' => $event['timeStart'],
                    'dateEnd' => $event['dateEnd'],
                    'timeEnd' => $event['timeEnd'],
                ];

            }
        }

        $eventsMap = EventsCalendarsMap::firstOrCreate([
            'calendarId' => $calId,
            'year' => $year,
            'month' => $month,
        ]);
        $eventsMap->lastUpdate = $lastUpdate;
        $eventsMap->eventsDatesIds = json_encode($eventsIds);
        $eventsMap->save();

        $res['dates'] = $eventsIds;
        $res['events'] = $eventsOnce;

        return $res;
    }
}
