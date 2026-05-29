<div class="tango-page">
    <div class="overlay" wire:loading.flex  wire:target="toggleType,toggleCity,loadEventsMap,toggleMonth">
        <span>Завантажуємо події...</span>
    </div>
    <header class="mobile-topbar">
        <button class="mobile-menu-btn" type="button" data-mobile-panel="mobileMenu" aria-label="Open menu">
            ☰
        </button>

        <a class="logo mobile-logo" href="{{ route('main', ['locale' => App::getLocale()]) }}">
            <span>TANGO</span>
            <small>CALENDAR</small>
        </a>

        <button class="mobile-calendar-btn" type="button" data-mobile-panel="mobileFilters" aria-label="Open filters">
            ▦
        </button>
    </header>

    <div class="mobile-overlay" data-mobile-close></div>

    <aside class="mobile-panel mobile-panel--menu" id="mobileMenu">
        <div class="mobile-panel__head">
            <span>Меню</span>
            <button type="button" data-mobile-close>×</button>
        </div>

        <a class="logo" href="{{ route('main', ['locale' => App::getLocale()]) }}">
            <span>TANGO</span>
            <small>CALENDAR</small>
        </a>

        <nav class="mobile-panel__nav">
            <a href="#"><span>♡</span> {{ __('site.favorites') }}</a>
        </nav>

        <div class="mobile-panel__bottom">
            <div class="subscribe-small">
                <h4>{{ __('site.subscription.title') }}</h4>
                <p>{{ __('site.subscription.description') }}</p>
                <form>
                    <input type="email" placeholder="{{ __('site.subscription.email_placeholder') }}">
                    <button type="button">→</button>
                </form>
            </div>

            <div class="lang">
                <a class="cursor-pointer lang {{ App::getLocale() == 'uk' ? 'active' : '' }}"
                    {{--                   href="{{ route('calendar.show', [--}}
                    {{--                            'locale' => 'uk',--}}
                    {{--                            'year' => $dateStart->year,--}}
                    {{--                            'month' => $dateStart->format('m'),--}}
                    {{--                       ]) }}"--}}
                >
                    <span>UA</span>
                </a>

                <span>/</span>

                <a class="cursor-pointer lang {{ App::getLocale() == 'en' ? 'active' : '' }}"
                    {{--                   href="{{ route('calendar.show', [--}}
                    {{--                            'locale' => 'en',--}}
                    {{--                            'year' => $dateStart->year,--}}
                    {{--                            'month' => $dateStart->format('m'),--}}
                    {{--                       ]) }}"--}}
                >
                    <span>EN</span>
                </a>
            </div>
        </div>
    </aside>

    <aside class="mobile-panel mobile-panel--filters" id="mobileFilters">
        <div class="mobile-panel__head">
            <span>Фільтри</span>
            <button type="button" data-mobile-close>×</button>
        </div>

        <nav class="side-nav mobile-filter-nav">
            @include('calendar.layouts.menu_filter')
        </nav>
    </aside>

    <aside class="sidebar">
        <div>
            <a class="logo" href="{{ route('main', ['locale' => App::getLocale()]) }}">
                <span>TANGO</span>
                <small>CALENDAR</small>
            </a>

            <nav class="side-nav">
                @include('calendar.layouts.menu_filter')
            </nav>
        </div>

        <div class="sidebar-bottom">
            <div class="subscribe-small">
                <h4>{{ __('site.subscription.title') }}</h4>
                <p>{{ __('site.subscription.description') }}</p>
                <form>
                    <input type="email" placeholder="{{ __('site.subscription.email_placeholder') }}">
                    <button type="button">→</button>
                </form>
            </div>

            <div class="lang">
                <a class="cursor-pointer lang {{ App::getLocale() == 'uk' ? 'active' : '' }}"
                   wire:click="switchLocale('uk')" >
                    <span>UA</span>
                </a>

                <span>/</span>

                <a class="cursor-pointer lang {{ App::getLocale() == 'en' ? 'active' : '' }}"
                   wire:click="switchLocale('en')" >
                    <span>EN</span>
                </a>
            </div>
        </div>
    </aside>

    <main class="content">
        <section class="hero">
            <div class="hero-bg"></div>
            <div class="hero-content">
                <h1>{{ __('site.hero.title') }}</h1>
                <p>{{ __('site.hero.subtitle') }}</p>
                {{--                <a href="#events" class="btn-red">{{ __('site.hero.button') }}<span>→</span></a>--}}
            </div>

            <div class="calendar-card" id="calendar">
                <div class="calendar-head">
                    <button>‹</button>
                    <strong>
                        {{ $calendar_title }}
                    </strong>
                    <div>
                        <a class="cursor-pointer" href="{{ $this->buildUrl([], $subDate) }}"
                           wire:click.prevent="toggleMonth('sub')">
                            <button>←</button>

                        </a>
                        <a class="cursor-pointer" href="{{ $this->buildUrl([], $nextDate) }}"
                           wire:click.prevent="toggleMonth('next')">
                            <button>→</button>
                        </a>
                    </div>
                </div>

                <div class="calendar-grid calendar-week">
                    @foreach($weekDays as $weekDay)
                        <span>{{ $weekDay }}</span>
                    @endforeach
                </div>

                <div class="calendar-grid calendar-days">
                    @foreach($days as $day)
                        @php
                            $dateKey = $day['date']->format('Y-m-d');
                            $dayEvents = $eventsMap['dates'][$dateKey] ?? [];
                            $eventsCount = count($dayEvents);
                            $dotsCount = min($eventsCount, 4);
                        @endphp

                        <div
                            @class([
                                'calendar-day',
                                'selected' => $selectedDay == $day['date']->format('Y-m-d'),
                                'is_current_month' => $day['is_current_month'],
                                'has-events' => $eventsCount > 0,
                            ])
                        >
                    <span class="calendar-day-number">

                        @if($eventsCount > 0)
                            <a class="cursor-pointer"
                               href="{{ $this->buildUrl(['select_day' => $day['date_key'], ]) }}"
                               wire:click.prevent="toggleDate('{{ $day['date_key'] }}')">
                                {{ $day['day'] }}
                            </a>
                        @else
                            <a class="cursor-pointer">
                                {{ $day['day'] }}
                            </a>
                        @endif

                    </span>

                            @if($eventsCount > 0)
                                <div class="calendar-day-dots">
                                    @for($i = 0; $i < $dotsCount; $i++)
                                        <span></span>
                                    @endfor
                                </div>

                                <div class="calendar-popover">
                                    <div class="calendar-popover-title cursor-pointer">
                                        {{ $day['date']->translatedFormat('d F') }}
                                    </div>

                                    <div class="calendar-popover-events">
                                        @foreach($dayEvents as $ev_id => $event_time)
                                            @php
                                                $event = $eventsMap['events'][$ev_id];
                                                $eventTitle = $event['summary'] ?? '';
                                                $startDay = $event_time['dateStart'] ?? '';
                                                $endDay = $event_time['dateEnd'] ?? '';
                                                $startDate = $startDay . $event_time['timeStart'] ?? '';
                                                $endDate = $endDay . $event_time['timeEnd'] ?? '';
                                            @endphp

                                            <div class="calendar-popover-event">
                                                <div class="calendar-popover-event-title">
                                                    {{ $eventTitle }}
                                                </div>

                                                @if($startDate)
                                                    <div class="calendar-popover-event-date">
                                                        {{ \Carbon\Carbon::parse($startDate)->translatedFormat('d M H.i') }}

                                                        @if($endDate)
                                                            — {{ \Carbon\Carbon::parse($endDate)->translatedFormat('d M H.i') }}
                                                        @endif
                                                    </div>
                                                @endif
                                            </div>
                                        @endforeach
                                    </div>
                                </div>
                            @endif
                        </div>
                    @endforeach
                </div>

                {{--                <button class="filter-btn">--}}
                {{--                    <span>⚙ Фильтры</span>--}}
                {{--                    <span>☷</span>--}}
                {{--                </button>--}}
            </div>
        </section>

        <section class="events-section">
            <livewire:country-events-dropdown/>

            @php
                $monthTitle = \Carbon\Carbon::parse($selectedDay)->translatedFormat('F Y');
            @endphp

            <div class="month-switcher">
                <a class="cursor-pointer" href="{{ $this->buildUrl([], $subDate) }}"
                   wire:click.prevent="toggleMonth('sub')">
                    <button class="month-switcher__arrow">←</button>
                </a>


                <div class="month-switcher__center">
                    <button class="month-switcher__title" type="button">
                        {{ $monthTitle }}
                    </button>

                    <div class="month-switcher__calendar-btn" type="button"
                         wire:click="toggleView('sub_calendar')">
                        <img class="icon" src="/assets/icons/calendar_dark.png">
                    </div>

                    @if($sub_calendar)
                        <div class="month-calendar-popover">
                            <div class="month-calendar-popover__head">
                                <strong>
                                    {{ $calendar_title }}
                                </strong>
                            </div>

                            <div class="month-calendar-popover__week">
                                @foreach($weekDays as $weekDay)
                                    <span>{{ $weekDay }}</span>
                                @endforeach
                            </div>

                            <div class="month-calendar-popover__days">
                                @foreach($days as $day)
                                    @php
                                        $dateKey = $day['date']->format('Y-m-d');
                                        $dayEvents = $eventsMap['dates'][$dateKey] ?? [];
                                        $eventsCount = count($dayEvents);
                                        $dotsCount = min($eventsCount, 4);
                                    @endphp

                                    <div
                                        @class([
                                            'calendar-day',
                                            'selected' => $selectedDay == $day['date']->format('Y-m-d'),
                                            'is_current_month' => $day['is_current_month'],
                                            'has-events' => $eventsCount > 0,
                                        ])
                                    >
                    <span class="calendar-day-number">

                        @if($eventsCount > 0)
                            <span class="cursor-pointer"
                                  wire:click.prevent="toggleDate('{{ $day['date_key'] }}')">
                                {{ $day['day'] }}
                            </span>
                        @else
                            <span class="cursor-pointer">
                                {{ $day['day'] }}
                            </span>
                        @endif

                    </span>

                                        @if($eventsCount > 0)
                                            <div class="calendar-day-dots">
                                                @for($i = 0; $i < $dotsCount; $i++)
                                                    <span></span>
                                                @endfor
                                            </div>

                                            <div class="calendar-popover">
                                                <div class="calendar-popover-title cursor-pointer">
                                                    {{ $day['date']->translatedFormat('d F') }}
                                                </div>

                                                <div class="calendar-popover-events">
                                                    @foreach($dayEvents as $ev_id => $event_time)
                                                        @php
                                                            $event = $eventsMap['events'][$ev_id];
                                                            $eventTitle = $event['summary'] ?? '';
                                                            $startDay = $event_time['dateStart'] ?? '';
                                                            $endDay = $event_time['dateEnd'] ?? '';
                                                            $startDate = $startDay . $event_time['timeStart'] ?? '';
                                                            $endDate = $endDay . $event_time['timeEnd'] ?? '';
                                                        @endphp

                                                        <div class="calendar-popover-event">
                                                            <div class="calendar-popover-event-title">
                                                                {{ $eventTitle }}
                                                            </div>

                                                            @if($startDate)
                                                                <div class="calendar-popover-event-date">
                                                                    {{ \Carbon\Carbon::parse($startDate)->translatedFormat('d M H.i') }}

                                                                    @if($endDate)
                                                                        — {{ \Carbon\Carbon::parse($endDate)->translatedFormat('d M H.i') }}
                                                                    @endif
                                                                </div>
                                                            @endif
                                                        </div>
                                                    @endforeach
                                                </div>
                                            </div>
                                        @endif
                                    </div>
                                @endforeach
                            </div>
                        </div>
                    @endif
                </div>


                <a class="cursor-pointer" href="{{ $this->buildUrl([], $nextDate) }}"
                   wire:click.prevent="toggleMonth('next')">
                    <button class="month-switcher__arrow">→</button>
                </a>

            </div>

            @php
                $eventDates = collect(array_keys($eventsMap['dates'] ?? []))
                    ->sort()
                    ->values();

                $selectedDate = \Carbon\Carbon::parse($selectedDay);

                $prevDate = $eventDates
                    ->filter(fn ($date) => \Carbon\Carbon::parse($date)->lt($selectedDate))
                    ->last();

                $nextDate = $eventDates
                    ->filter(fn ($date) => \Carbon\Carbon::parse($date)->gt($selectedDate))
                    ->first();

                $currentEventsCount = count($eventsMap['dates'][$selectedDay] ?? []);
            @endphp

            <div class="events-date-nav">
                <button
                    class="events-date-nav__arrow"
                    @if($prevDate)
                        wire:click.prevent="toggleDate('{{ $prevDate }}')"
                    @else
                        disabled
                    @endif
                >
                    ←
                </button>

                <div class="events-date-nav__dates">
                    @if($prevDate)
                        <button
                            class="events-date-nav__date"
                            wire:click.prevent="toggleDate('{{ $prevDate }}')"
                        >
                            <span>{{ \Carbon\Carbon::parse($prevDate)->translatedFormat('d M') }}</span>
                            <small>{{ count($eventsMap['dates'][$prevDate] ?? []) }} подій</small>
                        </button>
                    @else
                        <div></div>
                    @endif

                    <div class="events-date-nav__date is-active">
                        <span>{{ \Carbon\Carbon::parse($selectedDay)->translatedFormat('d M Y') }}</span>
                        <small>{{ $currentEventsCount }} подій</small>
                    </div>

                    @if($nextDate)
                        <button
                            class="events-date-nav__date"
                            wire:click.prevent="toggleDate('{{ $nextDate }}')"
                        >
                            <span>{{ \Carbon\Carbon::parse($nextDate)->translatedFormat('d M') }}</span>
                            <small>{{ count($eventsMap['dates'][$nextDate] ?? []) }} подій</small>
                        </button>
                    @else
                        <div></div>
                    @endif
                </div>

                <button
                    class="events-date-nav__arrow"
                    @if($nextDate)
                        wire:click.prevent="toggleDate('{{ $nextDate }}')"
                    @else
                        disabled
                    @endif
                >
                    →
                </button>
            </div>

            <div class="event-carousel">
                @forelse($eventsMap['dates'][$selectedDay] ?? [] as $ev_id => $event_day)
                    @php($event = $eventsMap['events'][$ev_id])

                    <article
                        class="event-card"
                        style="background-image: url('{{ asset('assets/tango-dark/event-riga.png') }}')"
                    >
                        <div class="bookmark cursor-pointer">
                            ἰ
                            <div class="event_popup">
                                <div class="event_popup__head">
                                    <div class="event_popup__location">
                                        @if(!empty($eventsMap['calendars'][$event['calendar_id']]['city']))
                                            {{ $eventsMap['calendars'][$event['calendar_id']]['city'] }}
                                        @endif
                                        {{ $event['location'] ?? '' }}
                                    </div>

                                    <h3 class="event_popup__title">
                                        {{ $event['summary'] ?? '' }}
                                    </h3>

                                    <div class="event_popup__time">
                                        @if ($event_day['dateStart'] == $event_day['dateEnd'])
                                            {{ $event_day['timeStart'] }} — {{ $event_day['timeEnd'] }}
                                        @else
                                            @include('components.date_formater', ['date' => $event_day['dateStart'], 'format' => 'd'])
                                            —
                                            @include('components.date_formater', ['date' => $event_day['dateEnd'], 'format' => 'd'])
                                        @endif
                                    </div>
                                </div>

                                <div class="event_popup__description">
                                    {!! nl2br($event['description']) !!}

                                    @if(config('app.debug'))
                                        @if(config('app.debug'))
                                            <details class="nested">
                                                <summary>event</summary>
                                                <div class="nested-content text-wrap">@dump($event)</div>
                                            </details>
                                        @endif
                                    @endif
                                </div>

                            </div>
                        </div>
                        <div class="event-card__date">
                            <strong>
                                @if ($event_day['dateStart'] == $event_day['dateEnd'])
                                    {{ $event_day['timeStart'] }} <br> {{ $event_day['timeEnd'] }}
                                @else
                                    @include('components.date_formater', ['date' => $event_day['dateStart'], 'format' => 'd'])
                                    -
                                    @include('components.date_formater', ['date' => $event_day['dateEnd'], 'format' => 'd'])
                                @endif

                            </strong>
                        </div>
                        <div class="event-card__body">
                            <div class="event-card__city">
                                {{ $event['location'] ?? '' }}
                            </div>
                            <h3 class="event-card__title">{{ $event['summary'] ?? '' }}</h3>
                            <div class="event-card__type">
                                        <span>
                                             {{ __('categories.' . $eventsMap['calendars'][$event['calendar_id']]['type_events']) }}

                                        </span>
                            </div>
                            @if(!empty($eventsMap['calendars'][$event['calendar_id']]['city']))
                                <div class="event-card__type">
                                    <span>{{ $eventsMap['calendars'][$event['calendar_id']]['city'] }}</span>
                                </div>
                            @endif

                        </div>
                    </article>
                @empty
                    <div class="events-empty">
                        На цю дату подій немає
                    </div>
                @endforelse
            </div>
        </section>

        <section id="events" class="events-section">
            <div class="section-head">
                <div>
                    <h2>{{ __('site.nearest_events.title') }}</h2>
                    <span></span>
                </div>
                <a >{{ __('site.nearest_events.view_all') }}→</a>
            </div>

            <div class="event-carousel future_events">
                @foreach($events_future as $event)
                    <article class="event-card"
                             style="background-image: url('{{ asset('assets/tango-dark/event-riga.png') }}')">
                        <div class="bookmark cursor-pointer">
                            ἰ
                            <div class="event_popup">
                                <div class="event_popup__head">
                                    <div class="event_popup__location">
                                        {{ $event['location'] ?? '' }}
                                    </div>

                                    <h3 class="event_popup__title">
                                        {{ $event['summary'] ?? '' }}
                                    </h3>

                                    <div class="event_popup__time">
                                        <strong>
                                            @include('components.date_formater', ['date' => $event['dateStart'], 'format' => 'd'])
                                            -
                                            @include('components.date_formater', ['date' => $event['dateEnd'], 'format' => 'd'])
                                        </strong>
                                        <small>
                                            @include('components.date_formater', ['date' => $event['dateEnd'], 'format' => 'M'])
                                        </small>
                                    </div>
                                </div>

                                <div class="event_popup__description">
                                    {!! $event['description'] !!}

                                    @if(config('app.debug'))
                                        @if(config('app.debug'))
                                            <details class="nested">
                                                <summary>event</summary>
                                                <div class="nested-content text-wrap">@dump($event)</div>
                                            </details>
                                        @endif
                                    @endif
                                </div>

                            </div>
                        </div>
                        <div class="event-card__date">
                            <strong>
                                @include('components.date_formater', ['date' => $event['dateStart'], 'format' => 'd'])
                                -
                                @include('components.date_formater', ['date' => $event['dateEnd'], 'format' => 'd'])
                            </strong>
                            <small>
                                @include('components.date_formater', ['date' => $event['dateEnd'], 'format' => 'M'])
                            </small>
                        </div>
                        <div class="event-card__body">
                            <div class="event-card__city">{{ $event['location'] }}</div>
                            <h3 class="event-card__title">{{ $event['name'] }}</h3>
                            <div class="event-card__type">
                                <span>festivals</span>
                            </div>

                        </div>
                    </article>
                @endforeach
            </div>

            <button class="slider-next">→</button>
        </section>

        <section class="promo-grid">
            <article class="map-card"></article>

            <article class="quote-card">
                <small>{{ __('site.inspiration.title') }}</small>
                <h2>{{ __('site.inspiration.dance') }}. {{ __('site.inspiration.travel') }}.<br>{{ __('site.inspiration.inspire') }}.</h2>
                <p>{{ __('site.inspiration.description') }}.</p>
                {{--                <a href="#">Смотреть карту →</a>--}}
            </article>

            <article class="video-card">
                <button>▶</button>
                <h3>{{ __('site.about.title') }}</h3>
            </article>
        </section>
        @if(config('app.debug'))
            <details class="nested">
                <summary>eventsMap</summary>
                <div class="nested-content text-wrap">@dump($eventsMap)</div>
            </details>
            <details class="nested">
                <summary>events_future</summary>
                <div class="nested-content text-wrap">@dump($events_future)</div>
            </details>
            <details class="nested">
                <summary>calendarsSelected</summary>
                <div class="nested-content text-wrap">@dump($calendarsSelected)</div>
            </details>
        @endif
    </main>
    @push('scripts')
        <script>
            document.addEventListener('livewire:init', () => {
                Livewire.on('calendar-url-updated', (event) => {
                    window.history.replaceState({}, '', event.url);
                });
            });
        </script>
    @endpush
</div>
