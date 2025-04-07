<?php

namespace App\Services;

use App\Models\CalendarWebhookIds;
use App\Models\Event;
use App\Models\EventsCalendarsMap;
use App\Models\FcmToken;
use App\Models\Gcalendar;
use App\Models\GcalendarService;
use App\Models\MessagesSubscribes;
use App\Models\UserToken;
use Carbon\Carbon;
use Illuminate\Http\Request;
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
}
