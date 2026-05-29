<?php

namespace App\Livewire\Calendar;

use App\Jobs\ProcessCalendarEvents;
use App\Models\EventsCalendarsMap;
use App\Models\Gcalendar;
use App\Models\GcalendarService;
use App\Services\CalendarDataService;
use Carbon\Carbon;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Cache;
use Livewire\Component;
use Livewire\Attributes\Url;

class EventsCalendarPage extends Component
{
    #[Url(as: 'type', except: '')]
    public string $typesParam = '';
    #[Url(as: 'city', except: '')]
    public string $citiesParam = '';
    #[Url(as: 'select_day', except: '')]
    public ?string $selectedDay = null;

    public ?string $locale = null;

    public ?int $year = null;

    public ?int $monthNumber = null;

    public string $month;

    public array $activeTypes = [];

    public array $activeCities = [];

    public array $activeCountries = [];

    public array $countriesList = [];

    public array $selectedCountries = [];

    public array $calendarsList = [];

    public array $calendarsMap = [];

    public array $calendarsSelected = [3 => 3];

    public array $eventsMap = [];

    public bool $eventsMapLoaded = false;
    public \Illuminate\Support\Collection $weekDays;
    public array $days;
    public string $calendar_title = '';
    public array $subDate;
    public array $nextDate;
    public array $events_future = [];
    public bool $sub_calendar = false;
    public array $calendars_filter = [];

    public function mount(?string $locale = null, ?int $year = null, ?int $monthNumber = null): void
    {
        $this->locale = $locale ?: $this->defaultLocale();
        App::setLocale($this->locale);

        $date = now();

        if ($year) {
            $date->year((int) $year);
        }

        if ($monthNumber) {
            $date->month((int) $monthNumber);
        }


        $this->activeTypes = explode(',', request('type', 'festivals'));

        $this->activeCities =  array_values(
            array_filter(
                explode(',', request('city', ''))
            )
        );

        $this->activeCountries = array_values(
            array_filter(
                explode(',', request('country', ''))
            )
        );

        $this->selectedCountries = $this->resolveSelectedCountries();

        $this->loadCalendarsList();


        $this->loadDateMap($date->format('Y-m-d'));
    }

    private function loadDateMap(string $date_string)
    {
        $date = Carbon::parse($date_string)->startOfMonth();
        $this->calendar_title = $date->locale($this->locale)->translatedFormat('F Y');
        $this->month = $date->format('Y-m');
        $this->eventsMap = $this->buildCountersOnlyMap();
        $this->eventsMap = $this->buildFullEventsMap();
        $date_key = key($this->eventsMap['dates']);
        $this->selectedDay = $date_key ?? $date_string;
        $this->subDate = [
            'year' => $date->copy()->subMonth()->year,
            'month' => $date->copy()->subMonth()->format('m'),
        ];
        $this->nextDate = [
            'year' => $date->copy()->addMonth()->year,
            'month' => $date->copy()->addMonth()->format('m'),
        ];
        $this->weekDays = collect(range(1, 7))->map(function ($day) {
            return now()
                ->startOfWeek()
                ->addDays($day - 1)
                ->locale($this->locale)
                ->translatedFormat('D');
        });

        $this->days = $this->getDaysProperty();
        $this->setFutureEvents();
    }

    private function setFutureEvents()
    {
        $gCalendarService = GcalendarService::setService();

        $calendar = Gcalendar::find(3);
        $cacheKey = 'festivals_all';

        $timeMin = now()->format('Y-m-d') . 'T00:00:00-00:00';
        $events_dates = Cache::remember(
            $cacheKey,
            now()->addHours(0.25),
            fn () => $gCalendarService->getCalendarEventsSingleData($calendar->gcalendarId, $timeMin)
        );

        $events_future = [];
        if (!empty($events_dates)) {
            foreach ($events_dates as $events) {
                foreach ($events as $event) {
                    $id = $event['eventId'];

                    if (!isset($events_future[$id])) {
                        $event['description'] = $this->linkfn($event['description'] ?? '');
                        $events_future[$id] = $event;
                    }
                }
            }

        }
        $this->events_future = $events_future;
    }

    private function defaultLocale(): string
    {
        return in_array(config('app.name'), ['TangoCalendarUA', 'TangoCalendarTest'], true)
            ? 'uk'
            : 'en';
    }

    private function resolveSelectedCountries(): array
    {
        if (!empty($this->activeCountries)) {
            return array_values(array_unique(array_merge(['All'], $this->activeCountries)));
        }

        if (in_array(config('app.name'), ['TangoCalendarUA', 'TangoCalendarTest'], true)) {
            return ['All', 'Ukraine'];
        }

        return ['All'];
    }

    private function loadCalendarsList(): void
    {
        $this->calendarsMap = Gcalendar::query()
            ->whereIn('country', $this->selectedCountries)
            ->get()
            ->keyBy('id')
            ->toArray();
    }

    private function buildCountersOnlyMap(): array
    {
        $gCalendarService = GcalendarService::setService();

        $map = [
            'category_map' => [],
            'selected_categories' => [],
            'events' => [],
            'dates' => [],
            'calendars' => [],
        ];

        $date = Carbon::parse($this->month . '-01');
        $year = $date->format('Y');
        $month = $date->format('m');

        $calendarIds = array_keys($this->calendarsMap);

        $counts = EventsCalendarsMap::query()
            ->whereIn('calendarId', $calendarIds)
            ->where('year', $year)
            ->where('month', $month)
            ->pluck('events_count', 'calendarId')
            ->toArray();

        foreach ($this->calendarsMap as $cid => $calendar) {
            $cid = (int) $cid;
            $category = $calendar['type_events'];

            if ($this->isIgnoredCategory($category)) {
                continue;
            }

            if ($this->isIgnoredCalendar($cid)) {
                continue;
            }

            $cacheKey = 'calendar_info_' . $cid;

            $calendarData = Cache::remember(
                $cacheKey,
                now()->addHours(36),
                fn () => $gCalendarService->getCalendarInfo($calendar['gcalendarId'])
            );

            $map['calendars'][$cid] = array_merge($calendarData, $calendar);

            $eventsCount = (int) ($counts[$cid] ?? 0);

            if ($eventsCount === 0) {
                ProcessCalendarEvents::dispatch($date->copy(), $cid);
            }

            $country = $calendar['country'] ?? null;
            $city = $calendar['city'] ?? null;

            $map['category_map'][$category]['count'] =
                ($map['category_map'][$category]['count'] ?? 0) + $eventsCount;

            $map['category_map'][$category][$country]['count'] =
                ($map['category_map'][$category][$country]['count'] ?? 0) + $eventsCount;


            if (!empty($city)) {
                $map['category_map'][$category][$country][$city]['count'] =
                    ($map['category_map'][$category][$country][$city]['count'] ?? 0) + $eventsCount;

                $map['category_map'][$category][$country][$city]['calendars'][$cid] = $eventsCount;
            } else {
                $map['category_map'][$category][$country]['calendar'] = $cid;
            }
        }

        return $map;
    }

    public function buildFullEventsMap(): array
    {
        $map = $this->eventsMap;
        $map['dates'] = [];
        $calendars_filter = $this->calendars_filter;

        $date = Carbon::parse($this->month . '-01');
        $calendarDataService = new CalendarDataService();

        $selectedCalendarIds = $this->resolveSelectedCalendarIds();

        foreach ($selectedCalendarIds as $calendarId) {
            if (! isset($this->calendarsMap[$calendarId])) {
                continue;
            }
            if (isset($calendars_filter[$calendarId])) {
                continue;
            }

            $calendar = $this->calendarsMap[$calendarId];
            $map['selected_categories'][$calendar['type_events']] = 1;

            $cacheKey = 'calendar_events_month:' . md5($calendarId . ':' . $date->format('Y-m'));

            $calendarData = Cache::remember(
                $cacheKey,
                now()->addMinutes(15),
                fn () => $calendarDataService->getCalendarEventsDb(
                    ['month' => $date->format('Y-m-d')],
                    $calendarId
                )
            );

            if (empty($calendarData['dates'])) {
                continue;
            }

            foreach ($calendarData['dates'] as $day => $dayData) {
                $map['dates'][$day] = array_merge($map['dates'][$day] ?? [], $dayData);
            }

            $map['events'] = array_merge($map['events'], $calendarData['events'] ?? []);
        }
        $map = $this->dayEventsData($map);

        return $map;
    }

    private function resolveSelectedCalendarIds(): array
    {
        $selected = $this->calendarsSelected;

        foreach ($this->calendarsMap as $cid => $calendar) {
            $cid = (int) $cid;

            if ($this->isIgnoredCalendar($cid)) {
                continue;
            }

            $category = $calendar['type_events'];

            if ($this->isIgnoredCategory($category)) {
                continue;
            }

            if (! in_array($category, $this->activeTypes, true)) {
                continue;
            }

            $country = $calendar['country'] ?? null;
            $city = $calendar['city'] ?? null;

            // Если это городской тип — без выбранного города не подтягиваем
            if (
                in_array($category, ['tango_school', 'practices', 'milongas'], true)
                && ! in_array($city, $this->activeCities, true)
            ) {
                continue;
            }

            // Если в этой категории уже есть All-календарь,
            // обычные country/city календари не подтягиваем автоматически
            if ($this->categoryHasSelectedAllCalendar($category, $selected)) {
                if ($country !== 'All') {
                    continue;
                }
            }

            $selected[$cid] = $cid;
        }

        return array_values($selected);
    }

    private function categoryHasSelectedAllCalendar(string $category, array $selected): bool
    {
        foreach ($selected as $cid) {
            $calendar = $this->calendarsMap[$cid] ?? null;

            if (! $calendar) {
                continue;
            }

            if (
                ($calendar['type_events'] ?? null) === $category
                && ($calendar['country'] ?? null) === 'All'
            ) {
                return true;
            }
        }

        return false;
    }

    private function isIgnoredCategory(string $category): bool
    {
        return in_array($category, [
            'festival_shedule',
            'online_events',
            'about_tango',
        ], true);
    }

    private function isIgnoredCalendar(int $calendarId): bool
    {
        return in_array($calendarId, [
            34,
        ], true);
    }

    public function getDaysProperty(): array
    {
        $date = Carbon::parse($this->selectedDay)->startOfMonth();

        $start = $date->copy()->startOfWeek(Carbon::MONDAY);
        $end = $date->copy()->endOfMonth()->endOfWeek(Carbon::SUNDAY);

        $days = [];
        $current = $start->copy();

        while ($current <= $end) {
            $days[] = [
                'date' => $current->copy(),
                'date_key' => $current->format('Y-m-d'),
                'day' => $current->day,
                'is_current_month' => $current->month === $date->month,
                'is_today' => $current->isToday(),
                'is_selected' => $this->selectedDay === $current->format('Y-m-d'),
            ];

            $current->addDay();
        }

        return $days;
    }

    public function getWeekDaysProperty()
    {
        return collect(range(1, 7))->map(function ($day) {
            return now()
                ->startOfWeek()
                ->addDays($day - 1)
                ->locale(App::getLocale())
                ->translatedFormat('D');
        });
    }

    public function toggleType(string $type): void
    {
        if (in_array($type, $this->activeTypes, true)) {
            $this->activeTypes = array_values(array_diff($this->activeTypes, [$type]));
        } else {
            $this->activeTypes[] = $type;
        }

        $this->typesParam = implode(',', $this->activeTypes);

        $this->eventsMap = $this->buildFullEventsMap();
        $this->dispatchBrowserUrl();
    }

    public function toggleCity(string $city, ?string $type = null): void
    {
        if (in_array($city, $this->activeCities, true)) {
            $this->activeCities = array_values(array_diff($this->activeCities, [$city]));
        } else {
            $this->activeCities[] = $city;
        }

        $this->citiesParam = implode(',', $this->activeCities);

        $this->eventsMap = $this->buildFullEventsMap();
        $this->dispatchBrowserUrl();
    }

    public function toggleDate(string $date): void
    {
        $this->selectedDay = $date;
        $this->sub_calendar = false;
        $this->dispatchBrowserUrl();
    }

    public function toggleMonth(string $flag): void
    {
        if ($flag == 'sub') {
            $date_string = $this->subDate['year'] . '-' . $this->subDate['month'] . '-01';
            $set_dates = $this->subDate;
        } else {
            $date_string = $this->nextDate['year'] . '-' . $this->nextDate['month'] . '-01';
            $set_dates = $this->nextDate;
        }

        $this->loadDateMap($date_string);

        $this->dispatchBrowserUrl($set_dates);
    }

    public function toggleView($view_string)
    {
        switch ($view_string) {
            case 'sub_calendar':
                $this->sub_calendar = $this->sub_calendar == false ? true : false;
        }
    }

    public function toggleCalendar($cid)
    {
        $calendars_filter = $this->calendars_filter;
        $calendar = $this->calendarsMap[$cid] ?? null;

        if (! $calendar) {
            return;
        }

        $category = $calendar['type_events'];
        $country = $calendar['country'] ?? null;

        if ($category == 'festivals') {

            $calendarsSelected = $this->calendarsSelected;
            // Если нажали на All
            if ($country === 'All') {
                if (isset($calendars_filter[$cid])) {
                    unset($calendars_filter[$cid]);

                    $calendarsSelected[$cid] = $cid;
                    // включили All обратно — остальные фестивальные календари скрываем
                    foreach ($this->calendarsMap as $otherCid => $otherCalendar) {
                        if (
                            ($otherCalendar['type_events'] ?? null) === 'festivals'
                            && ($otherCalendar['country'] ?? null) !== 'All'
                        ) {
                            $calendars_filter[(int) $otherCid] = 1;
                            unset($calendarsSelected[$otherCid]);
                        }
                    }
                } else {
                    // отключили All — открываем остальные фестивальные календари
                    $calendars_filter[$cid] = 1;
                    unset($calendarsSelected[$cid]);

                    foreach ($this->calendarsMap as $otherCid => $otherCalendar) {
                        if (
                            ($otherCalendar['type_events'] ?? null) === 'festivals'
                            && ($otherCalendar['country'] ?? null) !== 'All'
                        ) {
                            unset($calendars_filter[(int) $otherCid]);
                            $calendarsSelected[$otherCid] = $otherCid;
                        }
                    }
                }

            } else {
                if (isset($calendarsSelected[$cid])) {
                    unset($calendarsSelected[$cid]);
                } else {
                    unset($calendars_filter[$cid]);
                    $calendarsSelected[$cid] = $cid;
                }

                unset($calendarsSelected[3]);
                $calendars_filter[3] = 1;
            }
            $this->calendarsSelected = $calendarsSelected;
        } else {
            if (isset($calendars_filter[$cid])) {
                unset($calendars_filter[$cid]);
            } else {
                $calendars_filter[$cid] = 1;
            }
        }

        $this->calendars_filter = $calendars_filter;

        $this->eventsMap = $this->buildFullEventsMap();
    }
    public function buildUrl(array $changes = [], $set_params = []): string
    {
        $params = [];

        $date = Carbon::parse($this->selectedDay);

        $params['locale'] = app()->getLocale();

        if (request()->route()->hasParameter('year')) {
            $params['year'] = $date->year;
        }

        if (request()->route()->hasParameter('month')) {
            $params['month'] = $date->format('m');
        }

        if (isset($set_params['month'])) {
            $params['month'] = $set_params['month'];
        }
        if (isset($set_params['year'])) {
            $params['year'] = $set_params['year'];
        }

        $query = [
            'type' => ! empty($this->activeTypes)
                ? implode(',', $this->activeTypes)
                : null,

            'city' => ! empty($this->activeCities)
                ? implode(',', $this->activeCities)
                : null,

            'country' => ! empty($this->activeCountries)
                ? implode(',', $this->activeCountries)
                : null,

        ];

        $query = array_merge($query, $changes);
        $query = array_merge($params, $query);

        return route('calendar.show', $query);
    }
    private function dispatchBrowserUrl($dates = []): void
    {
        $this->dispatch('calendar-url-updated', url: $this->buildUrl([], $dates));
    }
    public function toggleUrlValue(array $values, string $value): string
    {
        if (in_array($value, $values, true)) {
            $values = array_values(array_diff($values, [$value]));
        } else {
            $values[] = $value;
        }

        return implode(',', array_unique($values));
    }
    public function switchLocale(string $locale): void
    {
        app()->setLocale($locale);

        $params = request()->query();
        $params['locale'] = $locale;

        if (request()->route()->hasParameter('year')) {
            $params['year'] = Carbon::parse($this->month . '-01')->year;
        }

        if (request()->route()->hasParameter('month')) {
            $params['month'] = Carbon::parse($this->month . '-01')->format('m');
        }

        $this->redirectRoute('calendar.show', $params, navigate: true);
    }

    private function dayEventsData($map)
    {
        if (!empty($map['events'])) {
            foreach ($map['events'] as  &$event) {
                $event['description'] = $this->linkfn($event['description'] ?? '');
            }
        }

        return $map;
    }
    private function linkfn($text)
    {
        $text = e($text ?? '');

        return preg_replace_callback(
            '~(https?://[^\s<]+|www\.[^\s<]+)~i',
            function ($matches) {
                $url = $matches[1];

                $href = str_starts_with($url, 'www.')
                    ? 'https://' . $url
                    : $url;

                return '<a href="' . $href . '" target="_blank" rel="noopener noreferrer">' . $url . '</a>';
            },
            $text
        );
    }

    public function render()
    {
        return view('livewire.calendar.events-calendar-page');
    }
}
