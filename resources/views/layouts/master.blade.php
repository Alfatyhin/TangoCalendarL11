<?php
/** @var \app\Models\AppCalendar $AppCalendar
 */
$verse = '1.5.2.2';
?>
    <!doctype html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">

    <!-- CSRF Token -->
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>{{ config('app.name', 'Laravel') }} @yield('title')</title>

    <!-- Scripts -->
    <script src="{{ asset('js/jquery-3.5.1.min.js') }}" defer></script>
    <script src="{{ asset('js/coda.js') }}?{{$verse}}" defer></script>
    <script src="{{ asset('js/master.js') }}?{{$verse}}" defer></script>
    <script src="{{ asset('js/fb_sdk.js') }}" defer></script>

    <!-- Fonts -->
    <link rel="dns-prefetch" href="//fonts.gstatic.com">
    <link href="https://fonts.googleapis.com/css?family=Nunito" rel="stylesheet">

    <!-- Styles -->
    <link href="{{ asset('css/preloader.css') }}" rel="stylesheet">
    <link href="{{ asset('css/app.css') }}?{{$verse}}" rel="stylesheet">
    <link href="{{ asset('css/master.css') }}?{{$verse}}" rel="stylesheet">

    <meta name="description" content="@yield('description')">
    <link type="image/x-icon" rel="shortcut icon" href="img/logo.ico">

    <meta property="og:locale" content="{{ str_replace('_', '-', app()->getLocale()) }}">
    <meta property="og:type" content="website">
    <meta property="og:title" content="{{ config('app.name', 'Laravel') }}">
    <meta property='og:description'   content='@yield('description')' >

    <meta property='og:image'   content='/img/logo-socal.png' />
    <meta property='og:image:secure_url'   content='/img/logo-socal.png' />
    <meta property="og:image:width" content="270">

    <meta property="og:url" content="/" >
    <meta property="og:site_name" content="{{ config('app.name', 'Laravel') }}" >
    <meta property="og:updated_time" content="">

    @section('head')

    @show

</head>

<body>

<header>
    @if (Route::has('login'))
        <div class="login">
            @auth
{{--                @if (!empty(Auth::user()->profile_photo_url))--}}
{{--                    <img class="user_logo round"--}}
{{--                         src="https://graph.facebook.com/{{ Auth::user()->fb_id }}/picture?type=normal"/>--}}
{{--                @endif--}}
            @else
                {{-- Login with Facebook --}}

{{--                <a class="btn facebook" href="{{ url('auth/facebook') }}">--}}
{{--                    Enter with Facebook--}}
{{--                </a>--}}

            @endauth
        </div>
    @endif
</header>


<div class="content">

    @section('content')

    @show


</div>
<footer>

    <span>Tango calendar v-{{$verse}}</span>
    <div
        class="fb-like"
        data-share="true"
        data-width="450"
        data-show-faces="false">
    </div>
</footer>

</body>
</html>
