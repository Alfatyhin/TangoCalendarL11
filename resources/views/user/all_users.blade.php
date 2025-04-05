@extends('layouts.master')

@section('title', 'dashboard')

@section('description', 'users')

@section('head')
    @parent

    <link href="{{ asset('css/dashboard.css') }}" rel="stylesheet">

@stop

@section('content')


    <h1>
        Users
    </h1>

    <section class="sidebar_left">
        <h3>admin panel</h3>
    </section>

    <section class="right">
        @if(!empty($users))
            <table>
                <tr>
                    <th>
                        id
                    </th>
                    <th>
                        name
                    </th>
                    <th>
                        role
                    </th>
                    <th>
                        email
                    </th>
                    <th>
                        data
                    </th>
                    <th>
                        reg date
                    </th>
                </tr>

                @foreach($users as $user)
                    <tr>
                        <td>
                            {{ $user->id }}
                        </td>
                        <td>
                            {{ $user->name }}
                        </td>
                        <td>
                            {{ $user->email }}
                        </td>
                        <td>
                            {{ $user->role }}
                        </td>
                        <td>
                            {{ $user->data }}
                        </td>
                        <td>
                            @php
                                $date = $user->created_at;
                            @endphp
                            {{ $date }}
                        </td>
                    </tr>

                @endforeach

            </table>
        @endif
    </section>

@stop
