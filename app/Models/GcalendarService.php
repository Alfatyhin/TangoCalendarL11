<?php

namespace App\Models;

use Carbon\Carbon;
use Google_Client;
use Google_Service_Calendar;
use Google_Service_Calendar_Channel;
use Google_Service_Calendar_Event;
use Illuminate\Support\Facades\Log;

class GcalendarService
{

    private static $_service;
    /** @var $service \Google_Service_Calendar */
    public $service;
    public $client;

    private function __construct()
    {
        self::getService();
    }

    static function setService()
    {
        if(!self::$_service) {
            self::$_service = new self();
        }
        return self::$_service;
    }

    public function getCalendarInfo($gcalendarId)
    {
        // получаем метаданные календаря
        $calendar = $this->service->calendars->get($gcalendarId);
        $calendarName = $calendar->getSummary();
        $calendarDescription = $calendar->getDescription();

        return ['name' => $calendarName, 'description' => $calendarDescription];
    }

    public function getCalendar($gcalendarId)
    {
        // получаем метаданные календаря
        $calendar = $this->service->calendars->get($gcalendarId);
        return $calendar;
    }

    public function getCalendarEvents($gcalendarId, $timeMin, $timeMax, $count = false)
    {
        $data = [
            'timeMin'      => $timeMin,
            'timeMax'      => $timeMax,
            'orderBy'      => 'startTime',
            'singleEvents' => true,
        ];
        if ($count) {
            $data['maxResults'] = $count;
        }

        $events = $this->service->events->listEvents($gcalendarId, $data);
        return $events;
    }

    public function getCalendarEventsOnce($gcalendarId, $timeMin, $timeMax, $count = false)
    {
        $data = [
            'timeMin'      => $timeMin,
            'timeMax'      => $timeMax,
            'singleEvents' => false,
        ];
        if ($count) {
            $data['maxResults'] = $count;
        }

        $events = $this->service->events->listEvents($gcalendarId, $data);
        return $events;
    }


    public function addEventToCalendar($gcalendarId, $eventData)
    {
        // https://developers.google.com/calendar/api/v3/reference/events/insert?hl=ru

        if (isset($eventData['name'])
            && isset($eventData['location'])
            && isset($eventData['description'])
            && (isset($eventData['start']['date']) || isset($eventData['start']['dateTime']) )
            && (isset($eventData['end']['date']) || isset($eventData['end']['dateTime']) )) {

        }

        $data = [
            'summary' => $eventData['name'],
            'location' => $eventData['location'],
            'description' => $eventData['description'],
            'start' => $eventData['start'],
            'end' => $eventData['end'],
            ];

        if (isset($eventData['source']['title'])) {
            $data['source']['title'] = $eventData['source']['title'];
        }

        if (isset($eventData['source']['url'])) {
            $data['source']['url'] = $eventData['source']['url'];
        }

        if (isset($eventData['recurrence'])) {
            $data['recurrence'] = $eventData['recurrence'];
        }


        $event = new Google_Service_Calendar_Event($data);



        $event = $this->service->events->insert($gcalendarId, $event);
        return $event->getId();
    }

    public function testNewInsert($gcalendarId, Google_Service_Calendar_Event $event)
    {

        $event = $this->service->events->insert($gcalendarId, $event);
        return $event->getId();
    }


    public function importEventToCalendar($gcalendarId, $eventData)
    {
        // https://developers.google.com/calendar/api/v3/reference/events/import?hl=ru

        if (isset($eventData['name'])
            && isset($eventData['location'])
            && isset($eventData['description'])
            && (isset($eventData['start']['date']) || isset($eventData['start']['dateTime']) )
            && (isset($eventData['end']['date']) || isset($eventData['end']['dateTime']) )) {

        }

        $data = [
            'iCalUID' => $eventData['iCalUID'],
            'summary' => $eventData['name'],
            'location' => $eventData['location'],
            'description' => $eventData['description'],
            'start' => $eventData['start'],
            'end' => $eventData['end'],
            ];

        if (isset($eventData['source']['title'])) {
            $data['source']['title'] = $eventData['source']['title'];
        }

        if (isset($eventData['source']['url'])) {
            $data['source']['url'] = $eventData['source']['url'];
        }


        $event = new Google_Service_Calendar_Event($data);


        $event = $this->service->events->import($gcalendarId, $event);
        return $event->getId();
    }

    public function getCalendarEvent($gcalendarId, $eventId)
    {
        $res = $this->service->events->get($gcalendarId, $eventId);

        return $res;
    }

    public function updateEventToCalendarOne($gcalendarId, $eventId, $eventData, $event_date)
    {
        $event_instances = $this->service->events->instances($gcalendarId, $eventId, array('timeMin' => $event_date . 'T00:00:00Z', 'timeMax' => $event_date . 'T23:59:59Z'));

        $instance_id = null; // Инициализируем идентификатор экземпляра

        foreach ($event_instances as $instance) {
            $instance_id = $instance->getId();
        }
        if ($instance_id) {
            $res = $this->updateEventToCalendar($gcalendarId, $instance_id, $eventData);
        } else {
            $res = $this->updateEventToCalendar($gcalendarId, $eventId, $eventData);
        }

        Log::channel('api_daily')->info("updateEventV1 res - " . json_encode([$res]));


        return $res;
    }

    public function getEventToCalendarOneInstans($gcalendarId, $eventId, $event_date)
    {
        $event_instances = $this->service->events->instances($gcalendarId, $eventId, array('timeMin' => $event_date . 'T00:00:00Z', 'timeMax' => $event_date . 'T23:59:59Z'));

        return $event_instances[0];
    }

    public function updateEventToCalendar($gcalendarId, $eventId, $eventData)
    {
        // https://developers.google.com/calendar/api/v3/reference/events/update?hl=ru#examples

        /** @var $event \Google_Service_Calendar_Event */

        $event = $this->service->events->get($gcalendarId, $eventId);

        if (isset($eventData['name'])) {
            $event->setSummary($eventData['name']);
        }
        if (isset($eventData['location'])) {
            $event->setLocation($eventData['location']);
        }
        if (isset($eventData['organizer'])) {
            $organizer = new \Google_Service_Calendar_EventOrganizer();
            $organizer->setDisplayName($eventData['organizer']['displayName']);
            $organizer->setEmail($eventData['organizer']['email']);
            $event->setOrganizer($organizer);
        }
        if (isset($eventData['description'])) {
            $event->setDescription($eventData['description']);
        }

        if (isset($eventData['start'])) {
            $dateEvent = new \Google_Service_Calendar_EventDateTime();
            if (isset($eventData['start']['date'])) {
                $dateEvent->setDate($eventData['start']['date']);
            }
            if (isset($eventData['start']['dateTime'])) {
                $dateEvent->setDateTime($eventData['start']['dateTime']);
            }
            if (isset($eventData['start']['timeZone'])) {
                $dateEvent->setTimeZone($eventData['start']['timeZone']);
            }

            $event->setStart($dateEvent);
        }

        if (isset($eventData['end'])) {
            $dateEvent = new \Google_Service_Calendar_EventDateTime();
            if (isset($eventData['end']['date'])) {
                $dateEvent->setDate($eventData['end']['date']);
            }
            if (isset($eventData['end']['dateTime'])) {
                $dateEvent->setDateTime($eventData['end']['dateTime']);
            }
            if (isset($eventData['end']['timeZone'])) {
                $dateEvent->setTimeZone($eventData['end']['timeZone']);
            }

            $event->setEnd($dateEvent);
        }

        if (isset($eventData['source'])) {
            $source = new \Google_Service_Calendar_EventSource();
            $source->setTitle($eventData['source']['title']);
            $source->setUrl($eventData['source']['url']);
            $event->setSource($source);
        }




        if (isset($eventData[0])) {

            $property = new \Google_Service_Calendar_EventExtendedProperties();
            $property->setShared('source_test', '123');
            $event->setExtendedProperties($property);
            $source = $event->getSource();
            $source['modelData'] = [123];
            $event->setSource($source);
            $creator = $event->getOrganizer();
            $creator->setId('erfibhiglhw5h');
//            dd($creator);

//            dd($event->getExtendedProperties());
        }

        if (isset($eventData['recurrence'])) {
            $event->setRecurrence($eventData['recurrence']);
        }

        $eventNew = $this->service->events->update($gcalendarId, $event->getId(), $event);


        return $eventNew->getId();
    }


    public function addCalendar($data)
    {
        $calendar = new \Google_Service_Calendar_Calendar();
        $calendar->setSummary($data['name']);
        if (!empty($data['description'])) {
            $calendar->setDescription($data['description']);
        }

        $createdCalendar = $this->service->calendars->insert($calendar);

        return $createdCalendar->getId();
    }


    public function deleteCalendar($gcalendarId)
    {
        $this->service->calendars->delete($gcalendarId);
    }


    public function setScopes($gcalendarId)
    {

        $rule = new \Google_Service_Calendar_AclRule();
        $scope = new \Google_Service_Calendar_AclRuleScope();

        $scope->setType("user");
        $scope->setValue("tangocalendar.ua@gmail.com");
        $rule->setScope($scope);
        $rule->setRole("owner");


        $this->service->acl->insert($gcalendarId, $rule);

        $scope->setType("default");
        $rule->setScope($scope);
        $rule->setRole("reader");


        $createdRule = $this->service->acl->insert($gcalendarId, $rule);

        return $createdRule->getId();
    }


    public function getEventById($gcalendarId, $eventId)
    {

        $event = $this->service->events->get($gcalendarId, $eventId);

        return $event;
    }

    public function deleteEventOne($gcalendarId, $eventId, $date_to_delete)
    {
        $event_instances = $this->service->events->instances($gcalendarId, $eventId, array('timeMin' => $date_to_delete . 'T00:00:00Z', 'timeMax' => $date_to_delete . 'T23:59:59Z'));
        $instance_id = null; // Инициализируем идентификатор экземпляра

        foreach ($event_instances as $instance) {
            $instance_id = $instance->getId();
        }

        if (!$instance_id) {
            $instance_id = $eventId;
        }

        $res = $this->service->events->delete($gcalendarId, $instance_id);
        return $res;
    }

    public function deleteEvent($gcalendarId, $eventId)
    {
        $res = $this->service->events->delete($gcalendarId, $eventId);
        return $res;
    }

    public function getWebhookChanel($calendarId)
    {
        // Настройка параметров вебхука
        $url = url('api/calendar_webhook');
        $webhookUrl = $url; // Замените на вашу точку входа
        $channel = new Google_Service_Calendar_Channel();
        $chanelId = 'channel-'.time();
        $channel->setId($chanelId); // Уникальный идентификатор вебхука
        $channel->setType('web_hook');
        $channel->setAddress($webhookUrl);

//        $this->service->events->stop('primary', 'channel-id-to-stop'); // Если уже существует активная подписка, отключите ее

        try {
            $res = $this->service->events->watch($calendarId, $channel);
//            $data = $res->toSimpleObject();
//            Log::channel('api_daily')->info("channel data #$chanelId - " . json_encode($data));
            return $chanelId;
        } catch (\Exception $exception) {
           return false;
        }
    }

    public function updateWebhookChanel($calendarId, $chanelId)
    {
        // Настройка параметров вебхука
        $url = url('api/calendar_webhook');
        $webhookUrl = $url; // Замените на вашу точку входа
        $channel = new Google_Service_Calendar_Channel();
        $channel->setId($chanelId); // Уникальный идентификатор вебхука
        $channel->setType('web_hook');
        $channel->setAddress($webhookUrl);

//        $this->service->events->stop('primary', 'channel-id-to-stop'); // Если уже существует активная подписка, отключите ее

        try {
            $res = $this->service->events->watch($calendarId, $channel);
//            $data = $res->toSimpleObject();
//            Log::channel('api_daily')->info("channel data #$chanelId - " . json_encode($data));
            return $chanelId;
        } catch (\Exception $exception) {
           return false;
        }
    }

    public function stopWebhookChanel($subscriptionId )
    {
        $channel = new Google_Service_Calendar_Channel();
        $channel->setId($subscriptionId);
        $url = url('api/calendar_webhook');
        $webhookUrl = $url;
        $channel->setType('web_hook');
        $channel->setAddress($webhookUrl);
        $channel->setResourceId('FVFxYWsBkZ0vfQZ2nQSfPj1WK08');

        $res = $this->service->channels->stop($channel);

        return $res;
    }

    public function getCalendarEventsDaysData($gcalendarId, $timeMin, $timeMax, $count = false)
    {

        $events = $this->getCalendarEvents($gcalendarId, $timeMin, $timeMax, $count);

        $date_max = Carbon::parse($timeMax);
        $date_min = Carbon::parse($timeMin);

        $listEvents = [];

        foreach ($events->getItems() as $event)
        {
            /** @var $event \Google_Service_Calendar_Event */

            $dateStartObj = $event->getStart();
            if ($dateStartObj->dateTime) {
                $dateStart = $dateStartObj->getDateTime();
            } else {
                $dateStart = $dateStartObj->getDate();
            }
            $date_start  = Carbon::parse($dateStart);
            $dateStart   = $date_start->format('Y-m-d');
            $timeStart   = $date_start->format('H:i');


            $dateEndtObj = $event->getEnd();
            if ($dateEndtObj->dateTime) {
                $dateEnd = $dateEndtObj->getDateTime();
            } else {
                $dateEnd = $dateEndtObj->getDate();
            }
            $date_end    = Carbon::parse($dateEnd);
            if (!$dateEndtObj->dateTime) {
                $date_end->addMinute(-1);
                $time_use = 0;
            } else {
                $time_use = 1;
            }
            $dateEnd     = $date_end->format('Y-m-d');
            $timeEnd     = $date_end->format('H:i');

            $lastModifed = $event->getUpdated();
            $date        = Carbon::parse($lastModifed);
            $dateMod     = $date->format('Y-m-d H:i');


            $eventData = [
                'eventId' => $event->getId(),
                'name' => $event->getSummary(),
                'description' => $event->getDescription(),
                'location' => $event->getLocation(),
                'timeUse' => $time_use,
                'dateStart' => $dateStart,
                'timeStart' => $timeStart,
                'dateEnd' => $dateEnd,
                'timeEnd' => $timeEnd,
                'update' => $dateMod,
                'creatorEmail' => $event->getCreator()->getEmail(),
                'creatorName' => $event->getCreator()->getDisplayName(),
                'organizerEmail' => $event->getOrganizer()->getEmail(),
                'organizerName' => $event->getOrganizer()->getDisplayName()
            ];

            if ($event->getSource() && $event->getSource()->getUrl()) {
                $eventData['url'] = $event->getSource()->getUrl();
            } else {
                $eventData['url'] = '';
            }
            $eventData['ICalUID'] = $event->getICalUID();
            $eventData['recurringEventId'] = $event->getRecurringEventId();
            $eventData['recurrence'] = $event->getRecurrence();

            $date_stop = Carbon::parse($dateEnd);


            $date_start->setHour(0)->setMinute(0);

            do {
                $dateEvent = $date_start->format("Y-m-d");
                $dateTest = $date_start->format("Ymd") / 1;

                if ($dateTest >= $date_min->format("Ymd") / 1
                    && $dateTest <= $date_max->format("Ymd") / 1) {
                    $listEvents[$dateEvent][] = $eventData;
                }

                $date_start->addDay();

            } while ($date_start < $date_stop);


        }

        return $listEvents;

    }

    public function getCalendarEventsDaysDataOnce($gcalendarId, $timeMin, $timeMax, $count = false)
    {

        $events = $this->getCalendarEventsOnce($gcalendarId, $timeMin, $timeMax, $count);

        $listEvents = [];

        foreach ($events->getItems() as $event)
        {
            if (isset($event->recurrence) && !empty($event->recurrence)) {
                $rrule_string =  $event->recurrence[0];

                $pattern = '/UNTIL=(\d{8}T\d{6}Z)/';

                if (preg_match($pattern, $rrule_string, $matches)) {
                    $date_string = $matches[1];

                    $until_date = Carbon::parse($date_string);

                    if ($timeMin <= $until_date) {
                        $listEvents[$event->getId()] = $event->toSimpleObject();
                    }
                } else {
                    $listEvents[$event->getId()] = $event->toSimpleObject();
                }
            } else {
                $listEvents[$event->getId()] = $event->toSimpleObject();
            }

        }

        return $listEvents;

    }

    public function getCalendarEventsDaysDataTest($gcalendarId, $timeMin, $timeMax, $count = false)
    {

        $events = $this->getCalendarEvents($gcalendarId, $timeMin, $timeMax, $count);

        $date_max = Carbon::parse($timeMax);

        $listEvents = [];
        foreach ($events->getItems() as $event)
        {
            /** @var $event \Google_Service_Calendar_Event */

            $dateStartObj = $event->getStart();
            if ($dateStartObj->dateTime) {
                $dateStart = $dateStartObj->getDateTime();
            } else {
                $dateStart = $dateStartObj->getDate();
            }
            $date_start  = Carbon::parse($dateStart);
            $dateStart   = $date_start->format('Y-m-d');
            $timeStart   = $date_start->format('H:i');


            $dateEndtObj = $event->getEnd();
            if ($dateEndtObj->dateTime) {
                $dateEnd = $dateEndtObj->getDateTime();
            } else {
                $dateEnd = $dateEndtObj->getDate();
            }
            $date_end    = Carbon::parse($dateEnd);
            if (!$dateEndtObj->dateTime) {
                $date_end->addMinute(-1);
                $time_use = 0;
            } else {
                $time_use = 1;
            }
            $dateEnd     = $date_end->format('Y-m-d');
            $timeEnd     = $date_end->format('H:i');

            $lastModifed = $event->getUpdated();
            $date        = Carbon::parse($lastModifed);
            $dateMod     = $date->format('Y-m-d H:i');


            if ($date_end <= $date_max) {
                $eventData = [
                    'eventId' => $event->getId(),
                    'name' => $event->getSummary(),
                    'description' => $event->getDescription(),
                    'location' => $event->getLocation(),
                    'timeUse' => $time_use,
                    'dateStart' => $dateStart,
                    'timeStart' => $timeStart,
                    'dateEnd' => $dateEnd,
                    'timeEnd' => $timeEnd,
                    'update' => $dateMod,
                    'creatorEmail' => $event->getCreator()->getEmail(),
                    'creatorName' => $event->getCreator()->getDisplayName(),
                    'organizerEmail' => $event->getOrganizer()->getEmail(),
                    'organizerName' => $event->getOrganizer()->getDisplayName()
                ];

                if ($event->getSource() && $event->getSource()->getUrl()) {
                    $eventData['url'] = $event->getSource()->getUrl();
                } else {
                    $eventData['url'] = '';
                }
                $eventData['ICalUID'] = $event->getICalUID();
                $eventData['recurringEventId'] = $event->getRecurringEventId();
                $eventData['recurrence'] = $event->getRecurrence();

                $date_stop = Carbon::parse($dateEnd);

                if ($date_start->format('Y-m-d') != $date_end->format('Y-m-d')) {
                    $date_stop->addDay();
                }


                do {
                    $dateEvent = $date_start->format("Y-m-d");
                    $listEvents[$dateEvent][] = $eventData;

                    $date_start->addDay();

                } while ($date_start < $date_stop);

            }


        }

        return $listEvents;
    }

    public function getCalendars()
    {
        $calendarList = $this->service->calendarList->listCalendarList();

        return $calendarList->getItems();

    }



    private static function missingServiceAccountDetailsWarning()
    {
        $ret = "
    <h3 class='warn'>
      Warning: You need download your Service Account Credentials JSON from the
      <a href='http://developers.google.com/console'>Google API console</a>.
    </h3>
    <p>
      Once downloaded, move them into the root directory of this repository and
      rename them 'service-account-credentials.json'.
    </p>
    <p>
      In your application, you should set the GOOGLE_APPLICATION_CREDENTIALS environment variable
      as the path to this file, but in the context of this example we will do this for you.
    </p>";

        return $ret;
    }

    private static function checkServiceAccountCredentialsFile()
    {
        // service account file
        $application_creds = '../../gap/laravelTangoCalendar-09fd9ec20b64.json';

        return file_exists($application_creds) ? $application_creds : false;
    }

    private function getService()
    {
        $client = new Google_Client();
        if ($credentials_file = self::checkServiceAccountCredentialsFile()) {
            // set the location manually
            $client->setAuthConfig($credentials_file);
        } elseif (getenv('GOOGLE_APPLICATION_CREDENTIALS')) {
            // use the application default credentials
            $client->useApplicationDefaultCredentials();
        } else {
            echo self::missingServiceAccountDetailsWarning();
            return;
        }
        ////////////////////////
        // инициализация сервиса
        $client->setApplicationName("laravelTangoCalendar");
        $client->setScopes(Google_Service_Calendar::CALENDAR);
        $service = new Google_Service_Calendar($client);

        $this->client = $client;
        $this->service = $service;
    }





    ////////////////////////////////////
//  /// метод для авторизации
//    private static function getGapClient()
//    {
//        $file = '../../gap/client_secret_1046737382657-aj1ug2a88t7nb9pb9kv3ijqg28qbrt30.apps.googleusercontent.com.json';
//        if (file_exists($file)) {
//
//            $client = new Google_Client();
//            $client->setAuthConfig($file);
//            return $client;
//
//        } else {
//            $res = "not file exist $file";
//            return $res;
//        }
//
//    }
//    public function gappautorise()
//    {
//
//        $client = Gcalendar::getGapClient();
//        // Ваш URI перенаправления может быть любым зарегистрированным URI, но в этом примере
//        // мы перенаправляем обратно на эту же страницу
//        $redirect_uri = 'https://' . $_SERVER['HTTP_HOST'];
//
//        $client->setRedirectUri($redirect_uri);
//        $client->addScope("https://www.googleapis.com/auth/calendar");
//        $service = new Google_Service_Calendar($client);
//
//
//        /************************************************
//         * If we have a code back from the OAuth 2.0 flow,
//         * we need to exchange that with the
//         * Google\Client::fetchAccessTokenWithAuthCode()
//         * function. We store the resultant access token
//         * bundle in the session, and redirect to ourself.
//         ************************************************/
//        if (isset($_GET['code'])) {
//
//            $token = $_GET['code'];
//            var_dump($token);
//            $token = $client->fetchAccessTokenWithAuthCode($token);
//            $client->setAccessToken($token);
//
//            // store in the session also
//            $_SESSION['upload_token'] = $token;
//
//            var_dump($_SESSION);
//
//            // redirect back to the example
////            header('Location: ' . filter_var($redirect_uri, FILTER_SANITIZE_URL));
//
//            file_put_contents('../../gap/test-get.json', json_encode($_GET));
//        }
//
//        // set the access token as part of the client
//        if (!empty($_SESSION['upload_token'])) {
//            $client->setAccessToken($_SESSION['upload_token']);
//            if ($client->isAccessTokenExpired()) {
//                unset($_SESSION['upload_token']);
//            }
//        } else {
//            $authUrl = $client->createAuthUrl();
//        }
//
//    }

}
