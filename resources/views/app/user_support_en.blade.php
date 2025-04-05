@extends('layouts.base')

@section('title', 'Users Support')
@section('description', 'User Support')

@section('head')
    @parent

@stop

@section('content')


    <div class="">




        <h1>
            <strong><font style="vertical-align: inherit;">
                    <font style="vertical-align: inherit;"><font style="vertical-align: inherit;"><font style="vertical-align: inherit;">Users Support Tango Calendar app</font></font></font>
                </font>
            </strong>
        </h1>

        <ul>
            <li>
                <a href="/">
                    Home
                </a>
            </li>
            <li>
                <a href="#description">
                    Application Description
                </a>
            </li>
            <li>
                <a href="#plans">
                    Development plans
                </a>
            </li>
            <li>
                <a href="#contacts">
                    Support Contacts
                </a>
            </li>
        </ul>
{{--        <p>--}}
{{--            <a href="{{ route('app_users_support', ['lang' => 'ua']) }}">--}}
{{--                українською тут--}}
{{--            </a>--}}
{{--        </p>--}}


        <h2><a id="description"></a>
            Application Description
        </h2>

        <p>
            Watch tango events around the world.
            Share tango events with others.
        </p>

        <p>
            <strong>
                Browse tango event calendars by category.
            </strong>

        </p>
        <pre>
        • Festivals around the world
        • Festivals in the country
        • Festival schedule
        • Master classes in the country
        • Milongas in the city
        • Practices in the city
        • Schedule of tango school.
        </pre>

        <p>

            You can set up a geo filter on the calendar selection page to see only the calendars you need.
            <br />
            A quick filter for displaying events from selected calendars on the main page.
            <br />
            Resizable calendar.
            <br />

            View the location of the event on the map. Go to the event page. View detailed information about an event.

            <br />

            The ability to easily add events from your Facebook calendar to other calendars.
            Creation and editing of regular and single events.
            Create calendars
            <br />
            <strong>
                *for organizers.
            </strong>
            <br />
            After registering in the application, you will be able to see more events.
            <br />

            In order to be able to add events, you need to specify the role of the organizer during registration.
            *You must provide a link to your Facebook profile.
            <br />

            Once your host application is approved, you will be able to create events and calendars if the type of calendar you need is not available.
            Edit events. (only the ones you created)
            <br />
            Import events from your Facebook calendar.
            <br />
            <strong>
                **To import events, you need to copy the link to the calendar from the desktop version of the Facebook interface.
            </strong>

            <br />
            If you have your own Google calendar with a school schedule, you can also add it to the list of available calendars.

            <br />
            Also, if you are not an organizer but want to share events from Facebook, you can specify the role of a volunteer. And after the approval of the application, you will be able to share events.

        </p>

        <h2><a id="plans"></a>
            Development plans.
        </h2>

        <pre>
            • Subscribe to notifications about a new event in the calendar.
            • Subscribe to change events in the calendar.
            • Subscribe to add a new calendar.
            • Event reminder.
            • Mark the event as interested or going.
            • Adding feedback about the event.
            • Possibility to apply for participation from the application.
            • Constructor of the application form for participation for the organizers.
            • Possibility of private correspondence within the application.
            • Registration for individual lessons through applications to teachers.
            • Thematic bulletin boards. Search for a companion or company to stay during the festival. Search for a partner for registration.

        </pre>
        <h2><a id="contacts"></a>
            Support Contacts.
        </h2>
        <p>
            If you have any questions or if you don't understand something. Email us <a href="mailto:virikidorhom@gmail.com">virikidorhom@gmail.com</a>
        </p>
        </div>

@stop
