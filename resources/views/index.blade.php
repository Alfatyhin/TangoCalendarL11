@extends('layouts.master')

@section('title', 'home')
@section('description', 'Танго календар, Танго фестивалі в Україні,
  мілонги у Києві та інших містах України, танго семінари, розклади танго шкіл.')

@section('head')
@parent
<script>
    var yearCalendar = {{$yearCalendar}};
    var monthCalendar = {{$monthCalendar}};
    var messagesLog = @json($messagesLog);
    var DataEvents = @json($DataEvents);
    var WorldFest = @json($worldFest);

</script>
@stop

@section('content')

    <div class="holder preloader_holder">
        <div class="preloader"><div></div><div></div><div></div><div></div><div></div><div></div><div></div><div></div><div></div><div></div></div>
    </div>

<h1>
    Tango Calendar
</h1>

<div class="calendar_list">
    <span class="caption">Calendars List</span>
    <span class="mobail menu-close"></span>
    <span class="mobail menu-open"></span>
    <ul class="calendars">
        <li class="first open festival_list">
                    <span>Festivals
                        <span class="count"></span>
                    </span>

            <ul class="sub-menu">
                @foreach($calendarList['festivals'] as $items)
                    @foreach($items as $id)
                        <li class="{{$calendarsCollection[$id]->getClass()}}">
                            <label>
                                <input class="calendar_id"
                                       type="checkbox" name="event_types"
                                       value="{{$id}}"
                                    {{$calendarsCollection[$id]->getSelect()}}
                                />
                                <span>{{$calendarsCollection[$id]->getName()}}
                                            <span class="count"></span>
                                        </span>

                            </label>
                            <div class="info">
                                <div class="description">
                                    {{$calendarsCollection[$id]->getDescription()}}
                                </div>
                            </div>
                        </li>
                    @endforeach
                @endforeach
                @foreach($calendarList['master_classes'] as $items)
                    @foreach($items as $id)
                        <li class="{{$calendarsCollection[$id]->getClass()}}">
                            <label>
                                <input class="calendar_id"
                                       type="checkbox" name="event_types"
                                       value="{{$id}}"
                                    {{$calendarsCollection[$id]->getSelect()}}
                                />
                                <span>{{$calendarsCollection[$id]->getName()}}
                                                <span class="count"></span>
                                            </span>


                            </label>

                            <div class="info">
                                <div class="description">
                                    {{$calendarsCollection[$id]->getDescription()}}
                                </div>
                            </div>
                        </li>
                    @endforeach
                @endforeach
            </ul>

        </li>
        <li class="first milongas_list">
                    <span>Милонги у місті
                        <span class="count"></span>
                    </span>
            <ul class="sub-menu">
                @foreach($calendarList['milongas'] as $city=>$items)

                    @foreach($items as $id)
                        <li class="{{$calendarsCollection[$id]->getClass()}}">
                            <label>
                                <input class="calendar_id"
                                       type="checkbox" name="event_types"
                                       value="{{$id}}"
                                    {{$calendarsCollection[$id]->getSelect()}}
                                />
                                <span>{{$calendarsCollection[$id]->getName()}}
                                        <span class="count"></span>
                                        </span>


                            </label>

                            <div class="info">
                                <div class="description">
                                    {{$calendarsCollection[$id]->getDescription()}}
                                </div>
                            </div>
                        </li>
                    @endforeach

                @endforeach
            </ul>
        </li>
        <li class="first school_list">
                    <span>Танго клуби
                        <span class="count"></span>
                    </span>

            <ul class="sub-menu">
                @foreach($calendarList['tango_school'] as $city=>$items)
                    <li>
                        <span>{{$city}}</span>
                        <ul>
                            @foreach($items as $id)
                                <li class="{{$calendarsCollection[$id]->getClass()}}">
                                    <label>
                                        <input class="calendar_id"
                                               type="checkbox" name="event_types"
                                               value="{{$id}}"
                                            {{$calendarsCollection[$id]->getSelect()}}
                                        />
                                        <span>{{$calendarsCollection[$id]->getName()}}
                                                    <span class="count"></span>
                                                </span>


                                    </label>

                                    <div class="info">
                                        <div class="description">
                                            {{$calendarsCollection[$id]->getDescription()}}
                                        </div>
                                    </div>
                                </li>
                            @endforeach
                        </ul>
                    </li>
                @endforeach
            </ul>
        </li>
    </ul>

</div>

<div class="calendar" id="calendar">
    <table  rules='none' style=' '>
        <thead>
        <tr>
            <th class="caption" colspan="7">
                танго календар: <span class="year"></span> рік
                <span class="view_mode">вигляд <span class="icon"></span></span>
            </th>
        </tr>
        <tr>
            <th class="header_table" colspan="2">
                <div class='button_cal right' data="minus"> < </div>
            </th>
            <th class="header_table" colspan="3">
                <span class="month"></span>
            </th>
            <th class="header_table" colspan="2">
                <div class='button_cal left' data="plus"> > </div>
            </th>
        </tr>
        </thead>
        <tbody>

        </tbody>
    </table>
</div>
<div class="world_events">
    @if ($worldFest)

        <h3> анонс найближчих фестивалів у світі </h3>

    @endif

</div>

<div class="clear"></div>





<form id="calendar_set"  action="{{route('get.events')}}" method="get">
    <input class="calendar_id_send" type="hidden" name="calendar_id" value="" />
    <input class="set_date" type="hidden" name="set_date" value="{{$yearCalendar}}-{{$monthCalendar+1}}-1" />
</form>


@stop
