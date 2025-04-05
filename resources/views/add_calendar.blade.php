
<html>
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">

    <title>Tango Calendar</title>
</head>

<body>
<h1>
    Tango Calendar
</h1>
<div class="content">
    @if (Route::has('login'))
        <div class="hidden fixed top-0 right-0 px-6 py-4 sm:block">
            @auth
                <a href="{{ url('/') }}" class="text-sm text-gray-700 underline">Home</a>
            @else
                <a href="{{ route('login') }}" class="text-sm text-gray-700 underline">Log in</a>

                @if (Route::has('register'))
                    <a href="{{ route('register') }}" class="ml-4 text-sm text-gray-700 underline">Register</a>
                @endif
            @endauth
        </div>
    @endif

    <h2> Добавить календарь </h2>

        @if (!empty($message))
            <p class="message">{{$message}}</p>
        @endif

        <form enctype="multipart/form-data" action="{{route('calendar.save')}}" method="post">

            <div class="table table-bordered">
                @csrf

                <p>
                    <span>gcalendar Id: </span> <br />
                    <input type="text" name="gcalendarId" value="" />

                @if($errors->has('gcalendarId'))

                    <div class="alert alert-danger">
                        {{$errors->first('gcalendarId')}}
                    </div>

                    @endif

                    </p>


                    <p>
                        <span>category:</span> <br>
                        <select name="type_events" >
                            @foreach($category as $item)
                                @if ($item == $select_type)
                                    <option value="{{$item}}" selected>{{$item}}</option>
                                @else
                                    <option value="{{$item}}" >{{$item}}</option>
                                @endif
                            @endforeach
                        </select>

                    @if ($errors->has('type_events'))
                        <div class="alert alert-danger">
                            {{$errors->first('type_events')}}
                        </div>
                        @endif
                        </p>

                        <p>
                            <span>country: </span> <br />
                            <input type="text" name="country" value="Ukraine" />

                        @if ($errors->has('country'))
                            <div class="alert alert-danger">
                                {{$errors->first('country')}}
                            </div>
                            @endif
                            </p>

                        <p>
                            <span>city: </span> <br />
                            <input type="text" name="city" value="" />

                        @if ($errors->has('city'))
                            <div class="alert alert-danger">
                                {{$errors->first('city')}}
                            </div>
                            @endif
                            </p>

                    <p>
                        <span>source:</span> <br>
                        <select name="source" >
                            @foreach($sources as $item)
                                @if ($item == $select_source)
                                    <option value="{{$item}}" selected>{{$item}}</option>
                                @else
                                    <option value="{{$item}}" >{{$item}}</option>
                                @endif
                            @endforeach
                        </select>
                        @if ($errors->has('source'))
                            <div class="alert alert-danger">
                                {{$errors->first('source')}}
                            </div>
                        @endif
                    </p>



            </div>
            <input type="submit" name="save" value="сохранить">
        </form>

</div>
</body>
</html>
