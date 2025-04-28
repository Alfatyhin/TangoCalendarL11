<?php

namespace App\Livewire;

use App\Models\Event;
use App\Models\EventsCalendarsMap;
use App\Models\Gcalendar;
use App\Models\GcalendarService;
use App\Services\CalendarDataService;
use Carbon\Carbon;
use Illuminate\Support\Facades\Cache;
use Livewire\Component;

class CalendarsList extends Component
{
    public $calendars;
    public $countries;
    public $countries_list;
    public $countries_list_selected = ['All'];
    public $countries_selected = [];
    public $type_events;
    public $cityes;

    public $currentDate;
    public array $weeks;
    private mixed $startDate;
    private mixed $endDate;


    protected $listeners = ['searchSelected' => 'searchSelected', 'calendarListen' => 'calendarListen'];
    public $calendars_list;
    public array $calendars_selected = [];
    public array $calendars_hiddens = [];
    public array $eventsMap = [
        'dates' => [],
        'events' => []
    ];
    public $select_date = '';
    public array $calendarsMap = [];
    public $openEvent;

    public $view_mode = 'list';
    private Carbon $nowDate;
    /**
     * @var array|mixed
     */
    public array $festivalsMap = [];

    public function mount()
    {
        //  получаем список календарей
        $calendars = Gcalendar::select('gcalendarId', 'type_events', 'country', 'city', 'id')
            ->get()
            ->keyBy('id');
        $this->countries = $calendars->groupBy('country')->map(fn($items) => $items->pluck('id')->toArray());
        $this->countries_list = Gcalendar::where('country', '!=', 'All')->distinct('country')->pluck(
            'country'
        )->toArray();
        $this->type_events = Gcalendar::distinct('type_events')->pluck('type_events')->toArray();
        $this->cityes = $calendars->groupBy('city')->map(fn($items) => $items->pluck('id')->toArray());

        $this->nowDate = Carbon::now();
        $this->currentDate = Carbon::now()->startOfMonth();
        $this->getCalendarsList();
        $this->getDates();
        $this->getFestivals();

        if (config('app.name') == 'TangoCalendarUA' || config('app.name') == 'TangoCalendarTest') {
            $this->searchSelected([42], 'select_country');
            $this->countries_selected = [42];
        }
    }

    public function setViewMode()
    {
        $this->view_mode = $this->view_mode == 'calendar' ? 'list' : 'calendar';
        $this->getEvents();
    }

    public function previousMonth()
    {
        $this->currentDate = $this->currentDate->subMonth()->startOfMonth();
        $this->getDates();
    }

    public function nextMonth()
    {
        $this->currentDate = $this->currentDate->addMonth()->startOfMonth();
        $this->getDates();
    }

    public function selectDate($date)
    {
        if ($this->select_date == $date || $date == 'close') {
            $this->select_date = '';
        } else {
            $this->select_date = $date;
        }
    }

    public function getDates()
    {
        $daysInMonth = $this->currentDate->daysInMonth;
        $firstDayOfMonth = $this->currentDate->copy()->startOfMonth()->dayOfWeek;
        $lastDayOfMonth = $this->currentDate->copy()->endOfMonth()->dayOfWeek;

        $weeks = [];
        $week = [];

        // Добавляем предыдущий месяц
        $prevMonth = $this->currentDate->copy()->subMonth();
        $prevDays = $firstDayOfMonth === 0 ? 6 : $firstDayOfMonth - 1;
        for ($i = $prevDays; $i > 0; $i--) {
            $week[] = [
                'date' => $prevMonth->copy()->endOfMonth()->subDays($i - 1),
                'current' => false
            ];
        }

        // Текущий месяц
        for ($i = 1; $i <= $daysInMonth; $i++) {
            $week[] = [
                'date' => $this->currentDate->copy()->day($i),
                'current' => true
            ];
            if (count($week) == 7) {
                $weeks[] = $week;
                $week = [];
            }
        }

        // Добавляем следующий месяц
        $nextMonth = $this->currentDate->copy()->addMonth();
        if ($lastDayOfMonth != 0) {
            $nextDays = $lastDayOfMonth === 7 ? 0 : 7 - $lastDayOfMonth;
            for ($i = 1; $i <= $nextDays; $i++) {
                $week[] = [
                    'date' => $nextMonth->copy()->day($i),
                    'current' => false
                ];
                if (count($week) == 7) {
                    $weeks[] = $week;
                    $week = [];
                }
            }
        }

        if (!empty($week)) {
            $weeks[] = $week;
        }

        $this->startDate = $weeks[0][0]['date'];
        $this->endDate = $weeks[sizeof($weeks) - 1][6]['date'];

        $this->weeks = $weeks;

        $this->getEvents();
    }



    public function selectCalendar($calendarId)
    {
        dump($calendarId);
        if (in_array($calendarId, $this->calendars_selected)) {
            $calendars_selected = [];
            foreach ($this->calendars_selected as $item) {
                if ($calendarId != $item) {
                    $calendars_selected[] = $item;
                }
            }
            $this->calendars_selected = $calendars_selected;
        } else {
            $this->calendars_selected[] = $calendarId;
        }

        $this->getEvents();
    }



    public function calendarHide($calendarId)
    {
        $calendars_hiddens = $this->calendars_hiddens;

        if (isset($calendars_hiddens[$calendarId])) {
            unset($calendars_hiddens[$calendarId]);
        } else {
            if (sizeof($calendars_hiddens) != sizeof($this->calendars_selected)) {
                $calendars_hiddens[$calendarId] = $calendarId;
            }
        }

        $this->calendars_hiddens = $calendars_hiddens;
        $this->getEvents();

    }

    public function getFestivals($date = null, $count = 0)
    {
        $festivalsMap = [];
        if (!$date) {
            $currentDate = $this->nowDate;
        } else {
            $currentDate = $date;
        }


        $eventsMonthMap = EventsCalendarsMap::where('calendarId', 3)
            ->where('year', $currentDate->format('Y'))
            ->where('month', $currentDate->format('m'))
            ->select('eventsDatesIds')
            ->get()
            ->toArray();

        foreach ($eventsMonthMap as $eventsMonth) {
            $eventsDatesIds = json_decode($eventsMonth['eventsDatesIds'], true);
            foreach ($eventsDatesIds as $date => $data) {
                foreach ($data as $eventId => $v) {
                    $date_event_start = Carbon::parse($v['dateStart']);
                    $date_event_end = Carbon::parse($v['dateEnd']);

                    if ($date_event_start->isAfter($this->nowDate) || $date_event_end->isAfter($this->nowDate)) {
//                        dd($v);
                        if (!isset($this->festivalsMap[$eventId]) && !isset($festivalsMap[$eventId])) {
                            $event = Event::where('calendarId', 3)->where('eventId', $eventId)->first();
                            if (!$event) {
                                $ids = explode('_', $eventId);
                                $eventId = $ids[0];
                                if (!isset($festivalsMap[$eventId])) {
                                    $event = Event::where('calendarId', 3)->where('eventId', $eventId)->first();
                                }
                            }
                            if ($event) {
                                $festivalsMap[$eventId] = json_decode($event->data, true);
                                $festivalsMap[$eventId]['date_data'] = $v;
                                $festivalsMap[$eventId]['description'] = $this->wrapLinksInText($festivalsMap[$eventId]['description'] ?? '');
                            }
                        }
                    }

                }
            }
        }

        $this->festivalsMap = array_merge($this->festivalsMap, $festivalsMap);
        if (sizeof($festivalsMap) < 10 && $count <= 9) {
            $count++;
            $this->getFestivals($currentDate->endOfMonth()->addDay(), $count);
        }
    }
    public function getEvents()
    {
        $currentDate = $this->currentDate;
        $calendars_selected = $this->calendars_selected;


        if (!empty($this->calendars_hiddens)) {
            foreach ($calendars_selected as $k => $cid) {
                if (isset($this->calendars_hiddens[$cid])) {
                    unset($calendars_selected[$k]);
                }
            }
        }


        $calendarDataService = new CalendarDataService();
        foreach ($calendars_selected as $cid) {
            $eventsMonthMap[$cid] = $calendarDataService->getCalendarEvents(['month' => $currentDate->format('Y-m-d')], $cid);
        }

        $resEvents =  [
            'dates' => [],
            'events' => []
        ];

        $eventsIds = [];
        foreach ($eventsMonthMap as $id => $eventsMonth) {
            $eventsDatesIds = $eventsMonth['dates'];
            $eventsIdsMonth = $eventsMonth['events'];

            foreach ($eventsDatesIds as $date => $data) {
                $resEvents['dates'][$date][$id] = $data;

                foreach ($data as $eventId => $v) {

                    $event = $eventsMonth['events'][$eventId];
                    if (!isset($eventsIds[$eventId])) {
                        $eventsIds[$eventId] = $event;
                        $eventsIds[$eventId]['description'] = $this->wrapLinksInText($event['description'] ?? '');
                        $eventsIds[$eventId]['date_data'] = $v;
                    }
                    if ($v['dateStart'] != $v['dateEnd'] && $this->view_mode == 'list') {
                        if ($v['dateStart'] != $date) {
                            unset($resEvents['dates'][$date][$id][$eventId]);
                            if (empty($resEvents['dates'][$date][$id])) {
                                unset($resEvents['dates'][$date][$id]);
                            }
                            if (empty($resEvents['dates'][$date])) {
                                unset($resEvents['dates'][$date]);
                            }
                        }
                    }
                }
            }
        }

        $resEvents['events'] = $eventsIds;
        $this->eventsMap = $resEvents;
    }

    public function searchSelected($selected, $select_name)
    {
        if ($select_name == 'select_country') {
            $set_selected = ['All'];
            if (!empty($selected)) {
                foreach ($selected as $k) {
                    $set_selected[] = $this->countries_list[$k];
                }
            }

            $this->countries_list_selected = $set_selected;
        }

        $this->getCalendarsList();
    }

    private function getCalendarsList()
    {
        // объект приложения
        $gCalendarService = GcalendarService::setService();

        $calendars_list = Gcalendar::whereIn('country', $this->countries_list_selected)
            ->get();
        $this->calendars_list = [];
        foreach ($calendars_list as $calendar) {
            $cacheKey = "calendar_info_{$calendar->id}";

            // Получаем данные из кеша или запрашиваем, если их нет
            $calendar_info = Cache::remember($cacheKey, now()->addHours(6), function () use ($gCalendarService, $calendar) {
                return $gCalendarService->getCalendarInfo($calendar->gcalendarId);
            });
            if(empty($this->calendars_selected)) {
                $this->calendars_selected[] = $calendar->id;
            }
            $this->calendars_list[$calendar->type_events][$calendar->id] = array_merge($calendar->toArray(), $calendar_info);
            $this->calendarsMap[$calendar->id] = array_merge($calendar->toArray(), $calendar_info);
        }
    }

    public function setOpenEvent($event_id)
    {
        $this->openEvent = $event_id;
    }

    private function wrapLinksInText($text) {
        $pattern = '/(https?:\/\/\S+)/i';
        $replacement = '<a href="$1" target="_blank">$1</a>';
        return preg_replace($pattern, $replacement, $text);
    }

    public function render()
    {
        return view('livewire.calendars-list', [
            'eventsMap' => $this->eventsMap,
            'weeks' => $this->weeks,
            'monthYear' => $this->currentDate->format('F Y'),
            'calendars_list' => $this->calendars_list,
            'calendars_selected' => $this->calendars_selected,
        ]);
    }
}
