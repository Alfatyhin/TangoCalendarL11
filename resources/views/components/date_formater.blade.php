@props(['date', 'format'])
@php($date_parse = \Illuminate\Support\Carbon::parse($date))

{{ $date_parse->format($format) }}
