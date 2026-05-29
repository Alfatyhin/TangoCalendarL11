
{{-- resources/views/tango/dark.blade.php --}}
@extends('layouts.app-dark')

@section('content')
    <style>
        .overlay {
            position: fixed;
            inset: 0;
            z-index: 9999;
            display: block;
            align-items: center;
            justify-content: center;

            background: #000000a8;
            color: #fff;
            font-size: 30px;

        }
    </style>
    <livewire:calendar.events-calendar-page
        :locale="$locale"
        :year="$year"
        :month-number="$month"/>
@endsection
