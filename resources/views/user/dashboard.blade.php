@extends('layouts.master')

@section('title', 'dashboard')

@section('description', 'dashboard')

@section('head')
    @parent

    <link href="{{ asset('css/dashboard.css') }}" rel="stylesheet">

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



        @if ($user_calendars)

        @endif

    </section>

@stop
