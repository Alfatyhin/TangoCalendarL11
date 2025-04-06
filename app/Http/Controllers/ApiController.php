<?php

namespace App\Http\Controllers;

use App\Models\CalendarWebhookIds;
use App\Models\Event;
use App\Models\EventsCalendarsMap;
use App\Models\EventTranslate;
use App\Models\FcmToken;
use App\Models\Gcalendar;
use App\Models\GcalendarService;
use App\Models\MessagesSubscribes;
use App\Models\UserToken;
use App\Services\CalendarDataService;
use App\Services\ClientBaseService;
use App\Services\FirebaseFirestoreService;
use App\Services\StructuredQuery;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

class ApiController
{
    private $apiKeyAi;

    private $calendarDataService;

    public function __construct()
    {
        $this->apiKeyAi = config('services.open_ai.key');
        $this->calendarDataService = new CalendarDataService();
    }

    private static function getApiKeyAi()
    {
        return config('services.open_ai.key');
    }

    public function getServerTimeSigneg()
    {
        $date = new Carbon();
        return $date->format('Y-m-d-H');
    }



    public function firebaseSendMessage(Request $request)
    {
        $data = $request->all();

        Log::channel('api_daily')->debug('firebaseSendMessage', $data);

        $status = 'error';
        $result = ['message not send'];
        try {
            if (isset($data['action'])) {

                $firebase = new FirebaseFirestoreService();
                $firebase->setMessaging();

                switch ($data['action']) {
                    case 'statements':

                        $user_uids = UserToken::whereIn('userRole', ['su_admin', 'admin'])->select('userUid')->get();
                        if ($user_uids) {
                            foreach ($user_uids as $uid_data) {
                                $uid = $uid_data->userUid;
                                $fcm_tokens_data = FcmToken::where('user_uid', $uid)->first();
                                $fcm_tokens = $fcm_tokens_data->fcm_tokens ?? [];

                                if (sizeof($fcm_tokens) > 0) {
                                    foreach ($fcm_tokens as $token) {
                                        $title = '🚀 Statement';
                                        $body = $data['type'] . " - " . $data['status'];
                                        $result[] = $firebase->sendNotification($token, $title, $body);
                                    }
                                }

                            }
                        }
                        $status = 'success';
                        break;
                }
            }
        } catch (\Exception $e) {
            $result = [$e->getMessage()];
            Log::channel('api_daily')->error('firebaseSendMessage - ', $result);
        }


        return response()->json(['status' => $status, 'result' => $result]);
    }


    public function addCfm(Request $request)
    {
        $data = $request->all();

        Log::channel('api_daily')->debug('addCfm', $data);

        $status = 'error';
        $result = 'user_uid not isset';
        try {
            if (isset($data['user_uid']) && isset($data['token_cfm'])) {
                $new_token = $data['token_cfm'];
                $fcm_tokens_data = FcmToken::firstOrCreate(['user_uid' => $data['user_uid']]);

                $status = 'success';
                $result = 'cfm token isset';
                $fcm_tokens = $fcm_tokens_data->fcm_tokens ?? [];
                $fcm_tokens_test = $fcm_tokens;
                if (sizeof($fcm_tokens) > 0) {
                    $fcm_tokens_test = array_flip($fcm_tokens);
                }

                if (!isset($fcm_tokens_test[$new_token])) {
                    $fcm_tokens = array_merge($fcm_tokens, [$new_token]);
                    $fcm_tokens_data->fcm_tokens = $fcm_tokens;
                    $fcm_tokens_data->save();
                    $result = 'cfm token add';
                }
            }
        } catch (\Exception $e) {
            $result = $e->getMessage();
            Log::channel('api_daily')->error('addCfm - ' . $result);
        }


        return response()->json(['status' => $status, 'message' => $result]);
    }


    public function addCfmTest(Request $request)
    {
        $data = $request->all();

        $status = 'error';
        $result = 'user_uid not isset';
            if (isset($data['user_uid']) && isset($data['token_cfm'])) {
                $new_token = $data['token_cfm'];
                $fcm_tokens_data = FcmToken::firstOrCreate(['user_uid' => $data['user_uid']]);

                $status = 'success';
                $result = 'cfm token isset';
                $fcm_tokens = $fcm_tokens_data->fcm_tokens ?? [];
                $fcm_tokens_test = $fcm_tokens;
                if (sizeof($fcm_tokens) > 0) {
                    $fcm_tokens_test = array_flip($fcm_tokens);
                }

                if (!isset($fcm_tokens_test[$new_token])) {
                    $fcm_tokens = array_merge($fcm_tokens, [$new_token]);
                    $fcm_tokens_data->fcm_tokens = $fcm_tokens;
                    $fcm_tokens_data->save();
                    $result = 'cfm token add';
                }
            }


        return response()->json(['status' => $status, 'message' => $result]);
    }

    public function getUserSubscribes(Request $request)
    {
        $data = $request->all();
        $status = 'error';
        $result = [];

        $subscribes = MessagesSubscribes::where('user_uid', $data['user_uid'])->get();

        if ($subscribes) {
            $status = 'success';
            $result = $subscribes->toArray();
        }

        return response()->json(['status' => $status, 'data' => $result] );
    }

    public function subscribeCalendarEvents(Request $request)
    {
        $data = $request->all();

        Log::channel('api_daily')->debug('subscribeCalendarEvents', $data);
        $status = 'error';
        if (isset($data['calendar_id'])) {
            $calendar_id = $data['calendar_id'];
            $event_types = $data['event_types'];

            foreach ($event_types as $type => $update) {
                $subscribe = MessagesSubscribes::firstOrCreate(
                    [
                        'user_uid' => $data['user_uid'],
                        'event_subscribe' => $type
                    ]
                );
                $data_subscribe = $subscribe->data_subscribe ?? [];
                $subscribe_calendars = collect($data_subscribe['calendars'] ?? []);

                if ($update) {
                    $subscribe_calendars->push($calendar_id)->unique();
                } else {
                    $subscribe_calendars = $subscribe_calendars->reject(fn($id) => $id == $calendar_id);
                }

                $data_subscribe['calendars'] = $subscribe_calendars->values()->all();

                $subscribe->data_subscribe = $data_subscribe;
                $subscribe->save();
            }

            $status = 'success';
        }

        return response()->json(['status' => $status]  );
    }

    public function getCalendarEvents(Request $request, $id)
    {
        $calendar = Gcalendar::find($id);
        if ($request->get('month')) {
            $dateStart = Carbon::parse($request->get('month'));
        } else {
            $dateStart = new Carbon();
        }


        $timeMin = $dateStart->format('Y-m') . '-01T00:00:00-00:00';
        $timeMax = $dateStart->endOfMonth()->format('Y-m-t') . 'T23:59:00-00:00';

        $gCalendarService = GcalendarService::setService();

        if ($request->get('test')) {

            $events = $gCalendarService->getCalendarEventsDaysData($calendar->gcalendarId, $timeMin, $timeMax);
            dd($events);
        }

        $events = $gCalendarService->getCalendarEventsDaysData($calendar->gcalendarId, $timeMin, $timeMax);
        return json_encode($events);
    }

    public function getCalendarEventV1(Request $request, $id)
    {
        try {

            $calendar = Gcalendar::find($id);


            $gCalendarService = GcalendarService::setService();

            $eventsOnce = $gCalendarService->getCalendarEvent($calendar->gcalendarId, $request->get('eventId'));

            $eventData = $eventsOnce->toSimpleObject();

            return json_encode($eventData);

        } catch (\Exception $e) {
            $res['error'] = $e->getMessage();

            return json_encode($res);
        }
    }

    public function getCalendarEventsV1(Request $request, $id)
    {
        $resEvents = $this->calendarDataService->getCalendarEvents($request->all(), $id);
        return json_encode($resEvents);
    }

    public function getCalendarUpdate(Request $request, $id)
    {

        $calendar = Gcalendar::where('id', $id)->first();

//        if ($request->has('test')) {
//
//            $gCalendarService = GcalendarService::setService();
//            $calendarData = $gCalendarService->getCalendar($calendar->gcalendarId);
//            $event = $gCalendarService->getEventById($calendar->gcalendarId, $request['eventId']);
//            $dateStart = Carbon::parse($event->getStart()->getDate() ?? $event->getStart()->getDateTime());
//            $event_date_start = $dateStart->format('Y-m-d');
//            dd($calendarData->summary, $event->getLocation(), $event->summary, $dateStart->format('Y-m-d'));
//        }

        if ($calendar) {

            $calendarApi = CalendarWebhookIds::where('calendarId', $id)->first();

            $gCalendarService = GcalendarService::setService();

            if (!$calendarApi) {

                $newCalendarWebhook = new CalendarWebhookIds();
                $newCalendarWebhook->calendarId = $id;
                $chanelId = $gCalendarService->getWebhookChanel($calendar->gcalendarId);
                if ($chanelId) {
                    $newCalendarWebhook->chanelId = $chanelId;
                } else {
                    $newCalendarWebhook->method = 'api';
                }
                $newCalendarWebhook->save();


                $calendarApi = CalendarWebhookIds::where('calendarId', $id)->first();
            }

            $chanelId = $calendarApi->chanelId;
            $idData = explode('-', $chanelId);

            if (isset($idData[1])) {
                $expiration = $idData[1]/1 + 3600*24*6;
            } else {
                Log::channel('api_daily')->info("channel ERROR $id - ".$chanelId);
                $expiration = time() - 100;
            }


            if ($expiration <= time() && !empty($lastUpdate)) {
                $new_chenelId = $gCalendarService->getWebhookChanel($calendar->gcalendarId, $calendarApi->chanelId);
                $calendarApi->chanelId = $new_chenelId;
                $calendarApi->save();
                Log::channel('api_daily')->info("channel updateWebhookChanel $id [$lastUpdate] #".$calendarApi->chanelId);
            }


            return $calendarApi->lastUpdate;
        } else {

            return '';
        }

    }

    public function getCalendars(Request $request)
    {
        $gCalendarService = GcalendarService::setService();

        $calendars = Gcalendar::all()->toArray();

        foreach ($calendars as $item) {
            $calendarInfo = $gCalendarService->getCalendarInfo($item['gcalendarId']);
            $id = $item['id'];
            $type = $item['type_events'];
            $country = $item['country'];
            $source = $item['source'];
            $city = $item['city'];
            if (!$city) {
                $city = '';
            }
            $calendar_data['calendars'][$id] = [
                'gcalendarId' => $item['gcalendarId'],
                'name' => $calendarInfo['name'],
                'description' => $calendarInfo['description'],
                'type_events' => $type,
                'country' => $country,
                'city' => $city,
                'source' => $source,

            ];
            if ($item['city']) {
                $calendar_data[$type][$country][$city][$id] = $id;
            } else {
                $calendar_data[$type][$country][$id] = $id;
            }
        }
        ksort($calendar_data['festivals']);

        if ($request->get('test')) {
//            Artisan::call('migrate');
            dd($calendar_data);
        }
        $json = json_encode($calendar_data);

        print_r($json);
    }

    public function registerTokenUser(Request $request)
    {

        $data = $request->all();
        $date = new Carbon();

        try {

            if (!empty($data['userUid']) && !empty($data['userRole'])) {

                $token = sha1($date.$data['userUid'].$data['userRole']);

                $userToken = UserToken::firstOrCreate(
                    ['userUid' => $data['userUid']],
                    ['userRole' => $data['userRole'], 'token' => $token]
                );

                if ($userToken->userRole != $data['userRole']) {
                    $userToken->userRole = $data['userRole'];
                    $userToken->save();
                }

                $res = [
                    'tokenId' => $userToken->id,
                    'token' => $userToken->token,
                ];

                print_r(json_encode($res));
            }
        } catch (\Exception $e) {
            print_r($e->getMessage());
        }

    }

    public function deleteEventV1(Request $request)
    {
        $data = $request->all();
        $calendar = Gcalendar::find($data['calId']);
        $gCalendarService = GcalendarService::setService();

        unset($data['signed']);
        Log::channel('api_daily')->info("deleteEventV1 - " . json_encode($data));

        try {

            $calendarData = $gCalendarService->getCalendar($calendar->gcalendarId);
            $event = $gCalendarService->getEventById($calendar->gcalendarId, $data['eventId']);
            $eventId = str_replace('@google.com', '', $event->getICalUID());
            $dateStart = Carbon::parse($event->getStart()->getDate() ?? $event->getStart()->getDateTime());
            $event_date_start = $dateStart->format('Y-m-d');

            switch ($data['changeMode']){
                case 'one':
                    try {
                        $gCalendarService->deleteEventOne($calendar->gcalendarId, $data['eventId'], $data['dateStart']);
                        $res = [
                            'success' => true,
                            'data' => 'delete'
                        ];


                    } catch (\Exception $e) {
                        $errorsMessage = $e->getMessage();
                        Log::channel('api_daily')->info("ERROR deleteEventV1 - " . $errorsMessage);

                        $res = [
                            'success' => false,
                            'errorMessage' => json_decode($errorsMessage)
                        ];


                    }

                    break;
                case 'all':

                    try {
                        $gCalendarService->deleteEvent($calendar->gcalendarId, $eventId);
                        $res = [
                            'success' => true,
                            'data' => 'delete'
                        ];


                    } catch (\Exception $e) {
                        $errorsMessage = $e->getMessage();
                        $res = [
                            'success' => false,
                            'errorMessage' => json_decode($errorsMessage)
                        ];

                        print_r(json_encode($res));

                    }

                    break;
                case 'after':


                    try {


                        $recDate = Carbon::parse($event->getStart()->getDateTime());
                        $recDate = Carbon::parse($recDate->format('Y-m-d 23:59:59'));
                        $recDate->addDay(-1);

                        $recDate = Carbon::create(
                            $recDate->format("Y"),
                            $recDate->format("m"),
                            $recDate->format("d"),
                            $recDate->format("H"),
                            $recDate->format("i"),
                            0, $calendarData->getTimeZone()
                        );

                        $recurence = $event->getRecurrence();
                        $newRecurence = $recurence[0] . ";" . "UNTIL=" . $recDate->format('Ymd\THi59\Z');
                        $newData['event']['recurrence'] = [$newRecurence];

                        $gCalendarService->updateEventToCalendar($calendar->gcalendarId, $eventId, $newData['event']);
                        $res = [
                            'success' => true,
                            'data' => 'delete'
                        ];

                    } catch (\Exception $e) {
                        $errorsMessage = $e->getMessage();
                        $res = [
                            'success' => false,
                            'errorMessage' => json_decode($errorsMessage)
                        ];

                        break;
                    }
            }


            if (isset($res) && $res['success']) {
                $this->eventsSubscribeMessage(
                    'delete_event',
                    '📅❌ ' . $calendarData->summary,
                    $event->summary
                    . "\n 🌍 " . $calendar->country
                    . "\n 🌆 " . $calendar->city
                    . "\n 📍 " . $event->getLocation()
                    . "\n 🕙 " . $event_date_start,
                    $data
                );

            }

        } catch (\Exception $e) {
            $errorsMessage = $e->getMessage();
            $res = [
                'success' => false,
                'errorMessage' => json_decode($errorsMessage)
            ];

        }


        print_r(json_encode($res));

    }

    public function calendarWebhook(Request $request)
    {
        $data = $request->all();

        $chanelId = $request->header('X-Goog-Channel-ID');


        if (is_string($data)) {
            Log::channel('api_daily')->info("calendarWebhook (string) - " . $data);

        } else {

            Log::channel('api_daily')->info("calendarWebhook - $chanelId " . json_encode($data));
        }


        $calendarApi = CalendarWebhookIds::where('chanelId', $chanelId)->first();

        if ($calendarApi) {
            Log::channel('api_daily')->info("chanelId $calendarApi->id update calId -  $calendarApi->calendarId");
            $calendarApi->lastUpdate = time();
            $calendarApi->save();
        }

    }


    public function apiLog(Request $request)
    {

        $date = $request->get('date');
        if ($date) {
            $date_nau = new Carbon($date);
        } else {
            $date_nau = new Carbon();
        }

//        $gCalendarService = GcalendarService::setService();
//        $res = $gCalendarService->stopWebhookChanel('channel-1700050022');
//
//        dd($res);

        $date_str = $date_nau->format("Y-m-d");
        $date_pre = $date_nau->addDays(-1);

        if (Storage::disk('logs')->exists("api-daily-$date_str.log")) {
            $monolog = Storage::disk('logs')->get("api-daily-$date_str.log");
        } else {
            $monolog = 'not file';
        }
//        $monolog = htmlspecialchars($monolog);
        $monolog = str_replace('['.$date_nau->format('Y'), '<hr><b>['.$date_nau->format('Y'), $monolog);
        $monolog = str_replace('] ', ']</b> ', $monolog);

        $monolog = utf8_decode($monolog);

        return view('api.log', [
            'route' => 'api_log',
            'log' => $monolog,
            'date_str' => $date_str,
            'date_pre' => $date_pre
        ]);
    }

    public function deleteEventTest(Request $request)
    {

        $data = '{"tokenId":1,"calId":"75","eventId":"d013ftvcjn0tvtm3fic48979lc","dateStart":"2023-11-21","changeMode":"one"}';

        $data = json_decode($data, true);

        $calendar = Gcalendar::find($data['calId']);

        $gCalendarService = GcalendarService::setService();



            switch ($data['changeMode']){
                case 'one':

                    $gCalendarService->deleteEventOne($calendar->gcalendarId, $data['eventId'], $data['dateStart']);


                    break;
                case 'all':

                    $event = $gCalendarService->getEventById($calendar->gcalendarId, $data['eventId']);
                    $eventId = str_replace('@google.com', '', $event->getICalUID());

                    try {
                        $gCalendarService->deleteEvent($calendar->gcalendarId, $eventId);
                        $res = [
                            'success' => true,
                            'data' => 'delete'
                        ];

                        print_r(json_encode($res));

                    } catch (\Exception $e) {
                        $errorsMessage = $e->getMessage();
                        $res = [
                            'success' => false,
                            'errorMessage' => json_decode($errorsMessage)
                        ];

                        print_r(json_encode($res));

                    }

                    break;
                case 'after':


                    try {
                        $calendarData = $gCalendarService->getCalendar($calendar->gcalendarId);
                        $event = $gCalendarService->getEventById($calendar->gcalendarId, $data['eventId']);
                        $eventId = str_replace('@google.com', '', $event->getICalUID());


                        $recDate = Carbon::parse($event->getStart()->getDateTime());
                        $recDate = Carbon::parse($recDate->format('Y-m-d 23:59:59'));
                        $recDate->addDay(-1);

                        $recDate = Carbon::create(
                            $recDate->format("Y"),
                            $recDate->format("m"),
                            $recDate->format("d"),
                            $recDate->format("H"),
                            $recDate->format("i"),
                            0, $calendarData->getTimeZone()
                        );

                        $event = $gCalendarService->getEventById($calendar->gcalendarId, $eventId);

                        $recurence = $event->getRecurrence();
                        $newRecurence = $recurence[0] . ";" . "UNTIL=" . $recDate->format('Ymd\THi59\Z');
                        $newData['event']['recurrence'] = [$newRecurence];

                        $gCalendarService->updateEventToCalendar($calendar->gcalendarId, $eventId, $newData['event']);
                        $res = [
                            'success' => true,
                            'data' => 'delete'
                        ];

                        print_r(json_encode($res));
                    } catch (\Exception $e) {
                        $errorsMessage = $e->getMessage();
                        $res = [
                            'success' => false,
                            'errorMessage' => json_decode($errorsMessage)
                        ];

                        print_r(json_encode($res));
                        break;
                    }
            }


    }

    public function addEvent(Request $request)
    {

        $data = $request->all();

        if (!empty($data['calendars']) && !empty($data['event'])) {

            $gCalendarService = GcalendarService::setService();
            foreach ($data['calendars'] as $calId) {

                $calendar = Gcalendar::find($calId);
                $gcalendarId = $calendar->gcalendarId;

                if (!isset($data['event']['start']['timeZone'])) {
                    $calendarData = $gCalendarService->getCalendar($calendar->gcalendarId);
                    $data['event']['start']['timeZone'] = $calendarData->getTimeZone();
                    $data['event']['end']['timeZone'] = $calendarData->getTimeZone();

                    $data['event']['start']['dateTime'] = self::reFormetedDateTimeByTimzone($data['event']['start']['dateTime'], $calendarData->getTimeZone());
                    $data['event']['end']['dateTime'] = self::reFormetedDateTimeByTimzone($data['event']['end']['dateTime'], $calendarData->getTimeZone());

                }

                if (isset($data['calendarsImportData'])
                    && !empty($data['calendarsImportData'])
                    && isset($data['calendarsImportData'][$calId])
                    && isset($data['calendarsImportData'][$calId]['eventId'])) {

                    $eventId = $data['calendarsImportData'][$calId]['eventId'];


                    if (isset($data['calendarsImportData'][$calId]['importEventData'])) {
                        foreach ($data['calendarsImportData'][$calId]['importEventData'] as $field => $value) {
                            $data['event'][$field] = $value;
                        }
                    }

                    try {
                        $eventId = $gCalendarService->updateEventToCalendar($gcalendarId, $eventId, $data['event']);
                        $dataRes[] = [
                            'eventId' => $eventId,
                            'calId' => $calId,
                            'import' => true
                        ];


                    } catch (\Exception $e) {
                        $errorsMessage = $e->getMessage();
                    }

                } else {

                    try {
                        $eventId = $gCalendarService->addEventToCalendar($gcalendarId, $data['event']);
                        $dataRes[] = [
                            'eventId' => $eventId,
                            'calId' => $calId
                        ];


                    } catch (\Exception $e) {
                        $errorsMessage = $e->getMessage();
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
                    'errorMessage' => json_decode($errorsMessage)
                ];
            }

            print_r(json_encode($res));

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

    public function addEventV1(Request $request)
    {

        $data = $request->all();
        unset($data['signed']);

        if (!empty($data['calendars']) && !empty($data['event'])) {

            Log::channel('api_daily')->info("addEventV1 - " . json_encode($data));

            $gCalendarService = GcalendarService::setService();


            $date_start = Carbon::parse($data['event']['start']['dateTime']);
            $event_date_start = $date_start->format('Y-m-d H:i:s');
            $date_end = Carbon::parse($data['event']['start']['dateTime']);
            $event_date_end = $date_start->format('Y-m-d H:i:s');

            foreach ($data['calendars'] as $calId) {


                if ($calId == 120)
                    $calId = 7;

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
                    && isset($data['calendarsImportData'][$calId]['eventId'])) {

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
                    'errorMessage' => json_decode($errorsMessage)
                ];
            }

            print_r(json_encode($res));

        }


    }

    public function updateEvent(Request $request)
    {

        $data = $request->all();

        if (!empty($data['calendarId']) && !empty($data['eventId'])) {

            $calendar = Gcalendar::find($data['calendarId']);

            $gCalendarService = GcalendarService::setService();
            if (isset($data['event']['start']['timeZone'])) {
                $calendarData = $gCalendarService->getCalendar($calendar->gcalendarId);
                $data['event']['start']['timeZone'] = $calendarData->getTimeZone();
                $data['event']['end']['timeZone'] = $calendarData->getTimeZone();

                $data['event']['start']['dateTime'] = self::reFormetedDateTimeByTimzone($data['event']['start']['dateTime'], $calendarData->getTimeZone());
                $data['event']['end']['dateTime'] = self::reFormetedDateTimeByTimzone($data['event']['end']['dateTime'], $calendarData->getTimeZone());

            }

            try {

                switch ($data['changeMode']){
                    case 'one':
                        $eventId = $gCalendarService->updateEventToCalendar($calendar->gcalendarId, $data['eventId'], $data['event']);
                        break;
                    case 'all':
                        $event = $gCalendarService->getEventById($calendar->gcalendarId, $data['eventId']);
                        $eventId = str_replace('@google.com', '', $event->getICalUID());

                        $event = $gCalendarService->getEventById($calendar->gcalendarId, $eventId);


                        if ($event->getStart()->getDateTime() != $data['event']['start']['dateTime']) {
                            $originDate = Carbon::parse($event->getStart()->getDateTime());
                            $newDateStart = Carbon::parse( $data['event']['start']['dateTime']);
                            $fixDateTime = Carbon::parse($originDate->format("Y-m-d").' '.$newDateStart->format("H:i"));
                            $data['event']['start']['dateTime'] = self::reFormetedDateTimeByTimzone($fixDateTime->format("Y-m-d H:i"), $calendarData->getTimeZone());
                        }

                        if ($event->getEnd()->getDateTime() != $data['event']['end']['dateTime']) {
                            $originDate = Carbon::parse($event->getEnd()->getDateTime());
                            $newDateStart = Carbon::parse( $data['event']['end']['dateTime']);
                            $fixDateTime = Carbon::parse($originDate->format("Y-m-d").' '.$newDateStart->format("H:i"));
                            $data['event']['end']['dateTime'] = self::reFormetedDateTimeByTimzone($fixDateTime->format("Y-m-d H:i"), $calendarData->getTimeZone());
                        }

                        $eventId = $gCalendarService->updateEventToCalendar($calendar->gcalendarId, $eventId, $data['event']);

                        break;
                    case 'after':
                        $event = $gCalendarService->getEventById($calendar->gcalendarId, $data['eventId']);
                        $eventId = str_replace('@google.com', '', $event->getICalUID());
                        $event = $gCalendarService->getEventById($calendar->gcalendarId, $eventId);


                        $recDate = Carbon::parse($data['event']['start']['dateTime']);
                        $recDate = Carbon::parse($recDate->format('Y-m-d 23:59:59'));
                        $recDate->addDay(-1);

                        $recDate = Carbon::create(
                            $recDate->format("Y"),
                            $recDate->format("m"),
                            $recDate->format("d"),
                            $recDate->format("H"),
                            $recDate->format("i"),
                            0, $calendarData->getTimeZone()
                        );

                        $recurence = $event->getRecurrence();
                        $newRecurence = $recurence[0].";"."UNTIL=".$recDate->format('Ymd\THi59\Z');
                        $newData['event']['recurrence'] = [$newRecurence];

                        $gCalendarService->updateEventToCalendar($calendar->gcalendarId, $eventId, $newData['event']);

                        $data['event']['recurrence'] = $recurence;

                        $eventId = $gCalendarService->addEventToCalendar($calendar->gcalendarId, $data['event']);
                        break;
                }
            } catch (\Exception $e) {
                $errorsMessage = $e->getMessage();
            }

            if (isset($eventId)) {
                $res = [
                    'success' => true,
                    'data' => $eventId
                ];

            } else {
                $res = [
                    'success' => false,
                    'errorMessage' => json_decode($errorsMessage)
                ];
            }

            print_r(json_encode($res));

        }


    }

    public function updateEventV1(Request $request)
    {

        $data = $request->all();

        unset($data['signed']);

        if (!empty($data['calendarId']) && !empty($data['eventId'])) {

            Log::channel('api_daily')->info("updateEventV1 - " . json_encode($data));

            $calendar = Gcalendar::find($data['calendarId']);

            $gCalendarService = GcalendarService::setService();
            $calendarData = $gCalendarService->getCalendar($calendar->gcalendarId);


            $date_start = Carbon::parse($data['event']['start']['dateTime']);
            $event_date_start = $date_start->format('Y-m-d H:i:s');
            $date_end = Carbon::parse($data['event']['start']['dateTime']);
            $event_date_end = $date_start->format('Y-m-d H:i:s');

            if (!isset($data['event']['start']['timeZone'])) {
                $data['event']['start']['timeZone'] = "UTC";
                $data['event']['end']['timeZone'] = "UTC";

                $data['event']['start']['dateTime'] = self::reFormetedDateTimeByTimzone($data['event']['start']['dateTime'], $calendarData->getTimeZone());
                $data['event']['end']['dateTime'] = self::reFormetedDateTimeByTimzone($data['event']['end']['dateTime'], $calendarData->getTimeZone());

            }

            try {

                switch ($data['changeMode']){
                    case 'one':
                        $eventId = $gCalendarService->updateEventToCalendarOne($calendar->gcalendarId, $data['eventId'], $data['event'], $data['dateStart']);
                        break;
                    case 'all':
                        $event = $gCalendarService->getEventById($calendar->gcalendarId, $data['eventId']);
                        $eventUid = str_replace('@google.com', '', $event->getICalUID());

                        $event = $gCalendarService->getEventById($calendar->gcalendarId, $eventUid);


                        if (isset($data['event']['start']) && $event->getStart()->getDateTime() != $data['event']['start']['dateTime']) {
                            $originDate = Carbon::parse($event->getStart()->getDateTime());
                            $newDateStart = Carbon::parse( $data['event']['start']['dateTime']);
                            $fixDateTime = Carbon::parse($originDate->format("Y-m-d").' '.$newDateStart->format("H:i"));
                            $data['event']['start']['dateTime'] = self::reFormetedDateTimeByTimzone($fixDateTime->format("Y-m-d H:i"), $calendarData->getTimeZone());
                        }

                        if (isset($data['event']['end']) && $event->getEnd()->getDateTime() != $data['event']['end']['dateTime']) {
                            $originDate = Carbon::parse($event->getEnd()->getDateTime());
                            $newDateStart = Carbon::parse( $data['event']['end']['dateTime']);
                            $fixDateTime = Carbon::parse($originDate->format("Y-m-d").' '.$newDateStart->format("H:i"));
                            $data['event']['end']['dateTime'] = self::reFormetedDateTimeByTimzone($fixDateTime->format("Y-m-d H:i"), $calendarData->getTimeZone());
                        }

                        $eventId = $gCalendarService->updateEventToCalendar($calendar->gcalendarId, $eventUid, $data['event']);

                        break;
                    case 'after':
                        $event = $gCalendarService->getEventById($calendar->gcalendarId, $data['eventId']);
                        $eventUid = str_replace('@google.com', '', $event->getICalUID());
                        $event = $gCalendarService->getEventById($calendar->gcalendarId, $eventUid);


                        $recDate = Carbon::parse($data['event']['start']['dateTime']);
                        $recDate = Carbon::parse($recDate->format('Y-m-d 23:59:59'));
                        $recDate->addDay(-1);

                        $recDate = Carbon::create(
                            $recDate->format("Y"),
                            $recDate->format("m"),
                            $recDate->format("d"),
                            $recDate->format("H"),
                            $recDate->format("i"),
                            0, $calendarData->getTimeZone()
                        );

                        $recurence = $event->getRecurrence();
                        $rrule = $recurence[0];
                        $newUntilDate = $recDate->format('Ymd\THi59\Z');
                        if (preg_match('/UNTIL=/', $rrule)) {

                            $pattern = '/(UNTIL=)[^;]+/';
                            $replacement = 'UNTIL=' . $newUntilDate;
                            $newRecurence = preg_replace($pattern, $replacement, $rrule);
                        } else {
                            $newRecurence = "$rrule;UNTIL=$newUntilDate";
                        }

                        $newData['event']['recurrence'] = [$newRecurence];

                        $gCalendarService->updateEventToCalendar($calendar->gcalendarId, $eventUid, $newData['event']);

                        $eventId = $gCalendarService->addEventToCalendar($calendar->gcalendarId, $data['event']);
                        break;

                }


                $calendarApi = CalendarWebhookIds::where('calendarId', $calendar->gcalendarId)->first();

                if ($calendarApi) {

                    Log::channel('api_daily')->info("updateEventV1 update - calendarApi");
                    $calendarApi->lastUpdate = time();
                    $calendarApi->save();
                }

            } catch (\Exception $e) {
                $errorsMessage = $e->getMessage();
                Log::channel('api_daily')->info("updateEventV1 ERROR - " . $errorsMessage);

            }

            if (!isset($errorsMessage) && isset($eventId)) {
                $res = [
                    'success' => true,
                    'data' => $eventId
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
                        'calId' => $data['calendarId'],
                    ]
                );


            } else {
                $res = [
                    'success' => false,
                    'errorMessage' =>$errorsMessage
                ];
            }

            print_r(json_encode($res));

        }


    }

    public function firebaseTest(Request $request)
    {
        $res = $this->eventsSubscribeMessage(
            'update_event',
            '📅🔄 test message 3',
            'test event'
            . "\n 🌍 test country"
            . "\n 🌆 test city"
            . "\n 📍 location"
            . "\n 🕙 from "
            . ' to ',
            [
                'event_type' => 'event_update'
            ]
        );

        dd($res);
    }
    public function addEventTest(Request $request)
    {

        $data = '{"tokenId":1,"calendarId":"9","eventId":"7gmiml2691ed6qtrru15pnsrp3_R20231121T120000","changeMode":"after","dateStart":"2023-11-28","event":{"name":"test 3.2","location":null,"description":null,"start":{"dateTime":"2023-11-28T14:00:00-00:00"},"end":{"dateTime":"2023-11-28T15:00:00-00:00"},"organizer":{"displayName":"Alex","email":"virikidorhom@gmail.com"},"recurrence":["RRULE:FREQ=WEEKLY;BYDAY=TU"]}}';




        $data = json_decode($data, true);


        if (!empty($data['calendarId']) && !empty($data['eventId'])) {

            $calendar = Gcalendar::find($data['calendarId']);

            $gCalendarService = GcalendarService::setService();
            if (!isset($data['event']['start']['timeZone'])) {
                $calendarData = $gCalendarService->getCalendar($calendar->gcalendarId);
                $data['event']['start']['timeZone'] = "UTC";
                $data['event']['end']['timeZone'] = "UTC";

                $data['event']['start']['dateTime'] = self::reFormetedDateTimeByTimzone($data['event']['start']['dateTime'], $calendarData->getTimeZone());
                $data['event']['end']['dateTime'] = self::reFormetedDateTimeByTimzone($data['event']['end']['dateTime'], $calendarData->getTimeZone());

            }



                switch ($data['changeMode']){
                    case 'one':
                        $eventId = $gCalendarService->updateEventToCalendarOne($calendar->gcalendarId, $data['eventId'], $data['event'], $data['dateStart']);
                        break;
                    case 'all':
                        $event = $gCalendarService->getEventById($calendar->gcalendarId, $data['eventId']);
                        $eventId = str_replace('@google.com', '', $event->getICalUID());

                        $event = $gCalendarService->getEventById($calendar->gcalendarId, $eventId);


                        if (isset($data['event']['start']) && $event->getStart()->getDateTime() != $data['event']['start']['dateTime']) {
                            $originDate = Carbon::parse($event->getStart()->getDateTime());
                            $newDateStart = Carbon::parse( $data['event']['start']['dateTime']);
                            $fixDateTime = Carbon::parse($originDate->format("Y-m-d").' '.$newDateStart->format("H:i"));
                            $data['event']['start']['dateTime'] = self::reFormetedDateTimeByTimzone($fixDateTime->format("Y-m-d H:i"), $calendarData->getTimeZone());
                        }

                        if (isset($data['event']['end']) && $event->getEnd()->getDateTime() != $data['event']['end']['dateTime']) {
                            $originDate = Carbon::parse($event->getEnd()->getDateTime());
                            $newDateStart = Carbon::parse( $data['event']['end']['dateTime']);
                            $fixDateTime = Carbon::parse($originDate->format("Y-m-d").' '.$newDateStart->format("H:i"));
                            $data['event']['end']['dateTime'] = self::reFormetedDateTimeByTimzone($fixDateTime->format("Y-m-d H:i"), $calendarData->getTimeZone());
                        }

                        $eventId = $gCalendarService->updateEventToCalendar($calendar->gcalendarId, $eventId, $data['event']);

                        break;
                    case 'after':
                        $event = $gCalendarService->getEventById($calendar->gcalendarId, $data['eventId']);
                        $eventId = str_replace('@google.com', '', $event->getICalUID());
                        $event = $gCalendarService->getEventById($calendar->gcalendarId, $eventId);


                        $recDate = Carbon::parse($data['event']['start']['dateTime']);
                        $recDate = Carbon::create(
                            $recDate->format("Y"),
                            $recDate->format("m"),
                            $recDate->format("d"),
                            $recDate->format("H"),
                            $recDate->format("i"),
                            0, $calendarData->getTimeZone()
                        );
                        $newUntilDateUid = $recDate->format('Ymd\THi00');

                        $recDate = Carbon::parse($recDate->format('Y-m-d 23:59:59'));



                        $recDate->addDay(-1);

                        $recDate = Carbon::create(
                            $recDate->format("Y"),
                            $recDate->format("m"),
                            $recDate->format("d"),
                            $recDate->format("H"),
                            $recDate->format("i"),
                            0, $calendarData->getTimeZone()
                        );

                        $recurence = $event->getRecurrence();
                        $rrule = $recurence[0];
                        $newUntilDate = $recDate->format('Ymd\THi59\Z');

                        $eventIdData = explode('_', $eventId);
                        $newId = $eventIdData[0]."_R$newUntilDateUid";
                        $newIcalUid = $eventIdData[0]."_R$newUntilDateUid@google.com'";
                        if (preg_match('/UNTIL=/', $rrule)) {

                            $pattern = '/(UNTIL=)[^;]+/';
                            $replacement = 'UNTIL=' . $newUntilDate;
                            $newRecurence = preg_replace($pattern, $replacement, $rrule);
                        } else {
                            $newRecurence = "$rrule;UNTIL=$newUntilDate";
                        }

                        $eventInstans = $gCalendarService->getEventToCalendarOneInstans($calendar->gcalendarId, $data['eventId'], $data['dateStart']);

                        $eventInstans->setId($newId);
                        $eventInstans->setICalUID($newIcalUid);
                        $res = $gCalendarService->testNewInsert($calendar->gcalendarId, $eventInstans);
                        dd($eventInstans->toSimpleObject(), $res);

                        $newData['event']['recurrence'] = [$newRecurence];

                        $gCalendarService->updateEventToCalendar($calendar->gcalendarId, $eventId, $newData['event']);
                        $eventid = $gCalendarService->updateEventToCalendar($calendar->gcalendarId, $eventInstans->getId(), $data['event']);
                        dd($eventid);

                        dd($recurence, $newRecurence, $data, $event->toSimpleObject());
//                        dd($newData['event']);

//                        $data['event']['recurrence'] = $recurence;

                        $eventId = $gCalendarService->addEventToCalendar($calendar->gcalendarId, $data['event']);
                        break;
                }


            if (isset($eventId)) {
                $res = [
                    'success' => true,
                    'data' => $eventId
                ];

            } else {
                $res = [
                    'success' => false,
                    'errorMessage' => 'error'
                ];
            }

            print_r(json_encode($res));

        }

    }

    public function addCalendar(Request $request)
    {
        $data = $request->all();


        $gCalendarService = GcalendarService::setService();



        if ($data['data']['addMode'] == 'newCalendar') {

            $calendarData = [
                'name' => $data['data']['name'],
                'description' => $data['data']['description']
            ];

            if (empty($calendarData['description'])) {
                $calendarData['description'] = '';
            }

            try {

                $test = false;

                if ($data['data']['type_events'] == 'festivals' || $data['data']['type_events'] == 'master_classes') {

                    $test = Gcalendar::where('type_events', $data['data']['type_events'])
                        ->where('country', $data['data']['country'])->first();
                }

                if ($data['data']['type_events'] == 'milongas' || $data['data']['type_events'] == 'practices') {
                    $test = Gcalendar::where('type_events', $data['data']['type_events'])
                        ->where('country', $data['data']['country'])
                        ->where('city', $data['data']['city'])->first();
                }

                if (!$test) {
                    $gCalendarId = $gCalendarService->addCalendar($calendarData);

                    $gCalendarService->setScopes($gCalendarId);

                    $gCalendar = new Gcalendar();
                    $gCalendar->gcalendarId = $gCalendarId;
                    $gCalendar->type_events = $data['data']['type_events'];
                    $gCalendar->country = $data['data']['country'];
                    $gCalendar->city = $data['data']['city'];
                    $gCalendar->source = $data['data']['source'];

                    $gCalendar->save();

                    $resData = $gCalendar->toArray();
                    $resData = array_merge($calendarData, $resData);
                    $res = json_encode($resData);


                    $this->eventsSubscribeMessage(
                        'calendar_add',
                        'New Calendar '
                        . $gCalendar->type_events . ' ',
                        $data['name'] . ' to Country: '
                        . $gCalendar->country . ' to City: '
                        . $gCalendar->city,
                    );
                    print_r($res);
                } else {
                    $res = [
                        'success' => false,
                        'errorMessage' => [
                            'error' => [
                                'message' => 'calendar isset'
                            ]
                        ]
                    ];

                    print_r(json_encode($res));
                }

            } catch (\Exception $e) {
                $errorsMessage = $e->getMessage();
                $res = [
                    'success' => false,
                    'errorMessage' => json_decode($errorsMessage)
                ];

                print_r(json_encode($res));

            }
        }

        if ($data['data']['addMode'] == 'issetCalendar') {
            try {

                $test = Gcalendar::where('gcalendarId', $data['data']['uid'])->first();

                if (!$test) {

                    $gCalendarIsset = $gCalendarService->getCalendarInfo($data['data']['uid']);


                    $gCalendar = new Gcalendar();
                    $gCalendar->gcalendarId = $data['data']['uid'];
                    $gCalendar->type_events = $data['data']['type_events'];
                    $gCalendar->country = $data['data']['country'];
                    $gCalendar->city = $data['data']['city'];
                    $gCalendar->source = $data['data']['source'];
                    $gCalendar->save();

                    $resData = $gCalendar->toArray();
                    $calendarData = [
                        'name' => $gCalendarIsset['name'],
                        'description' => $gCalendarIsset['description']
                    ];
                    if (empty($calendarData['description'])) {
                        $calendarData['description'] = '';
                    }

                    $resData = array_merge($calendarData, $resData);

                    $res = json_encode($resData);

                    print_r($res);

                } else {

                    $res = [
                        'success' => false
                    ];

                    $res['errorMessage']['error']['message'] = "calendar {$data['data']['uid']} isset";

                    print_r(json_encode($res));
                }



            } catch (\Exception $e) {
                $errorsMessage = $e->getMessage();
                $res = [
                    'success' => false,
                    'errorMessage' => json_decode($errorsMessage)
                ];

                print_r(json_encode($res));

            }
        }

    }

    public function getCalendarDataBuUid(Request $request, $uid)
    {

        $gCalendarService = GcalendarService::setService();
        $gCalendarIsset = $gCalendarService->getCalendarInfo($uid);
        $calendarData = [
            'name' => $gCalendarIsset['name'],
            'description' => $gCalendarIsset['description']
        ];
        if (empty($calendarData['description'])) {
            $calendarData['description'] = '';
        }

        print_r(json_encode($calendarData));

    }

    private static function reFormetedDateTimeByTimzone($dateTime, $timeZone)
    {

        $date = Carbon::parse($dateTime);

        $newDate = Carbon::create(
            $date->format("Y"),
            $date->format("m"),
            $date->format("d"),
            $date->format("H"),
            $date->format("i"),
            0, $timeZone
        );
        return $newDate->format("Y-m-d")
            ."T".$newDate->format("H:i:00").$newDate->getTimezone()->toOffsetName();
    }

    public function translate(Request $request)
    {
        $files = Storage::disk('local')->files('langs');

        foreach ($files as $path) {
            $lang = str_replace('langs/app_', '', $path);
            $lang = str_replace('.arb', '', $lang);

            $content = Storage::disk('local')->get($path);

            $langs[$lang] = json_decode($content, true);
            if ($lang == 'en') {
                $langEn = $langs[$lang];
            }
        }
        $ofset = sizeof($langs['az']);
        $translit_data = array_slice($langEn, $ofset);
        $tr_str = json_encode($translit_data);


        foreach ($langs as $lang => $value) {

            if ($request->get('comand') == 'new_arb') {
                $data = [];
                if ($lang != 'en') {
                    $lan_old = $lang;
                    if ($lang == 'ru') {
                        $lan_old = 'uk';
                    }

                    $new_content = Storage::disk('local')->get("langs/new_translit/new_$lan_old.json");
                    $new_data = json_decode($new_content, true);
                    $langs[$lang] = array_merge($langs[$lang], $new_data);

                    foreach ($langs[$lang] as $key => $val) {
                        $val = str_replace("\n", '\\n', $val);
                        $data[] = "  \"$key\": \"$val\"";
                    }
                    $data_str = implode(",\r\n", $data);
                    $data_str = "{\r\n$data_str\r\n}";
                    Storage::disk('local')->put("langs/new_arb/app_$lang.arb", $data_str);

                }
            }

            if ($request->get('comand') == 'translate'
//                && $lang != 'he'
//                && $lang != 'hy'
//                && $lang != 'ka'
//                && $lang != 'kk'
//                && $lang != 'kn'
            ) {

                if (!Storage::disk('local')->exists("langs/new_translit/new_$lang.json") && $lang != 'en') {


//                    dd($lang);
                    $request = [
                        'url' => 'https://api.openai.com/v1/chat/completions',
                        'method' => 'POST',
                        'headers' => [
                            'Authorization' => 'Bearer ' . $this->apiKeyAi,
                            'Content-Type' => 'application/json',
                            'Content-Type2' => 'text/plain'
                        ],
                        'data' => [
                            'model' => 'gpt-4',
                            'messages' => [
                                [
                                    'role' => 'system',
                                    'content' => "тебе будет дан json обьект со значениями на английском языке. Переведи значения на $lang.",
                                ],
                                [
                                    'role' => 'user',
                                    'content' => "$tr_str",
                                ],
                            ],
                            'temperature' => 0,
                            'max_tokens' => 4350,
                            'top_p' => 1,
                            'frequency_penalty' => 0,
                            'presence_penalty' => 0,
                        ],
                    ];

                    $ClientBase = new ClientBaseService($request);

                    $res = $ClientBase->request();
                    if ($res['http_code'] == 200) {
                        $answer = json_decode($res['response'], true);
                        $res_tr = $answer['choices'][0]['message']['content'];

                        Storage::disk('local')->put("langs/new_translit/new_$lang.json", $res_tr);

//                        print_r("<p>$lang</p>");
                        return redirect('/translate?comand=translate');
                        dd($lang, $res_tr);
                    }
                }
            }

        }


        dd('end ' . sizeof($langs));
    }

    public function countries(Request $request)
    {
//        $new_data = Storage::disk('local')->get("geo/countries/ua.json");
//
//        $data = json_decode($new_data, true);


        $new_data = '';


        $pattern = '/<tr>\s*<td>(.*?)<\/td>/s';  // Регулярное выражение для поиска значений внутри тегов <td>

        preg_match_all($pattern, $new_data, $matches);

        $firstColumnValues = $matches[1]; // Массив значений из первой колонки


        foreach ($firstColumnValues as $item) {
            $city = strip_tags($item);
            $city = preg_replace('/\[.*\]/', '', $city);
            $data[] = '"'.$city.'"';
        }
        $str = implode(',', $data);
        dd($str);



    }

    public function translatePm(Request $request)
    {
        $data = '{"en":"en-US","uk":"uk","sq":"sq","hr":"hr","az":"az-AZ","bg":"bg","hu":"hu-HU","da":"da-DK","es":"es-ES","it":"it-IT","lv":"lv","lt":"lt","nl":"nl-NL","de":"de-DE","pl":"pl-PL","pt":"pt-BR","ro":"ro","sk":"sk","sl":"sl","sr":"sr","fi":"fi-FI","fr":"fr-FR","cs":"cs-CZ","sv":"sv-SE","zh":"zh-CN"}';

        $langs = json_decode($data, true);

        $tr_str = 'Доступ до керування подіями, командна робота.
Адмін доступ до подій для організаторів.

Організатор або волонтер може подати заявку на доступ до управління подією у загальних календарях, таких як мілонги чи практики.
Схвалити таку заявку може адміністратор або користувач з адмін доступом до події.

Зареєстрований користувач може подати заявку на приєднання до команди організатора.
Після схвалення такої заявки користувач матиме права доступу до подій, прописані організатором.

Перегляд статусу заявок у власному кабінеті.
Інформація про команду та керування доступами членів команди в особистому кабінеті.';

        if (!Storage::disk('local')->exists("langs/translit_Pm/new_uk.txt")) {

            Storage::disk('local')->put("langs/translit_Pm/new_uk.txt", $tr_str);
        }

        $tr_str = 'Access to event management, team work.
Administrator access to events for organizers.

An organizer or volunteer can apply for access to event management in shared calendars such as milongas or practices.
Such a request can be approved by an administrator or a user with admin access to the event.

A registered user can apply to join the organizer\'s team.
After approval of such an application, the user will have access rights to events prescribed by the organizer.

View the status of applications in your personal account.
Information about the team and managing access of team members in your personal account.';
        if (!Storage::disk('local')->exists("langs/translit_Pm/new_en.txt")) {

            Storage::disk('local')->put("langs/translit_Pm/new_en.txt", $tr_str);
        }



        foreach ($langs as $lang => $val) {

            if ($request->get('comand') == 'new_arb') {

                print_r("<pre>");
                $new_data = Storage::disk('local')->get("langs/translit_Pm/new_$lang.txt");
                $data_str = "<$val>
$new_data
</$val>";
                $data_str = htmlspecialchars($data_str);
                print_r("<p>$data_str</p>");
            }

            if ($lang != 'en'
//                && $lang != 'az'
                && $lang != 'uk')
            if ($request->get('comand') == 'translate') {

                if (!Storage::disk('local')->exists("langs/translit_Pm/new_$lang.txt") && ($lang != 'en' || $lang != 'uk')) {

//                    dd($lang);
                    $request = [
                        'url' => 'https://api.openai.com/v1/chat/completions',
                        'method' => 'POST',
                        'headers' => [
                            'Authorization' => 'Bearer ' . $this->apiKeyAi,
                            'Content-Type' => 'application/json',
                            'Content-Type2' => 'text/plain'
                        ],
                        'data' => [
                            'model' => 'gpt-3.5-turbo-16k',
                            'messages' => [
                                [
                                    'role' => 'system',
                                    'content' => "тебе будет дан текст на английском языке. Переведи на $lang.",
                                ],
                                [
                                    'role' => 'user',
                                    'content' => "$tr_str",
                                ],
                            ],
                            'temperature' => 0,
                            'max_tokens' => 4350,
                            'top_p' => 1,
                            'frequency_penalty' => 0,
                            'presence_penalty' => 0,
                        ],
                    ];

                    $ClientBase = new ClientBaseService($request);

                    $res = $ClientBase->request();
                    if ($res['http_code'] == 200) {
                        $answer = json_decode($res['response'], true);
                        $res_tr = $answer['choices'][0]['message']['content'];

                        Storage::disk('local')->put("langs/translit_Pm/new_$lang.txt", $res_tr);

                        print_r("<p>$lang </p>");
                        return redirect('/translate_pm?comand=translate');
                        dd($lang, $res_tr);
                    }
                } else {

//                    dd($lang);
                }
            }

        }

        dd('end');
    }

    public static function translateTextEvent(Request $request)
    {
        $data = $request->all();

        unset($data['signed']);

        try {
            $res_tr = 'not translated';
            $lang_to = $data['lang_to'];
            $tr_key = $data['tr_key'];
            $tr_str = $data['text'];
            $instruction = "Определи на скольких языках текст. если их больше 2х то переведи с того который идет первый. Переведи на $lang_to. Покажи только перевод.";

            $checksum = md5($tr_str);

            $translateData = EventTranslate::where('tr_key', $tr_key)->first();

            if (!$translateData || $translateData->checksum != $checksum) {

                Log::channel('api_daily')->info("translateTextEvent P -" . json_encode($data));

                $request = [
                    'url' => 'https://api.openai.com/v1/chat/completions',
                    'method' => 'POST',
                    'headers' => [
                        'Authorization' => 'Bearer ' . self::getApiKeyAi(),
                        'Content-Type' => 'application/json',
                        'Content-Type2' => 'text/plain'
                    ],
                    'data' => [
                        'model' => 'gpt-3.5-turbo-16k',
                        'messages' => [
                            [
                                'role' => 'system',
                                'content' => $instruction,
                            ],
                            [
                                'role' => 'user',
                                'content' => $tr_str,
                            ],
                        ],
                        'temperature' => 0,
                        'max_tokens' => 4350,
                        'top_p' => 1,
                        'frequency_penalty' => 0,
                        'presence_penalty' => 0,
                    ],
                ];

                $ClientBase = new ClientBaseService($request);
                $res = $ClientBase->request();
                if ($res['http_code'] == 200) {

                    $answer = json_decode($res['response'], true);
                    $res_tr = $answer['choices'][0]['message']['content'];

                    Log::channel('api_daily')->info("translateTextEvent S -" . json_encode($answer));
                    if (!$translateData ) {
                        $translateData = new EventTranslate();
                        $translateData->tr_key = $tr_key;
                        $translateData->checksum = $checksum;
                        $translateData->tr_text = $res_tr;
                    } else {
                        $translateData->checksum = $checksum;
                        $translateData->tr_text = $res_tr;
                    }
                    $translateData->save();
                } else {

                    Log::channel('api_daily')->info("translateTextEvent E -" . json_encode($res));
                    if ($request->get('test'))
                        dd($res);
                }

            } else {
                $res_tr = $translateData->tr_text;
            }
        } catch (\Exception $e) {
            $res_tr = $e->getMessage();

            Log::channel('api_daily')->info("translateTextEvent E -" . $res_tr);
        }



        return $res_tr;
    }

    public static function translateTextEventTest(Request $request)
    {
        $data = '{"tokenId":1,"lang_to":"uk","tr_key":"e1366700157262923@facebook.com-uk","text":"Enrique and Martina love both dance forms - Contact Improvisation and Tango. Since years they research and teach them.\nDriven by this passion the create their own specific way of combing and teaching this and finding ways of transition smoothly from on to the other,\nletting them feed each other and getting new experiences out of this.\nThey play with musicality, physical exploration to open freedom ins playfullness in dance.\n\nTimes: saturday\/sunday 12-17h\nsaturday 19-23 ContacTango Jam\n\nEarly Bird until 13.10.2023 110,- \u20ac after 125,- including Jamilonga 125,-\u20ac\/140,-\u20ac\n\nSleeping in studio possible\n \n\nEnrique und Martina lieben, unterrichten und forschen in beiden Tanzformen Tango und Contact Improvisation. \n\u00dcber diese Leidenschaft finden sie Ihre ganz eigenen Wege und Ideen, dies zu verbinden und vermitteln.\nDabei fliessen sie mal mehr vom der einen Seite zur anderen und dann wieder zur\u00fcck, spielen mit Musikalit\u00e4t, physischen Forschungsreisen und Varationen.\n\nZeiten: Samstag\/Sonntag je 12-17h\nSamstag 18-ca20\/21h ContacTango Jam\nEarly Bird bis. 10.10.2023 \u20ac danach 145,-\u20ac inklusive Jamilonga 125,-\u20ac\/160,-\u20ac\n\n\u00dcbernachtung im Tanzraum m\u00f6glich\n \nRegistration\/Info: info@gabrielekoch.net; 0160-5258336\n\nhttps:\/\/www.facebook.com\/events\/1366700157262923\/"}';


        $data = json_decode($data, true);

        unset($data['signed']);

            $res_tr = 'not translated';
            $lang_to = $data['lang_to'];
            $tr_key = $data['tr_key'];
            $tr_str = $data['text'];
            $instruction = "Определи на скольких языках текст. если их больше 2х то переведи с того который идет первый. Переведи на $lang_to. Покажи только перевод.";

            $checksum = md5($tr_str);

            $translateData = EventTranslate::where('tr_key', $tr_key)->first();

            $rows = preg_split("/[.!?]\\s+|\\n/", $tr_str, -1, PREG_SPLIT_NO_EMPTY);


            if (!$translateData || $translateData->checksum != $checksum) {


                $request = [
                    'url' => 'https://api.openai.com/v1/chat/completions',
                    'method' => 'POST',
                    'headers' => [
                        'Authorization' => 'Bearer ' . self::getApiKeyAi(),
                        'Content-Type' => 'application/json',
                        'Content-Type2' => 'text/plain'
                    ],
                    'data' => [
                        'model' => 'gpt-3.5-turbo-16k',
                        'messages' => [
                            [
                                'role' => 'system',
                                'content' => $instruction,
                            ],
                            [
                                'role' => 'user',
                                'content' => $tr_str,
                            ],
                        ],
                        'temperature' => 0,
                        'max_tokens' => 4350,
                        'top_p' => 1,
                        'frequency_penalty' => 0,
                        'presence_penalty' => 0,
                    ],
                ];

                $ClientBase = new ClientBaseService($request);
                $res = $ClientBase->request();

                if ($res['http_code'] == 200) {

                    $answer = json_decode($res['response'], true);
                    $res_tr = $answer['choices'][0]['message']['content'];

                    if (!$translateData ) {
                        $translateData = new EventTranslate();
                        $translateData->tr_key = $tr_key;
                        $translateData->checksum = $checksum;
                        $translateData->tr_text = $res_tr;
                    } else {
                        $translateData->checksum = $checksum;
                        $translateData->tr_text = $res_tr;
                    }
                    $translateData->save();
                } else {
                    dd($res);
                }

            } else {
                $res_tr = $translateData->tr_text;
            }


        dd('stop test', $res_tr);


        return $res_tr;
    }


    public static function signedCheck($data)
    {

        $date = new Carbon();

        if (isset($data['tokenId'])) {
            $tokenUser = UserToken::find($data['tokenId']);
            if ($tokenUser) {
                $signed = $data['signed'];

                $testSigned = md5($tokenUser->token.'-'.$date->format('Y-m-d-H'));

                if ($testSigned == $signed) {
                    return true;
                }
            }
            return true;
        }

        return false;
    }


    public function apiTestPostback(Request $request)
    {
        $data = $request->all();
        $method = $request->getMethod();
        Log::channel('api_postback')->info("apiTestPostback $method - <br>" . $request->getQueryString());

//        if (is_string($data)) {
//
//            Log::channel('api_postback')->info("apiTestPostback (string) $method - " . $data);
//        } else {
//
//            Log::channel('api_postback')->info("apiTestPostback $method - " . json_encode($data));
//        }
    }


    public function almabetPostback(Request $request)
    {
        $data = $request->all();
        $method = $request->getMethod();
        Log::channel('almabet_postback')->info("postback $method - <br>" . json_encode($data));

        $url = 'http://135.181.84.188:8000/api/almabet_postback';
        Http::get($url, $data);

    }



    public function apiLogPostbeck(Request $request)
    {

        $date = $request->get('date');
        if ($date) {
            $date_nau = new Carbon($date);
        } else {
            $date_nau = new Carbon();
        }

//        $url = 'https://graph.facebook.com/v18.0/786881193157660/events?data=%5B%0A%20%20%20%20%20%20%20%20%7B%0A%20%20%20%20%20%20%20%20%20%20%20%20%22event_name%22%3A%20%22CompleteRegistration%22%2C%0A%20%20%20%20%20%20%20%20%20%20%20%20%22event_id%22%3A%20%22123456%22%2C%0A%20%20%20%20%20%20%20%20%20%20%20%20%22event_time%22%3A%201701857355%2C%0A%20%20%20%20%20%20%20%20%20%20%20%20%22event_source_url%22%3A%20%22https%3A%2F%2Ftango-calendar.it-alex.net.ua%22%2C%0A%20%20%20%20%20%20%20%20%20%20%20%20%22action_source%22%3A%20%22website%22%2C%0A%20%20%20%20%20%20%20%20%20%20%20%20%22user_data%22%3A%20%7B%0A%20%20%20%20%20%20%20%20%20%20%20%20%20%20%20%20%22external_id%22%3A%20%5B%0A%20%20%20%20%20%20%20%20%20%20%20%20%20%20%20%20%20%20%20%20%22123%22%0A%20%20%20%20%20%20%20%20%20%20%20%20%20%20%20%20%5D%0A%20%20%20%20%20%20%20%20%20%20%20%20%7D%0A%20%20%20%20%20%20%20%20%7D%0A%20%20%20%20%5D&access_token=EAAKKdMvZCdTsBO4NvLDatWSSA0zjJj8FeB49XNl8jtYO7ZCA4AjizpN89SbID3hk1nS9jAhw2suzQ6EV3mvs7C73t8pMut46dr4sLyW0PZBc32FlfARIJPNS0r5fPM39ggkyTFy0BTfK7Q3nQAYWf2oD6JPWuoZAfWJTwbCPZAsEiXupFR3jIZAkFkPmjS4fWjQKwwqLIR5SP8MOGTafEZAryjrAJ9MgnKfRQ2lqAZDZD"';
//
//
//        $url = urldecode($url);
//
//        dd($url);

        $date_str = $date_nau->format("Y-m-d");
        $date_pre = $date_nau->addDays(-1);

        if (Storage::disk('logs')->exists("api_postback-$date_str.log")) {
            $monolog = Storage::disk('logs')->get("api_postback-$date_str.log");
        } else {
            $monolog = 'not file';
        }
//        $monolog = htmlspecialchars($monolog);
        $monolog = str_replace('['.$date_nau->format('Y'), '<hr><b>['.$date_nau->format('Y'), $monolog);
        $monolog = str_replace('] ', ']</b> ', $monolog);

        $monolog = utf8_decode($monolog);

        return view('api.log', [
            'route' => 'api_postback',
            'log' => $monolog,
            'date_str' => $date_str,
            'date_pre' => $date_pre
        ]);
    }

}
