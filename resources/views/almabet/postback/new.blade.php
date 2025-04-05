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

        <div class="hidden">
            <input class="phone_number" id="phone1" type="tel"  placeholder="" >
        </div>


        <div class="config form_master">

            <form class="" method="post" action="">

                @csrf

                <p>
                    Name:
                    <input type="text" name="name" value="" >
                </p>


                <p>
                    registration:
                    <textarea name="reg"></textarea>
                </p>
                <p>
                    deposit:
                    <textarea name="dep"></textarea>
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
