<?php


namespace App\Services;


class Gcalendarservice
{
    // получаем события календаря и формируем данные
    public static function getCalendarEventsStatic($gcalendarId, $timeMin, $timeMax, $count)
    {

        $gCalendarService = \App\Models\GcalendarService::setService();
        $events = $gCalendarService->getCalendarEvents($gcalendarId, $timeMin, $timeMax, $count);

        $listEvents = [];
        foreach ($events->getItems() as $event) {
            $eventId                 = $event->getId();
            $eventName               = $event->getSummary();
            $eventDescription        = $event->getDescription();
            $eventLocation           = $event->getLocation();
            $eventCreatorEmail       = $event->getCreator()->getEmail();
            $eventCreatorName        = $event->getCreator()->getDisplayName();
            $eventOrganizerEmail     = $event->getOrganizer()->getEmail();
            $eventOrganizerName      = $event->getOrganizer()->getDisplayName();
            $eventType               = $event->getEventType();
            $GgHtmlLink              = $event->getHtmlLink();
//            var_dump($GgHtmlLink);

            if (empty($eventDescription)) {
                $eventDescription = '';
            }

            if (empty($eventLocation)) {
                $eventLocation = '';
            }


            $dateStartObj = $event->getStart();
            $dateStart    = $dateStartObj->getDateTime();
            $date         = new \DateTime($dateStart);
            $dateStart    = $date->format('Y-n-j');
            $timeStart    = $date->format('H-i');


            $dateEndtObj = $event->getEnd();
            $dateEnd     = $dateEndtObj->getDateTime();
            $date        = new \DateTime($dateEnd);
            $dateEnd     = $date->format('Y-n-j');
            $timeEnd     = $date->format('H-i');

            $lastModifed = $event->getUpdated();
            $date        = new \DateTime($lastModifed);
            $dateMod     = $date->format('Y-m-d H:i');


            $listEvents[$dateStart] = [
                'eventId'        => $eventId,
                'name'           => $eventName,
                'description'    => $eventDescription,
                'location'       => $eventLocation,
                'dateStart'      => $dateStart,
                'timeStart'      => $timeStart,
                'dateEnd'        => $dateEnd,
                'timeEnd'        => $timeEnd,
                'update'         => $dateMod,
                'creatorEmail'   => $eventCreatorEmail,
                'creatorName'    => $eventCreatorName,
                'organizerEmail' => $eventOrganizerEmail,
                'organizerName'  => $eventOrganizerName
            ];

        }

        return $listEvents;

    }


}
