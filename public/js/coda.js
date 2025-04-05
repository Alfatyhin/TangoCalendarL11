$(function () {

    if (messagesLog) {
        for (key in messagesLog) {
            var message = messagesLog[key];
            console.log(message);
        }
    }

    setCalendarsSelected();

    var jmon=['січень', 'лютий', 'березень', 'квітень', 'травень', 'червень', 'липень', 'серпень', 'вересень', 'жовтень', 'листопад', 'грудень'];
    var jdn=['нд', 'пн', 'вт', 'ср', 'чт', 'пт', 'сб'];

    function CalendarOut(year, mon) {


        // текущая дата
        var adate=new Date();
        // текущий год
        var ayear=adate.getFullYear();
        // текущий месяц
        var amon=adate.getMonth();
        //текущий день
        var adey=adate.getDate();

        //следующий год и месяц
        var nextd = new Date(year, mon, 31);
        nextd.setDate(nextd.getDate() + 1);
        var nexty=nextd.getFullYear();
        var nextm=nextd.getMonth();


        //предыдущий год и месяц
        var ford = new Date(year, mon, 1);
        ford.setDate(ford.getDate() - 1);
        var fory=ford.getFullYear();
        var form=ford.getMonth();


        // получаем день недели начала месяца
        var ndate = new Date(year, mon, 1);

        // текущий год календаря
        var tyear=ndate.getFullYear();
        // текущий месяц календаря
        var tmon=ndate.getMonth();


        // получаем номер дня недели
        var fdn=ndate.getDay();
        // вычисляем с какой даты начинается неделя
        if (fdn==0)
            var xdey=6;
        else
            var xdey=fdn - 1;



        //получаем новую дату начала отсчета цикла календаря
        ndate.setDate(ndate.getDate() - xdey);
        // начальное значение месяца календаря
        var smon=ndate.getMonth();
        // начальный год календаря
        var syear=ndate.getFullYear();



        var txt=`<tr>`;

        for (x=0; x<=6; x++)
        {
            if (x==6)
                var xn=0;
            else
                xn=x + 1;
            var dn=jdn[xn];
            txt=`${txt} <th>${dn}</th>`;
        }
        txt=`${txt} </tr>`;

        var i=0;
        var nmon=ndate.getMonth();
        while (i<=6)
        {
            x=0;
            txt=`${txt} <tr>`;
            while (x<=6)
            {
                if (nmon == tmon)
                    cl='activmon';
                else
                    cl='unactivmon';

                var ndm=ndate.getDate();

                if (tyear==ayear && tmon==amon && ndm==adey && cl == 'activmon')
                    cl= cl + ' activdey';

                var xtmon = tmon + 1;

                txt=`${txt} <td class='${cl} date_${tyear}-${xtmon}-${ndm}' ><div>
			${ndm}
			</div></td>`;

                ndate.setDate(ndate.getDate() +1);
                var nmon=ndate.getMonth();
                x++;
            }
            if (nmon != tmon && nmon != smon)
                i=6;
            txt=`${txt} </tr>`;
            i++;
        }

        // получаем имя месяца
        $('#calendar .year').text(year);
        $('#calendar .month').text(jmon[tmon]);
        $('#calendar table tbody').html(txt);

        appendToCalendar(year, mon);


        $('.calendar .description-view').click(function () {
            $(this).parents('ul.sub_menu').toggleClass('content');
            $(this).parents('ul.event_list').toggleClass('pop-app');
            $(this).parents('ul.event_list').toggle('500');
            event.stopPropagation();
        });
        $('.pop-app-close').click(function () {
            $(this).parents('ul.sub_menu').toggleClass('content');
            $(this).parents('ul.event_list').toggleClass('pop-app');
            $(this).parents('ul.event_list').toggle('500');
            event.stopPropagation();
        });
        $('.event_list .menu-close').click(function () {
            $(this).parents('ul').first().hide('500');
            event.stopPropagation();
        });
        $('.event_list > li').click(function () {
            $(this).children('ul.sub_menu').show('500');
            event.stopPropagation();
        });

    }

    function appendToCalendar(year, month) {

        $('ul.event_list').remove();
        $('.date_has_event div.event').removeClass('event');
        $('.date_has_event').removeClass('date_has_event');

        var selectedId = [];

        $("input.calendar_id:checkbox:checked").each(function () {
            var id = $(this).val();
            selectedId.push(id);

            Setobj('calendars-selected', selectedId);
        });

        $("input.calendar_id:checkbox:checked").each(function () {
            var id = $(this).val();

            if (!!DataEvents[id] && !!DataEvents[id][year] && !!DataEvents[id][year][month]) {

                if (!!DataEvents[id][year][month]) {

                    var listEvents = DataEvents[id][year][month];

                    var count = Object.keys(listEvents).length;


                    $(this).parent().children('span').children('.count').html('(<span>' + count + '</span>)');

                    for (key in listEvents) {

                        if($('.date_' + key).hasClass('date_has_event')) {

                        } else {
                            appendDate(key);
                        }

                        var event = listEvents[key];

                        if (event['dateStart'] != event['dateEnd']) {

                            var eventDateStart = new Date(event['dateStart']);
                            var eventdateEnd = new Date(event['dateEnd']);

                            while (eventDateStart < eventdateEnd) {
                                eventDateStart.setDate(eventDateStart.getDate() + 1);
                                var eventYear = eventDateStart.getFullYear();
                                var eventMonth = eventDateStart.getMonth() + 1;
                                var eventDey = eventDateStart.getDate();
                                var date = eventYear + '-' + eventMonth + '-' + eventDey;

                                appendDate(date);
                                appendEvent(date, event);
                            }


                        }

                        appendEvent(event['dateStart'], event);

                    }

                }

            } else {
                $('.preloader_holder').addClass('holder');
                $('.preloader_holder .preloader_dis').addClass('preloader');

                console.log('нет данных по календарю id ' + id);

                selectedId = selectedId.join('|');
                $('.calendar_id_send').val(selectedId);
                $("#calendar_set").submit();
            }



        });


        $('.date_has_event').click(function () {
            $('.events ul.event_list', this).show('500');
            event.stopPropagation();
        });


        countFirst();
    }

    function countFirst () {

        $('.calendar_list .first').each(function () {

            var count = 0;
            $('input.calendar_id:checkbox:checked', this).each(function () {
                var countx = $(this).parent().children('span').children('.count').children('span').html();
                if (countx) {
                    count = countx / 1 + count;
                }

            });

            if (count > 0 ) {
                $(this).children('span').children('.count').html('(' + count + ')');
                $(this).children('span').addClass('hash_events');
            } else {
                $(this).children('span').removeClass('hash_events');
            }

        });


    }




    function appendDate(date) {
        $('.activmon.date_' + date).addClass('date_has_event');
        $('.date_' + date + ' div').addClass('events');
        if($('.date_' + date + ' ul').hasClass('event_list')) {

        } else {
            $('.date_' + date + ' div').append('<ul class="event_list"><li class="mobail"><span class="menu-close"></span></li></ul>');
        }


    }

    function appendEvent(date, event) {
        var description = event['description'];
        description = linkify(description);

        var dateStart = formatDate(event['dateStart']);
        var dateEnd = formatDate(event['dateEnd']);

        if (dateStart == dateEnd) {
            var dateText = `<span class="date-start">${dateStart}</span>
                <sapan class="time-start">начало в ${event['timeStart']}</sapan>
                <span class="time-end"> окончание в ${event['timeEnd']}</span>`;
            var classDate = 'one-event';
        } else {
            var dateText = `<span class="date-start">с ${dateStart}</span>
                <sapan class="time-start">начало в ${event['timeStart']}</sapan>
                <span class="date-end">по ${dateEnd}</span>
                <span class="time-end"> окончание в ${event['timeEnd']}</span>`;
            var classDate = 'many-event';
        }

        var locationLink = getMapLink(event['location']);

        var eventDate = `<li>
<span class="title">${event['name']}</span>
    <ul class="sub_menu">
        <li class="mobail">
        <span class="menu-close"></span>
        </li>
        <li class="title">
        <span class="title">${event['name']}</span>
        <span class="pop-app-close"></span>
        </li>
        <li class="date ${classDate}"> Date: <br>
            <span>
               ${dateText}
            </span>
        </li>
        <li class="location"> Location: <br>
            <span>${event['location']}</span>
            <span>${locationLink}</span>
        </li>
        <li class="description"> Description: <br>
            <span>${description}</span>
            <span class="description-view"></span>
        </li>

    </ul>
</li>`;



        var viewMode = Getobj('view-mode')

        if (viewMode == 'calendar') {
            $('.date_' + date + ' .events ul.event_list').append(eventDate);
        } else {
            var txt = `<tr><td class="event_list" colspan="7"><ul class="list_event_list">${eventDate}</ul></td></tr>`;
            $('#calendar table tbody').append(txt);
        }

    }



    $('.calendar_list .menu-close').click(function () {
        $('.calendar_list ul.calendars').hide('500');
        $('.calendar_list .menu-close').hide('500');
        $('.calendar_list .menu-open').show('500');
    });
    $('.calendar_list .menu-open').click(function () {
        $('.calendar_list ul.calendars').show('500');
        $('.calendar_list .menu-close').show('500');
        $('.calendar_list .menu-open').hide('500');
        $('.info .description').hide('500');
    });

    $('.info').click(function () {
        $(this).children('.description').toggle('500');
    });

    $('.calendars .first > span').click(function () {
        var el = $(this).parent();
        $(this).parent().toggleClass('open');
    });




    // аякс обработка данных пока не работает
    function getCalendarEvents(calendarId) {

        var settings = {
            "url": "api/getevents",
            "method": "POST",
            "timeout": 0,
            "dataType": 'json',
            "data": {
                "calendarId" : calendarId,
            }
        };

        $.ajax({
            url: '/api/getevents',
            method: 'POST',
            dataType: 'json',
            "data": {
                "Items" : 'test',
            },
            success: function(data){
                console.log(data);
            }
        });

    }

    function listOut(year, month) {

        // получаем имя месяца
        $('#calendar .year').text(year);
        $('#calendar .month').text(jmon[month]);
        $('#calendar table tbody').html('');

        var selectedId = [];

        $("input.calendar_id:checkbox:checked").each(function () {
            var id = $(this).val();
            selectedId.push(id);

            Setobj('calendars-selected', selectedId);
        });


        $("input.calendar_id:checkbox:checked").each(function () {
            var id = $(this).val();

            if (!!DataEvents[id] && !!DataEvents[id][year] && !!DataEvents[id][year][month]) {

                if (!!DataEvents[id][year][month]) {

                    var listEvents = DataEvents[id][year][month];

                    var count = 0;

                    for (key in listEvents) {


                        var event = listEvents[key];

                        var ToDeyDate = new Date();
                        var eventdateEnd = new Date(event['dateEnd']);

                        if (eventdateEnd >= ToDeyDate) {
                            count++;
                            appendEvent(event['dateStart'], event);
                        } else {

                        }
                        $(this).parent().children('span').children('.count').html('(<span>' + count + '</span>)');

                    }

                }

            } else {
                $('.preloader_holder').addClass('holder');
                $('.preloader_holder .preloader_dis').addClass('preloader');

                console.log('нет данных по календарю id ' + id);

                selectedId = selectedId.join('|');
                $('.calendar_id_send').val(selectedId);
                $("#calendar_set").submit();
            }

            $('.calendar .description-view').click(function () {
                $(this).parents('ul.sub_menu').addClass('content');
                $(this).parents('ul.list_event_list').addClass('pop-app');
                $(this).parents('ul.event_list').show('500');
            });
            // $('.pop-app-close').click(function () {
            //     $(this).parents('ul.sub_menu').toggleClass('content');
            //     $(this).parents('ul.list_event_list').toggleClass('pop-app');
            //     $(this).parents('ul.event_list').toggle('500');
            //     event.stopPropagation();
            // });
            $('.list_event_list .menu-close').click(function () {
                $(this).parents('ul.sub_menu').removeClass('content');
                $(this).parents('ul.list_event_list').removeClass('pop-app');
                $(this).parents('ul.event_list').hide('500');
            });

        });
        countFirst();
    }


    $('.preloader_holder').removeClass('holder');
    $('.preloader_holder .preloader').addClass('preloader_dis');
    $('.preloader_holder .preloader').removeClass('preloader');




    $('#calendar .view_mode').click(function () {
        var viewMode = Getobj('view-mode');
        if (viewMode == 'calendar') {
            var viewMode = 'list';
        } else {
            var viewMode = 'calendar';
        }
        Setobj('view-mode', viewMode);
        eventStart(yearCalendar, monthCalendar);
    });

    function eventStart(yearCalendar, monthCalendar) {
        var viewMode = Getobj('view-mode');
        if (!viewMode) {
            var viewMode = 'list';
            Setobj('view-mode', viewMode);
        } else {
        }

        if (viewMode == 'calendar') {
            $('#calendar .caption .view_mode .icon').html('&minusb;');
            CalendarOut(yearCalendar, monthCalendar);
        } else {
            $('#calendar .caption .view_mode .icon').html('&plusb;');
            listOut(yearCalendar, monthCalendar);
        }
    }


    $('.calendar_list input.calendar_id').change(function () {
        eventStart(yearCalendar, monthCalendar);
    })

    $('#calendar table .header_table .button_cal').click(function () {
        var data = $(this).attr('data');

        if (data == 'minus') {
            //предыдущий год и месяц
            var date = new Date(yearCalendar, monthCalendar, 1);
            date.setDate(date.getDate() - 1);
            yearCalendar = date.getFullYear();
            monthCalendar = date.getMonth();
        } else {
            //следующий год и месяц
            var date = new Date(yearCalendar, monthCalendar, 31);
            date.setDate(date.getDate() + 1);
            yearCalendar = date.getFullYear();
            monthCalendar = date.getMonth();
        }
        var next = monthCalendar + 1;
        var date = yearCalendar + '-' + next + '-1';

        $("#calendar_set .set_date").val(date);

        eventStart(yearCalendar, monthCalendar);
    })

    eventStart(yearCalendar, monthCalendar);

    worldFestAdd();


    function worldFestAdd() {

        if (WorldFest) {
            for (key in WorldFest) {

                var event = WorldFest[key];

                var dateStart = formatDate(event['dateStart']);
                var dateEnd = formatDate(event['dateEnd']);

                var adressMap = getMapLink(event['location']);

                var eventText = `<div class="container"> <div class="event">
                <span class="pop-app-close"></span>
                <p class="title">${event['name']}</p>
            <p class="location">location: <span>${event['location']}</span>
            <span>${adressMap}</span>
            </p>
            <p class="date">date: с <span> ${dateStart} по ${dateEnd}</span></p>
            <p class="description"> ${event['description']}</p>
            <span class="description-view"></span>
                </div></div>`;

                $('.world_events').append(eventText);

            }
        }

        $('.world_events .description').each(function () {
            var txt = $(this).html();
            $(this).html(linkify(txt));
        });

        $('.world_events .description-view').click(function () {
            $(this).parents('.event').toggleClass('content');
            $(this).parents('.container').toggleClass('pop-app');
        });


        $('.pop-app-close').click(function () {
            $(this).parents('.content').toggleClass('content');
            $(this).parents('.pop-app').toggleClass('pop-app');
        });
    }


});




function linkify(text) {
    var urlRegex =/(\b(https?|ftp|file):\/\/[-A-Z0-9+&@#\/%?=~_|!:,.;]*[-A-Z0-9+&@#\/%=~_|])/ig;
    return text.replace(urlRegex, function(url) {
        return '<a target="_blank" href="' + url + '">' + url + '</a>';
    });
}

////////////////////////////////////////////////////////
function Setobj(str, obj) {
    var serialObj = JSON.stringify(obj);
    localStorage.setItem(str, serialObj);
}
////////////////////////////////////////////////////////
function Getobj(str) {
    var res=JSON.parse(localStorage.getItem(str));
    return (res);
}

function setCalendarsSelected() {
    var calendarsId = Getobj('calendars-selected');

    if (!!calendarsId) {
        var selectedCalendars = {};
        calendarsId.forEach(function(id) {
            selectedCalendars[id] = id;
        });

        $("input.calendar_id").each(function () {
            var id = $(this).val();
            if (!!selectedCalendars[id]) {
                $(this).prop('checked', true);
            } else {
                $(this).prop('checked', false);
            }
        });
    }
}
function formatDate(date) {
    var newDate = new Date(date);

    var dey = newDate.getDate();
    if (dey < 10) dey = '0' + dey;

    var mm = newDate.getMonth() + 1;
    if (mm < 10) mm = '0' + mm;

    var yy = newDate.getFullYear();

    return dey + '.' + mm + '.' + yy;
}

function getMapLink(adres) {
    var link = "<a href='http://maps.google.com/maps?q=" + encodeURIComponent(adres) + "' target='_blank'>Google Map</a>";
    return link;
}

