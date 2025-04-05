@extends('layouts.master')

@section('title', $route.': '.$date_str)

@section('head')

    <style>

        .log * {
            border: 2px;
        }
        .log hr {
            border-width: 2px;
            border-top-style: groove;
            border-color: #bb0a0a;
            margin: 10px 0;
        }
    </style>
@stop

@section('content')


    <p>
         @if ($date_pre)
            <a class="button" href="{{ route($route, ['date' => $date_pre->format('Y-m-d')]) }}">{{  $date_pre->format('Y-m-d') }}</a>
        @endif

        <a class="button" href="#end">endt</a>
    </p>



    @if (!empty($log))
        <div class="log">
            {!! $log !!}
        </div>
    @endif

    <a name="end"></a>
@stop
