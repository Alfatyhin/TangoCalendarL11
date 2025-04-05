@extends('layouts.master')

@section('title', 'dashboard')

@section('description', 'dashboard')

@section('head')
    @parent


    <link href="{{ asset('css/dashboard.css') }}" rel="stylesheet">

    <script src="{{ asset('js/dashboard.js') }}" defer></script>

    <script>

        var calendarList = @json($calendarList);
        var calendarCollection = @json($calendarCollection);
    </script>
@stop

@section('content')


    <h1>
        Dashboard
    </h1>

    <section class="sidebar_left">
        <div>
            <img class=""
                 src="https://graph.facebook.com/{{ $user->fb_id }}/picture?type=normal"/>
            <p>{{ $user->name }}</p>
            <p>{{ $user->email }}</p>
            <p>{{ $user->role }}</p>
        </div>

        @if ($calendars)

        @else

        @endif

        @if ($user_fb_calendar)

            <label>
                @if ($fb_events)
                    <input type="checkbox" class="user_fb_calendar">
                @endif

                <span>
               my fb calendar ({{ $fb_events_count }} events)
           </span>
            </label>


        @else
            <div class="form">
                <p>add faceboock calendar </p>
                <form method="post" action="{{ route('add_user_calendar') }}" >
                    @csrf

                    <input type="text" name="fb_cal_link" />
                    <input type="submit">
                </form>
            </div>
        @endif

    </section>

    <section class="right">
        <div class="fb_event_list">
            @if (!empty($fb_events))
                <h2> FB events </h2>

                @foreach($fb_events as $event)

                    <div class="box">
                        <div class="event">
                            <p>
                            <span class="event_name">
                                {{ $event['SUMMARY'] }}
                            </span>
                                <span class="context_calendar" data-type="facebook" data-uid="{{ $event['UID'] }}">...</span>
                            </p>
                            <p>
                                location:
                                <span class="event_location">
                                {{ $event['LOCATION'] }}
                            </span>
                            </p>
                            @if ($event['date_start'] == $event['date_end'])
                                <p class="event_dates">
                                    Date:
                                    <span>
                                    {{ $event['date_start'] }}
                                ( {{ $event['time_start'] }} - {{ $event['time_end'] }} )
                                </span>
                                </p>
                            @else
                                <p class="event_dates">
                                    Dates:
                                    <span>
                                    {{ $event['date_start'] }} - {{ $event['date_end'] }}
                                </span>
                                </p>
                            @endif
                            <p class="event_link">
                                <a class="button" href="{{ $event['URL'] }}" target="_blank">
                                    fb event
                                </a>
{{--                                <span class="button descript_show">description</span>--}}
                            </p>

                        </div>

                        <div class="pre description ">
                            {!! $event['DESCRIPTION'] !!}
                        </div>
                    </div>

                @endforeach
            @endif
        </div>

        <div class="add_to_calendar_form hidden">
            <span class="close pointer"></span>
            <h3>add event to calendar</h3>
            <form action="" method="post">
                @csrf
                <input type="hidden" name="type_calendar">
                <input type="hidden" name="uid_event">

                <select name="import_calendar" size="1" >
                    <option >select calendar</option>
                    @foreach($calendarList['festivals'] as $items)

                        <optgroup label="festivals" >festivals</optgroup>

                        @foreach($items as $id)
                            <option value="{{ $id }}">
                                {{ $calendarCollection[$id]->getName() }}
                            </option>
                        @endforeach
                    @endforeach

                    @foreach($calendarList['master_classes'] as $items)

                        <optgroup label="master classes" >master_classes</optgroup>

                        @foreach($items as $id)
                            <option value="{{ $id }}">
                                {{ $calendarCollection[$id]->getName() }}
                            </option>
                        @endforeach
                    @endforeach

                    <optgroup label="milongas" >milongas</optgroup>
                    @foreach($calendarList['milongas'] as $items)
                        @foreach($items as $id)
                            <option value="{{ $id }}">
                                {{ $calendarCollection[$id]->getName() }}
                            </option>
                        @endforeach
                    @endforeach

                </select>
                <br>
                <input type="submit" value="add to calendar">
            </form>
        </div>
    </section>


@stop
