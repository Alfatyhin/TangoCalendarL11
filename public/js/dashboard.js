

$('input.user_fb_calendar').on('click', function () {

    $('.fb_event_list').toggle(500);

    // FbDescriptionShowToInput();

});


function FbDescriptionShowToInput() {
    var active = false;
    $('.fb_event_list .event').each(function () {
        if ($(this).hasClass('active')) {
            active = true;
        }
    });
    if (active == false) {
        $('.fb_event_list .event').first().addClass('active');
        $('.fb_event_list .event').first().next().addClass('active');

    }
}

// $('.descript_show').on('click', function () {
//     // $('.fb_event_list .event.active').removeClass('active');
//     // $('.fb_event_list .description.active').removeClass('active');
//     //
//     // $(this).parents('.event').toggleClass('active');
//     // $(this).parents('.event').next().toggleClass('active');
//     //
//     // var hbox=$(this).parents('.event').outerHeight(true);
//
//     // var hx = Math.round($(this).parents('.event').offset().top);
//     // hx = hx - hbox;
//     // $(this).parents('.event').next().css('top', hx + 'px');
// });

$('.context_calendar').on('click', function () {
    $('.add_to_calendar_form').removeClass('hidden');
    var calendar_uid = $(this).attr('data-uid');
    var calendar_type = $(this).attr('data-type');

    $('input[name=type_calendar]').val(calendar_type);
    $('input[name=uid_event]').val(calendar_uid);

    var hbox=$(this).parents('.event').outerHeight(true);
    var hx = Math.round($(this).offset().top);
    hx = hx - hbox;
    $('.add_to_calendar_form').css('top', hx + 'px');
});
