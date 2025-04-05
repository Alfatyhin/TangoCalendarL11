@extends('almabet.layouts.master')

@section('title', 'config redact')

@section('head')


    <style>
        textarea {
            display: block;
            width: 400px;
            height: 200px;
            margin-left: 40px;
        }
    </style>

@stop

@section('content')


    <div>
        <div class="switch_box">
            <span class="switch_class active" data_class="config">Config</span>
        </div>




        <div class="config form_master">

            <form class="" method="post" action="{{ route('postback_edit', ['postback' => $postback]) }}">

                @csrf

                <input type="hidden" name="id" value="{{ $postback->id }}">
                <p>
                    #{{ $postback->id }} Name:
                    <input type="text" name="name" value="{{ $postback->name }}" >
                </p>


                <p>
                    registration:
                    <textarea name="reg">{{ $postback->reg }}</textarea>
                </p>
                <p>
                    deposit:
                    <textarea name="dep">{{ $postback->dep }}</textarea>
                </p>
                <p>
                    <input class="btn button" type="submit" name="save" value="save"/>
                </p>

                <div class="info_params">

                </div>
            </form>

        </div>


        <br>


    </div>


@stop
