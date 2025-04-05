
<div class="row min-vh-90">
    <div class="col-md-3 select_options">
        <h5>Calendars List</h5>
        <div class="row select_option_item">
            <livewire:select-search
                wire:key="type_events"
                :selectArray="$type_events"
                :option_value="'key'"
                :option_name="'value'"
                :select_name="'select_types'"
                :first_option="'Types Events'"
                :multiple="true"/>
        </div>

        <div class="row select_option_item">
            <livewire:select-search
                wire:key="select_country"
                :selectArray="$countries_list"
                :option_value="'key'"
                :option_name="'value'"
                :search_title="'search country'"
                :select_name="'select_country'"
                :first_option="'Select country'"
                :multiple="true"/>
        </div>
        <div class="row select_option_item">
            <strong>Calendars List</strong>

            <div>
                <ul class="list">
                    @foreach($calendars_selected as $calendar_id)
                        <li>
                            <div class="row">
                                <div class="col">
                                    {{ $calendarsMap[$calendar_id] ? $calendarsMap[$calendar_id]['name'] : $calendar_id}}
                                </div>
                                <div class="col-md-1" >
                                    <span class="fa @isset($calendars_hiddens[$calendar_id]) fa-eye-slash @else fa-eye @endif"
                                          wire:click="calendarHide({{ $calendar_id }})"></span>
                                </div>
                                <div class="col-md-1">
                                      <span wire:click="selectCalendar({{ $calendar_id }})"
                                            class="close">
                                          +
                                      </span>
                                </div>
                            </div>
                        </li>
                    @endforeach
                </ul>
            </div>

            @foreach($calendars_list as $group => $value)

                <strong>
                    {{ str_replace('_', ' ', $group) }}
                </strong>

                <ul>
                    @foreach($value as $item)
                        <li wire:click="selectCalendar({{ $item['id'] }})"
                            class="pointer cal_{{ $item['id'] }}
                           @if(in_array($item['id'], $calendars_selected)) selected @endif"
                            title="{{ $item['description'] }}">
                            {{ $item['name'] }}
                        </li>
                    @endforeach
                </ul>
                @endforeach
                </ul>
        </div>

    </div>
    <div class="col-md-6 calendar_box">
        <div class="text-center">
            <div class="row md-11">
                <div class="col-md-2">
                    <button wire:click="previousMonth" class="col px-3 py-1 bg-gray-300 rounded">←</button>
                </div>
                <div class="col">
                    <strong class="col text-lg font-bold">{{ $monthYear }}</strong>
                    @if($view_mode == 'list')
                        <span class="pointer fa fa-calendar" wire:click="setViewMode"></span>
                    @else
                        <span class="pointer fa fa-list" wire:click="setViewMode"></span>
                    @endif
                </div>
                <div class="col-md-2">
                    <button wire:click="nextMonth" class="col px-3 py-1 bg-gray-300 rounded">→</button>
                </div>
            </div>
            <div class="">

                @if($view_mode == 'calendar')
                    <div class="row">
                        @foreach (['Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat', 'San'] as $day)
                            <div class="col font-bold">{{ $day }}</div>
                        @endforeach
                    </div>
                @endif
                <div class=" events_view_{{ $view_mode }}">
                    @foreach ($weeks as $week)
                        @if($view_mode == 'calendar')
                            <div class="row">
                                @foreach ($week as $day_data)
                                    @php($day = $day_data['date'])
                                    <div class="col calendar_day week_day_{{ $day->dayOfWeek }}
                                @if ($day->format('Y-m-d') == $select_date) select_date @endif
                             @isset($eventsMap['dates'][$day->format('Y-m-d')]) events_isset @endisset
                            {{ $day_data['current'] ? 'current' : '' }}">

                                        {{ $day->day }}

                                        <div class="events_cell" wire:click="selectDate('{{ $day->format('Y-m-d') }}')">
                                            @isset($eventsMap['dates'][$day->format('Y-m-d')])
                                                @foreach($eventsMap['dates'][$day->format('Y-m-d')] as $dk => $item)

                                                @endforeach
                                            @endisset
                                            @isset($eventsMap['dates'][$day->format('Y-m-d')])
                                                @foreach($eventsMap['dates'][$day->format('Y-m-d')] as $calendar_id => $value)
                                                    @foreach($value as $dk => $item)
                                                        <span>
                                                        {{ $eventsMap['events'][$dk]['summary'] }}
                                                    </span>
                                                    @endforeach

                                                @endforeach
                                            @endisset
                                        </div>

                                        <div class="events_list">
                                            @if ($day->format('Y-m-d') == $select_date)
                                                <div class="row h-1">
                                            <span wire:click="selectDate('close')"
                                                  class="close">+</span>
                                                </div>
                                                <p>{{ $day->format('Y-m-d')  }}</p>
                                            @endif
                                            <ul>
                                                @isset($eventsMap['dates'][$day->format('Y-m-d')])
                                                    @foreach($eventsMap['dates'][$day->format('Y-m-d')] as $calendar_id => $value)
                                                        <li class="calendar_head">
                                                            {{ $calendarsMap[$calendar_id]['name'] }}
                                                        </li>
                                                        @foreach($value as $dk => $item)
                                                            <li class="small_name pointer" >
                                                                <div>
                                                            <span wire:click="setOpenEvent('{{ $dk }}')">
                                                                {{ $eventsMap['events'][$dk]['summary'] }}
                                                            </span>
                                                                </div>
                                                            </li>
                                                        @endforeach
                                                    @endforeach
                                                @endisset
                                            </ul>
                                        </div>
                                    </div>
                                @endforeach
                            </div>
                        @else
                            @foreach ($week as $day_data)
                                @php($day = $day_data['date'])
                                @isset($eventsMap['dates'][$day->format('Y-m-d')])

                                    <ul>
                                        <li>
                                            {{ $day->format('Y-m-d') }}
                                        </li>

                                        <li>
                                            <ul>
                                                @isset($eventsMap['dates'][$day->format('Y-m-d')])
                                                    @foreach($eventsMap['dates'][$day->format('Y-m-d')] as $calendar_id => $value)
                                                        <li class="calendar_head">
                                                            {{ $calendarsMap[$calendar_id]['name'] }}
                                                        </li>
                                                        @foreach($value as $dk => $item)
                                                            <li>
                                                                <div class=" pointer"
                                                                     wire:click="setOpenEvent('{{ $dk }}{{$day->day}}')" >
                                                                    <strong>
                                                                        {{ $eventsMap['events'][$dk]['summary'] ?? '' }}
                                                                    </strong>
                                                                    <span>
                                                                         @if ($item['dateStart'] == $item['dateEnd'])
                                                                            from: {{ $item['timeStart'] }}
                                                                            to: {{ $item['timeEnd'] }}
                                                                        @else
                                                                            from: {{ $item['dateStart'] }}
                                                                            to: {{ $item['dateEnd'] }}
                                                                        @endif
                                                                    </span>
                                                                </div>

                                                                <div class="description @if ($openEvent != $dk.$day->day) hidden @endif">
                                                                    <div class="row">
                                                                        <br>
                                                                        <span wire:click="setOpenEvent('')"
                                                                              class="close">+</span>

                                                                    </div>
                                                                    <p>
                                                                        <strong>
                                                                            Location:
                                                                        </strong>
                                                                        {{ $eventsMap['events'][$dk]['location'] ?? '' }}
                                                                    </p>
                                                                    <p>
                                                                        <strong>
                                                                            Date:
                                                                        </strong>
                                                                        @if ($item['dateStart'] == $item['dateEnd'])
                                                                            {{ $item['dateStart'] }}
                                                                            from: {{ $item['timeStart'] }}
                                                                            to: {{ $item['timeEnd'] }}
                                                                        @else
                                                                            from: {{ $item['dateStart'] }} {{ $item['timeStart'] }}
                                                                            to: {{ $item['dateEnd'] }} {{ $item['timeEnd'] }}
                                                                        @endif
                                                                    </p>
                                                                    <p>
                                                                        <strong>
                                                                            Description:
                                                                        </strong>
                                                                    </p>
                                                                    <div class="description">{!!  $eventsMap['events'][$dk]['description'] ?? '' !!}
                                                                    </div>
                                                                </div>
                                                            </li>
                                                        @endforeach
                                                    @endforeach
                                                @endisset
                                            </ul>
                                        </li>
                                    </ul>
                                @endisset
                            @endforeach
                        @endif
                    @endforeach
                </div>
            </div>
        </div>

        @if ($openEvent != '' && $view_mode == 'calendar' && isset($eventsMap['events'][$openEvent]))
            <div class="event_data">
                <div class="row">
                    <br>
                    <span wire:click="setOpenEvent('')"
                          class="close">+</span>

                </div>
                <h4>
                    {{ $eventsMap['events'][$openEvent]['summary'] }}
                </h4>
                <p>
                    <strong>
                        Date:
                    </strong>
                    @php($eventDates = $eventsMap['events'][$openEvent]['date_data'])
                    @if ($eventDates['dateStart'] == $eventDates['dateEnd'])
                        {{ $eventDates['dateStart'] }}
                        from: {{ $eventDates['timeStart'] }}
                        to: {{ $eventDates['timeEnd'] }}
                    @else
                        from: {{ $eventDates['dateStart'] }} {{ $eventDates['timeStart'] }}
                        to: {{ $eventDates['dateEnd'] }} {{ $eventDates['timeEnd'] }}
                    @endif
                </p>
                <p>
                    <strong>
                        Location:
                    </strong>
                    {{ $eventsMap['events'][$openEvent]['location'] }}
                </p>
                <p>
                    <strong>
                        Description:
                    </strong>
                </p>
                <div class="description">{!!  $eventsMap['events'][$openEvent]['description'] !!}
                </div>
            </div>
        @endif
    </div>
    <div class="col-md-3 festivals_list">
        <div class="events_view_list">
            <h4>
                Coming Soon Festivals
            </h4>
            <ul>
                @foreach($festivalsMap as $event_id => $value)

                    @php($eventDates = $value['date_data'])
                    <li class=" {{ $event_id }}">
                        <div class="pointer"
                             wire:click="setOpenEvent('{{ $event_id }}_list')" >
                              <span>
                                  {{ $value['summary'] ?? '' }}
                              </span>
                            <br>
                            <small>
                                {{ $eventDates['dateStart'] }}
                            </small>
                        </div>

                        <div class="description @if ($openEvent == $event_id.'_list') open @endif">
                            <div class="row">
                                <br>
                                <span wire:click="setOpenEvent('')"
                                      class="close">+</span>

                            </div>
                            <p>
                                <strong>
                                    Date:
                                </strong>
                                @if ($eventDates['dateStart'] == $eventDates['dateEnd'])
                                    {{ $eventDates['dateStart'] }}
                                    from: {{ $eventDates['timeStart'] }}
                                    to: {{ $eventDates['timeEnd'] }}
                                @else
                                    from: {{ $eventDates['dateStart'] }} {{ $eventDates['timeStart'] }}
                                    to: {{ $eventDates['dateEnd'] }} {{ $eventDates['timeEnd'] }}
                                @endif
                            </p>
                            <p>
                                <strong>
                                    Location:
                                </strong>
                                {{ $value['location'] ?? '' }}
                            </p>
                            <p>
                                <strong>
                                    Description:
                                </strong>
                            </p>
                            <div class="description">{!! $value['description'] ?? '' !!}
                            </div>
                            <div class="pointer row"
                                 wire:click="setOpenEvent('')" >
                                <div class="col-md-10">

                                </div>
                              <span class="col text-center">

                                  <span class="button">
                                      close
                                  </span>
                              </span>

                            </div>

                        </div>
                    </li>
                @endforeach
            </ul>
        </div>
    </div>
</div>
