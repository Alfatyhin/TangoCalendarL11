<?php
$verse = '0.1';
?>
    <!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>{{ config('app.name', 'Tango Calendar') }}</title>

    <!-- Fonts -->
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=figtree:400,500,600&display=swap" rel="stylesheet" />

    <!-- Scripts -->
    @vite(['resources/css/app.css', 'resources/js/app.js'])

    <!-- Styles -->
    <link href="{{asset("fontawesome-free/css/all.min.css")}}" rel="stylesheet" type="text/css">
    <link href="{{ asset('css/bootstrap.min.css') }}" rel="stylesheet">
    <link href="{{asset("css/tailwind.css")}}" rel="stylesheet">
    <link href="{{ asset('css/calendars_page.css') }}?{{$verse}}" rel="stylesheet">
    <link href="{{ asset('css/components.css') }}?{{$verse}}" rel="stylesheet">
    @livewireStyles
</head>
<body class="min-h-screen flex flex-col font-sans antialiased">

<div class="bg-gray-100">
    <div class="row">
        <div class="col-md-3">
            <h5>User menu</h5>
            <ul>
                <li>
                    <a >Import Calendars</a>
                </li>
            </ul>
        </div>
        <div class="col">
            <header class="bg-white shadow">
                <div class="max-w-7xl mx-auto py-6 px-4 sm:px-6 lg:px-8">
                    {{ $header ?? 'Dashboard' }}
                </div>
            </header>
            <!-- Page Content -->
            <main>
                @yield('content')
            </main>
        </div>
    </div>
</div>

@stack('modals')

@livewireScripts

<footer class="mt-auto bg-gray-200 py-1">
    <div class="text-center">
        <span>Tango calendar v-{{$verse}}</span>
        <span>&copy;<a target="_blank" href="https://it-alex.net.ua/">it-alex.net.ua</a> </span>
    </div>
</footer>
</body>

</html>
