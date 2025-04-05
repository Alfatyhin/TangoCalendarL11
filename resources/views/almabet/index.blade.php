@extends('almabet.layouts.master')

@section('title', 'config redact')

@section('head')


    <style>
        table {
            width: 90%;
            margin: auto;
            color: #000000;
        }
        th, td {
            border: 1px solid #9b9b9b;
            padding: 3px;
        }

        td div {
            font-size: 60%;
            max-width: 400px;
            word-break: break-word;
        }

    </style>

@stop

@section('content')



    <div>

        <table>
            <caption> Postbacks </caption>
            <tr>
                <th>
                    id
                </th>
                <th>
                    pid
                </th>
                <th>
                    btag
                </th>
                <th>
                    btag
                </th>
                <th>
                    psId
                </th>
                <th>
                    reg count
                </th>
                <th>
                    dep count
                </th>
                <th>
                    dep summ
                </th>
                <th>
                    last postback
                </th>
            </tr>

            @foreach($postbacks_count as $item)
                <tr>
                    <td>
                        {{ $item->id }}
                    </td>
                    <td>
                        {{ $item->pid }}
                    </td>
                    <td>
                        {{ $item->pid }}
                    </td>
                    <td>
                        {{$item->btag}}
                    </td>
                    <td>
                        {{$item->ps_id}}
                    </td>
                    <td>
                        {{$item->reg_count}}
                    </td>
                    <td>
                        {{$item->dep_count}}
                    </td>
                    <td>
                        {{$item->dep_summ}}
                    </td>
                    <td>
                        <div>
                            {{$item->last_postback}}
                        </div>
                    </td>
                </tr>
            @endforeach
        </table>


        <br>


    </div>


@stop
