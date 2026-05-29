<?php

namespace App\Services;

use App\Jobs\ProcessCalendarEvents;
use App\Models\CalendarWebhookIds;
use App\Models\Event;
use App\Models\EventsCalendarsMap;
use App\Models\FcmToken;
use App\Models\Gcalendar;
use App\Models\GcalendarService;
use App\Models\MessagesSubscribes;
use App\Models\UserToken;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Log;

class CalendarDataService
{
    public function getCalendarEvents($data, $calendar_id)
    {
        if ($calendar_id == 120) {
            $calendar_id = 7;
        }

        if (isset($data['month'])) {
            $dateStart = Carbon::parse($data['month']);
        } else {
            $dateStart = new Carbon();
        }

        ProcessCalendarEvents::dispatch($dateStart, $calendar_id);

        $year = $dateStart->format('Y');
        $month = $dateStart->format('m');


        return $this->getDbEventsData($calendar_id, $year, $month);
    }
    public function getCalendarEventsNew($data, $calendar_id)
    {
        if ($calendar_id == 120) {
            $calendar_id = 7;
        }

        $calendar = Gcalendar::find($calendar_id);

        if ($calendar) {
            if (isset($data['month'])) {
                $dateStart = Carbon::parse($data['month']);
            } else {
                $dateStart = new Carbon();
            }

            $calendarApi = $this->getCalendarWebhookDate($calendar);

            $actual_time = $calendarApi->last_webhook_at;

            $year = $dateStart->format('Y');
            $month = $dateStart->format('m');

            $eventsMonthMap = EventsCalendarsMap::where('calendarId', $calendar_id)
                ->where('year', $year)
                ->where('month', $month)
                ->first();

            if (!$eventsMonthMap || $eventsMonthMap->updated_at < $actual_time) {
                $timeMin = $dateStart->copy()->startOfMonth()->format('Y-m-d\T00:00:00P');
                $timeMax = $dateStart->copy()->endOfMonth()->format('Y-m-d\T23:59:00P');

                $this->updateCalendarEventsDb($calendar_id, $calendar->gcalendarId, $year, $month, $actual_time->timestamp, $timeMin, $timeMax);
            }
        }

        return $this;
    }
    public function getCalendarEventsDb($data, $calendar_id)
    {
        if ($calendar_id == 120) {
            $calendar_id = 7;
        }

        if (isset($data['month'])) {
            $dateStart = Carbon::parse($data['month']);
        } else {
            $dateStart = new Carbon();
        }

//        $this->getCalendarEventsNew($data, $calendar_id);
        ProcessCalendarEvents::dispatch($dateStart, $calendar_id);

        $year = $dateStart->format('Y');
        $month = $dateStart->format('m');

        return $this->getDbEventsData($calendar_id, $year, $month);
    }

    private function getDbEventsData($calendar_id, $year, $month)
    {

        $eventsMonthMap = EventsCalendarsMap::where('calendarId', $calendar_id)
            ->where('year', $year)
            ->where('month', $month)
            ->first();

        if ($eventsMonthMap) {
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
                            $event_data = json_decode($event->data, true);
                            $event_data['calendar_id'] = $calendar_id;
                            $eventsIds[$eventId] = $event_data;
                        }
                    }

                }
            }
            $resEvents['dates'] = $eventsDatesIds;
            $resEvents['events'] = $eventsIds;
            $resEvents['events_count'] = $eventsMonthMap->events_count;
            $resEvents['source'] = $source;
            $resEvents['last_update'] = $eventsMonthMap->updated_at->format('Y-m-d H:i:m');

        } else {
            $resEvents = [];
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

    private function  updateCalendarEventsDb($calId, $gCalendarId, $year, $month, $lastUpdate, $timeMin, $timeMax)
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
        $eventsMap->events_count =  sizeof($eventsOnce);
        $eventsMap->lastUpdate = $lastUpdate;
        $eventsMap->eventsDatesIds = json_encode($eventsIds);
        $eventsMap->save();

        return $this;
    }

    public function addEvent($data)
    {

        if (!empty($data['calendars']) && !empty($data['event'])) {

            Log::channel('api_daily')->info("addEvent - " . json_encode($data));

            $gCalendarService = GcalendarService::setService();


            $date_start = Carbon::parse($data['event']['start']['dateTime']);
            $event_date_start = $date_start->format('Y-m-d H:i:s');
            $date_end = Carbon::parse($data['event']['start']['dateTime']);
            $event_date_end = $date_start->format('Y-m-d H:i:s');

            foreach ($data['calendars'] as $calId) {

                if ($calId == 120) {
                    $calId = 7;
                }

                $calendar = Gcalendar::find($calId);
                $gcalendarId = $calendar->gcalendarId;
                $calendarData = $gCalendarService->getCalendar($calendar->gcalendarId);

                if (!isset($data['event']['start']['timeZone'])) {
                    $data['event']['start']['timeZone'] = "UTC";
                    $data['event']['end']['timeZone'] = "UTC";

                    $data['event']['start']['dateTime'] = self::reFormetedDateTimeByTimzone($data['event']['start']['dateTime'], $calendarData->getTimeZone());
                    $data['event']['end']['dateTime'] = self::reFormetedDateTimeByTimzone($data['event']['end']['dateTime'], $calendarData->getTimeZone());

                }

                if (isset($data['calendarsImportData'])
                    && !empty($data['calendarsImportData'])
                    && isset($data['calendarsImportData'][$calId])
                    && isset($data['calendarsImportData'][$calId]['eventId']))
                {

                    $eventId = $data['calendarsImportData'][$calId]['eventId'];


                    if (isset($data['calendarsImportData'][$calId]['importEventData'])) {
                        foreach ($data['calendarsImportData'][$calId]['importEventData'] as $field => $value) {
                            $data['event'][$field] = $value;
                        }
                    }

                    try {
                        $eventId = $gCalendarService->updateEventToCalendarOne($gcalendarId, $eventId, $data['event'], $data['dateStart']);
                        $dataRes[] = [
                            'eventId' => $eventId,
                            'calId' => $calId,
                            'import' => true
                        ];

                        $this->eventsSubscribeMessage(
                            'update_event',
                            '📅🔄 ' . $calendarData->summary,
                            $data['event']['name']
                            . "\n 🌍 " . $calendar->country
                            . "\n 🌆 " . $calendar->city
                            . "\n 📍 " . $data['event']['location']
                            . "\n 🕙 from " . $event_date_start
                            . ' to ' . $event_date_end,
                            [
                                'eventId' => $eventId,
                                'calId' => $calId,
                            ]
                        );

                    } catch (\Exception $e) {
                        $errorsMessage = $e->getMessage();
                        Log::channel('api_daily')->info("addEventV1 Exception - " . $errorsMessage);

                    }

                } else {

                    try {
                        $eventId = $gCalendarService->addEventToCalendar($gcalendarId, $data['event']);
                        $dataRes[] = [
                            'eventId' => $eventId,
                            'calId' => $calId
                        ];

                        $this->eventsSubscribeMessage(
                            'create_event',
                            '📅➕ ' . $calendarData->summary,
                            $data['event']['name']
                            . "\n 🌍 " . $calendar->country
                            . "\n 🌆 " . $calendar->city
                            . "\n 📍 " . $data['event']['location']
                            . "\n 🕙 from " . $event_date_start
                            . ' to ' . $event_date_end,
                            [
                                'eventId' => $eventId,
                                'calId' => $calId,
                            ]
                        );

                    } catch (\Exception $e) {
                        $errorsMessage = $e->getMessage();
                        Log::channel('api_daily')->info("addEventV1 Exception - " . $errorsMessage);

                    }

                }

            }
            if (isset($dataRes)) {
                $res = [
                    'success' => true,
                    'data' => $dataRes
                ];
            } else {
                $res = [
                    'success' => false,
                    'errorMessage' => $errorsMessage
                ];
            }

            return $res;
        }
    }


    private function eventsSubscribeMessage($event_type, $title, $body, $data = [])
    {

        $firebase = new FirebaseFirestoreService();
        $firebase->setMessaging();

        switch ($event_type) {

            case('create_event'):
            case('update_event'):
            case('delete_event'):

                $subscribes = MessagesSubscribes::where('event_subscribe', $event_type)->get();
                $cal_id = $data['calId'] ?? '';

                if ($subscribes) {
                    foreach ($subscribes as $uid_data) {
                        $uid = $uid_data->user_uid;
                        $data_subscribe = $uid_data->data_subscribe;

                        $subscribe_calendars = $data_subscribe['calendars'] ?? [];
                        $calendars_ids = array_flip($subscribe_calendars);


                        if (isset($calendars_ids[$cal_id])) {
                            $fcm_tokens_data = FcmToken::where('user_uid', $uid)->first();
                            $fcm_tokens = $fcm_tokens_data->fcm_tokens ?? [];

                            if (sizeof($fcm_tokens) > 0) {
                                foreach ($fcm_tokens as $token) {
                                    $result[] = $firebase->sendNotification($token, $title, $body, $data);
                                }
                            }
                        }
                    }
                }
                break;

            default:

                $user_uids = UserToken::whereIn('userRole', ['su_admin', ])->select('userUid')->get();

                if ($user_uids) {
                    foreach ($user_uids as $uid_data) {
                        $uid = $uid_data->userUid;
                        $fcm_tokens_data = FcmToken::where('user_uid', $uid)->first();
                        $fcm_tokens = $fcm_tokens_data->fcm_tokens ?? [];

                        if (sizeof($fcm_tokens) > 0) {
                            foreach ($fcm_tokens as $token) {
                                $result[] = $firebase->sendNotification($token, $title, $body, $data);
                            }
                        }

                    }
                }

        }

        Log::channel('api_daily')->info("eventsSubscribeMessage - Results", $result);

        return $result;
    }

    public function getCalendarWebhookDate($calendar): CalendarWebhookIds
    {
        $gCalendarService = GcalendarService::setService();
        $calendarApi = CalendarWebhookIds::where('calendarId', $calendar->id)->first();


        if (!$calendarApi) {
            $calendarApi = new CalendarWebhookIds();
            $calendarApi->calendarId = $calendar->id;
            $calendarApi->save();
        }

        if (empty($calendarApi->data)) {
            $webhook_data = $gCalendarService->getWebhookChanel($calendar->gcalendarId);

            if (isset($webhook_data['id'])) {
                $calendarApi->data = $webhook_data;
                $calendarApi->chanelId = $webhook_data['id'];
                $calendarApi->expires_at = isset($webhook_data['expiration']) ? Carbon::createFromTimestampMs($webhook_data['expiration']) : null;
                $calendarApi->resourceId = $webhook_data['resourceId'] ?? null;
                $calendarApi->resourceUri = $webhook_data['resourceUri'] ?? null;
                $calendarApi->last_webhook_at = Carbon::now();
            }
            $calendarApi->save();
        }

        return $calendarApi;
    }
}
